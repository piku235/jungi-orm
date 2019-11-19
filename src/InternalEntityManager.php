<?php

namespace Jungi\Orm;

use Doctrine\DBAL\Connection;
use Jungi\Orm\Criteria\CriteriaQuery;
use Jungi\Orm\Mapping\BasicField;
use Jungi\Orm\Mapping\ClassMetadataFactory;
use Jungi\Orm\Mapping\CollectionField;
use Jungi\Orm\Mapping\Property;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class InternalEntityManager
{
    private $connection;
    private $classMetadataFactory;
    private $identityMap;
    private $commitMap;
    private $criteriaQueryExecutor;
    private $objectMapper;

    public function __construct(Connection $connection, ClassMetadataFactory $classMetadataFactory)
    {
        $this->connection = $connection;
        $this->classMetadataFactory = $classMetadataFactory;
        $this->identityMap = new IdentityMap();
        $this->commitMap = new \SplObjectStorage();
        $this->criteriaQueryExecutor = new CriteriaQueryExecutor($connection, $classMetadataFactory);
        $this->objectMapper = new ObjectMapper($connection, $classMetadataFactory);
    }

    public function createCriteriaQuery(string $type): CriteriaQuery
    {
        return new CriteriaQuery($this->classMetadataFactory->getEntityMetadata($type), $this);
    }

    public function createGenericRepository(string $type): GenericRepository
    {
        return new GenericRepository($type, $this);
    }

    public function executeCriteriaQuery(CriteriaQuery $query): \Iterator
    {
        if ($this->isDirty()) {
            $this->commit();
        }

        return $this->loadAllEntityResultSet($this->criteriaQueryExecutor->executeCriteriaQuery($query));
    }

    public function find(string $type, $id): ?object
    {
        if ($this->identityMap->contains($type, $id)) {
            return $this->identityMap->get($type, $id);
        }

        $q = $this->createCriteriaQuery($type);
        $cb = $q->builder();
        
        $entityMetadata = $q->getQueriedEntity();
        $q->where($cb->eq($entityMetadata->getIdProperty()->getName(), $id));

        return $q->getSingleResult();
    }

    public function isDirty(): bool
    {
        return (bool) count($this->commitMap);
    }

    public function commit(): void
    {
        $this->connection->beginTransaction();

        try {
            foreach ($this->commitMap as $entity) {
                $state = $this->commitMap->getInfo();

                switch ($state) {
                    case EntityStates::NEW:
                        $this->executeInsert($entity);
                        break;
                    case EntityStates::DIRTY:
                        $this->executeUpdate($entity);
                        break;
                    case EntityStates::REMOVED:
                        $this->executeRemove($entity);
                        break;
                    default:
                        throw new \RuntimeException(sprintf('Unknown entity state "%s".', $state));
                }
            }

            $this->connection->commit();
        } catch (\Throwable $e) {
            $this->connection->rollBack();
            throw $e;
        }

        $this->commitMap = new \SplObjectStorage();
    }

    public function save(object $entity): void
    {
        $entityMetadata = $this->classMetadataFactory->getEntityMetadata(get_class($entity));
        $entityId = $entityMetadata->getIdProperty()->getValue($entity);

        if ($this->identityMap->containsObject($entity)) {
            $this->registerEntity($entity, EntityStates::DIRTY);
            return;
        }

        $this->registerEntity($entity, EntityStates::NEW);
        $this->identityMap->put($entityId, $entity);
    }

    public function remove(object $entity): void
    {
        $type = get_class($entity);
        $entityMetadata = $this->classMetadataFactory->getEntityMetadata($type);
        $entityId = $entityMetadata->getIdProperty()->getValue($entity);

        $this->registerEntity($entity, EntityStates::REMOVED);
        $this->identityMap->remove($type, $entityId);
    }

    private function registerEntity(object $entity, string $state): void
    {
        $this->commitMap[$entity] = $state;
    }

    private function executeInsert(object $entity): void
    {
        $entityMetadata = $this->classMetadataFactory->getEntityMetadata(get_class($entity));

        $bindings = array();
        $data = $this->objectMapper->mapEntityTo($entity, $bindings);

        $this->connection->insert($entityMetadata->getTableName(), $data, $bindings);

        $collectionProperties = $entityMetadata->getNestedPropertiesOf(CollectionField::class);
        foreach ($collectionProperties as $collectionMetadata) {
            $this->insertCollection($entity, $collectionMetadata);
        }
    }

    private function executeUpdate(object $entity): void
    {
        $entityMetadata = $this->classMetadataFactory->getEntityMetadata(get_class($entity));
        $idMetadata = $entityMetadata->getIdProperty();
        /** @var BasicField $idFieldMetadata */
        $idFieldMetadata = $idMetadata->getField();

        $bindings = array();
        $data = $this->objectMapper->mapEntityTo($entity, $bindings);

        $entityId = $data[$idFieldMetadata->getColumnName()];
        unset($data[$idFieldMetadata->getColumnName()]);

        $this->connection->update($entityMetadata->getTableName(), $data, array(
            $idFieldMetadata->getColumnName() => $entityId,
        ), $bindings);

        $collectionProperties = $entityMetadata->getNestedPropertiesOf(CollectionField::class);
        foreach ($collectionProperties as $collectionMetadata) {
            $this->clearCollection($entityId, $collectionMetadata, $idMetadata);
            $this->insertCollection($entity, $collectionMetadata);
        }
    }

    private function executeRemove(object $entity): void
    {
        $entityMetadata = $this->classMetadataFactory->getEntityMetadata(get_class($entity));
        $idMetadata = $entityMetadata->getIdProperty();
        $idFieldMetadata = $entityMetadata->getIdProperty()->getField();
        $entityId = $idFieldMetadata->getType()->convertToDatabaseValue(
            $idMetadata->getValue($entity),
            $this->connection
        );

        $collectionProperties = $entityMetadata->getNestedPropertiesOf(CollectionField::class);
        foreach ($collectionProperties as $collectionMetadata) {
            $this->clearCollection($entityId, $collectionMetadata, $idMetadata);
        }

        $this->connection->delete($entityMetadata->getTableName(), array(
            $idFieldMetadata->getColumnName() => $entityId,
        ), [$idFieldMetadata->getType()->getBindingType()]);
    }

    private function clearCollection($entityId, Property $collectionMetadata, Property $entityIdMetadata): void
    {
        /** @var CollectionField $collectionFieldMetadata */
        $collectionFieldMetadata = $collectionMetadata->getField();

        $this->connection->delete($collectionFieldMetadata->getTableName(), array(
            $collectionFieldMetadata->getJoinColumnName() => $entityId
        ), array(
            $collectionFieldMetadata->getJoinColumnName() => $entityIdMetadata->getField()->getType()->getBindingType()
        ));
    }

    private function insertCollection(object $entity, Property $collectionMetadata): void
    {
        /** @var CollectionField $collectionFieldMetadata */
        $collectionFieldMetadata = $collectionMetadata->getField();

        $bindings = array();
        $data = $this->objectMapper->mapCollectionTo($entity, $collectionMetadata->getPath(), $bindings);

        foreach ($data as $elementData) {
            $this->connection->insert($collectionFieldMetadata->getTableName(), $elementData, $bindings);
        }
    }

    private function loadAllEntityResultSet(EntityResultSetInterface $resultSet): \Iterator
    {
        while ($resultSet->next()) {
            $entityId = $resultSet->readEntityId();

            if ($entity = $this->identityMap->get($resultSet->getEntityClass(), $entityId)) {
                yield $entity;
                continue;
            }

            yield $this->identityMap->put($entityId, $resultSet->readEntity());
        }
    }
}

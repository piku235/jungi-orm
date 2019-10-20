<?php

namespace Jungi\Orm;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Jungi\Orm\Criteria\CriteriaQuery;
use Jungi\Orm\Criteria\DbalConditionalExpressionVisitor;
use Jungi\Orm\Criteria\Order;
use Jungi\Orm\Mapping\BasicField;
use Jungi\Orm\Mapping\Class_;
use Jungi\Orm\Mapping\ClassMetadataFactory;
use Jungi\Orm\Mapping\CollectionField;
use Jungi\Orm\Mapping\EmbeddedField;
use Jungi\Orm\Mapping\Entity;
use Jungi\Orm\Mapping\Field;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class DistinctResultSetCriteriaQueryExecutor implements CriteriaQueryExecutorInterface
{
    private $connection;
    private $classMetadataFactory;
    private $queryMappings;

    public function __construct(Connection $connection, ClassMetadataFactory $classMetadataFactory)
    {
        $this->connection = $connection;
        $this->classMetadataFactory = $classMetadataFactory;
        $this->queryMappings = array();
    }

    /**
     * {@inheritdoc}
     */
    public function executeCriteriaQuery(CriteriaQuery $query): EntityResultSetInterface
    {
        $entityMetadata = $query->getQueriedEntity();
        $queryMapping = $this->getEntityQueryMapping($entityMetadata->getClassName());

        $idFieldMetadata = $entityMetadata->getIdProperty()->getField();
        $idColumnName = $queryMapping->getColumn($entityMetadata->getTableName(), $idFieldMetadata->getColumnName())->getResultName();

        $qb = $this->createDbalQueryBuilder($query, $queryMapping);
        $resultSet = new DistinctResultSet($qb->execute(), $idColumnName);
        $resultSetMapper = new DistinctResultSetMapper($this->connection, $entityMetadata, $queryMapping);

        return new DistinctEntityResultSet($entityMetadata->getClassName(), $resultSet, $resultSetMapper);
    }

    private function createDbalQueryBuilder(CriteriaQuery $criteriaQuery, QueryMapping $queryMapping): DbalQueryBuilder
    {
        $entityMetadata = $criteriaQuery->getQueriedEntity();
        $idFieldMetadata = $entityMetadata->getIdProperty()->getField();
        $tableAlias = $queryMapping->getTableAlias($entityMetadata->getTableName());
        $idColumnName = $queryMapping->getColumn($entityMetadata->getTableName(), $idFieldMetadata->getColumnName())->getQualifiedName();

        $qb = $this->connection->createQueryBuilder();

        if (null !== $criteriaQuery->getFirstResult() || null !== $criteriaQuery->getMaxResults()) {
            $sqb = $this->connection->createQueryBuilder();
            $sqb
                ->select('*')
                ->from($entityMetadata->getTableName());

            if (null !== $criteriaQuery->getFirstResult()) {
                $sqb->setFirstResult($criteriaQuery->getFirstResult());
            }
            if (null !== $criteriaQuery->getMaxResults()) {
                $sqb->setMaxResults($criteriaQuery->getMaxResults());
            }

            $qb->from('('.$sqb.')', $tableAlias);
        } else {
            $qb->from($entityMetadata->getTableName(), $tableAlias);
        }

        foreach ($queryMapping->getColumns() as $column) {
            $qb->addSelect(sprintf('%s AS %s', $column->getQualifiedName(), $column->getResultName()));
        }

        $collectionProperties = $entityMetadata->getNestedPropertiesOf(CollectionField::class);

        foreach ($collectionProperties as $collectionMetadata) {
            /** @var CollectionField $collectionFieldMetadata */
            $collectionFieldMetadata = $collectionMetadata->getField();
            $joinColumnName = $queryMapping->getColumn($collectionFieldMetadata->getTableName(), $collectionFieldMetadata->getJoinColumnName())->getQualifiedName();

            $qb->leftJoin(
                $tableAlias,
                $collectionFieldMetadata->getTableName(),
                $queryMapping->getTableAlias($collectionFieldMetadata->getTableName()),
                $qb->expr()->eq($idColumnName, $joinColumnName)
            );
        }

        if ($criteriaQuery->getConditionalExpression()) {
            $conditionVisitor = new DbalConditionalExpressionVisitor($qb, $entityMetadata, $queryMapping);
            $conditionVisitor->visit($criteriaQuery->getConditionalExpression());
        }

        foreach ($criteriaQuery->getOrderings() as $order) {
            $property = $entityMetadata->getProperty($order->getPropertyName());
            $columnName = $queryMapping->getColumn($entityMetadata->getTableName(), $property->getField()->getColumnName());

            $qb->addOrderBy($columnName, $order->getDirection());
        }

        // VERY IMPORTANT!
        // without that the result set will be returned
        // in the unspecified order resulting entities with
        // collections to be totally messed up
        $qb->orderBy($idColumnName, Order::ASC);

        return $qb;
    }

    private function getEntityQueryMapping(string $type): QueryMapping
    {
        if (isset($this->queryMappings[$type])) {
            return $this->queryMappings[$type];
        }

        $entityMetadata = $this->classMetadataFactory->getEntityMetadata($type);
        $tableAliasMap = $this->generateTableAliasMap($entityMetadata);

        $tableColumnMap = array(
            $entityMetadata->getTableName() => array()
        );
        $this->mapClassColumns($tableColumnMap, $entityMetadata, $entityMetadata, $entityMetadata->getTableName(), $tableAliasMap);

        return $this->queryMappings[$type] = new QueryMapping($tableAliasMap, $tableColumnMap);
    }

    private function generateTableAliasMap(Entity $entityMetadata): array
    {
        $tableAliasMap = [];
        $indexes = [];

        $tableNames = [$entityMetadata->getTableName()];

        $properties = $entityMetadata->getNestedPropertiesOf(CollectionField::class);
        foreach ($properties as $property) {
            $tableNames[] = $property->getField()->getTableName();
        }

        foreach ($tableNames as $tableName) {
            $alias = $tableName[0];

            if (!isset($indexes[$alias])) {
                $indexes[$alias] = 0;
            }

            $alias .= $indexes[$alias]++;

            $tableAliasMap[$tableName] = $alias;
        }

        return $tableAliasMap;
    }

    private function mapClassColumns(array &$tableColumnMap, Class_ $classMetadata, Entity $entity, string $tableName, array $tableAliasMap): void
    {
        $entityIdFieldMetadata = $entity->getIdProperty()->getField();

        foreach ($classMetadata->getProperties() as $propertyMetadata) {
            $fieldMetadata = $propertyMetadata->getField();

            switch (true) {
                case $fieldMetadata instanceof CollectionField:
                    $tableColumnMap[$fieldMetadata->getTableName()] = array();
                    $joinField = new BasicField($fieldMetadata->getJoinColumnName(), $entityIdFieldMetadata->getType());

                    $this->mapFieldColumns($tableColumnMap, $joinField, $entity, $fieldMetadata->getTableName(), $tableAliasMap);
                    $this->mapFieldColumns($tableColumnMap, $fieldMetadata->getKey(), $entity, $fieldMetadata->getTableName(), $tableAliasMap);
                    $this->mapFieldColumns($tableColumnMap, $fieldMetadata->getElement(), $entity, $fieldMetadata->getTableName(), $tableAliasMap);
                    break;
                default:
                    $this->mapFieldColumns($tableColumnMap, $fieldMetadata, $entity, $tableName, $tableAliasMap);
                    break;
            }
        }
    }

    private function mapFieldColumns(array &$tableColumnMap, Field $fieldMetadata, Entity $entity, string $tableName, array $tableAliasMap): void
    {
        $tableAlias = $tableAliasMap[$tableName];

        switch (true) {
            case $fieldMetadata instanceof EmbeddedField:
                if (null !== $nullField = $fieldMetadata->getNullField()) {
                    $this->mapFieldColumns($tableColumnMap, $nullField, $entity, $tableName, $tableAliasMap);
                }

                $this->mapClassColumns($tableColumnMap, $fieldMetadata->getEmbeddable(), $entity, $tableName, $tableAliasMap);
                break;
            case $fieldMetadata instanceof BasicField:
                $tableColumnMap[$tableName][$fieldMetadata->getColumnName()] = $this->getColumnFor($tableAlias, $fieldMetadata->getColumnName());
                break;
        }
    }

    private function getColumnFor(string $tableAlias, string $columnName): QueryColumnMapping
    {
        return new QueryColumnMapping($tableAlias.'.'.$columnName, $tableAlias.'_'.$columnName);
    }
}

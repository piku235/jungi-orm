<?php

namespace Jungi\Orm;

use Doctrine\DBAL\Connection;
use Jungi\Orm\Criteria\CriteriaQuery;
use Jungi\Orm\Mapping\ClassMetadataFactory;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class EntityManager
{
    private $iem;

    public function __construct(Connection $connection, ClassMetadataFactory $classMetadataFactory)
    {
        $this->iem = new InternalEntityManager($connection, $classMetadataFactory);
    }

    public function createCriteriaQuery(string $type): CriteriaQuery
    {
        return $this->iem->createCriteriaQuery($type);
    }

    public function createGenericRepository(string $type): GenericRepository
    {
        return $this->iem->createGenericRepository($type);
    }

    public function find(string $type, $id): ?object
    {
        return $this->iem->find($type, $id);
    }

    public function isDirty(): bool
    {
        return $this->iem->isDirty();
    }

    public function commit(): void
    {
        $this->iem->commit();
    }

    public function save(object $entity): void
    {
        $this->iem->save($entity);
    }

    public function remove(object $entity): void
    {
        $this->iem->remove($entity);
    }

    public function clear(): void
    {
        $this->iem->clear();
    }
}

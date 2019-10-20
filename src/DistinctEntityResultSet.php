<?php

namespace Jungi\Orm;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class DistinctEntityResultSet implements EntityResultSetInterface
{
    private $entityClass;
    private $resultSet;
    private $resultSetMapper;

    public function __construct(string $entityClass, DistinctResultSet $resultSet, DistinctResultSetMapper $resultSetMapper)
    {
        $this->entityClass = $entityClass;
        $this->resultSet = $resultSet;
        $this->resultSetMapper = $resultSetMapper;
    }

    public function next(): bool
    {
        return (bool) $this->resultSet->next();
    }

    public function valid(): bool
    {
        return (bool) $this->resultSet->current();
    }

    public function readEntity(): ?object
    {
        return $this->resultSet->current() ? $this->resultSetMapper->mapToEntity($this->resultSet) : null;
    }

    public function readEntityId()
    {
        return $this->resultSet->current() ? $this->resultSetMapper->peekEntityId($this->resultSet) : null;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }
}

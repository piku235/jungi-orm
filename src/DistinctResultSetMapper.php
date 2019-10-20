<?php

namespace Jungi\Orm;

use Doctrine\DBAL\Connection;
use Jungi\Orm\Mapping\BasicField;
use Jungi\Orm\Mapping\Class_;
use Jungi\Orm\Mapping\CollectionField;
use Jungi\Orm\Mapping\EmbeddedField;
use Jungi\Orm\Mapping\Entity;
use Jungi\Orm\Mapping\Field;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class DistinctResultSetMapper
{
    private $connection;
    private $entityMetadata;
    private $queryMapping;

    public function __construct(Connection $connection, Entity $entityMetadata, QueryMapping $queryMapping)
    {
        $this->connection = $connection;
        $this->entityMetadata = $entityMetadata;
        $this->queryMapping = $queryMapping;
    }

    public function peekEntityId(DistinctResultSet $resultSet)
    {
        $idFieldMetadata = $this->entityMetadata->getIdProperty()->getField();

        return $this->mapResultSetToValue($resultSet, $idFieldMetadata, $this->entityMetadata->getTableName());
    }

    public function mapToEntity(DistinctResultSet $resultSet): object
    {
        $collectionProperties = iterator_to_array($this->entityMetadata->getNestedPropertiesOf(CollectionField::class));

        $entity = $this->mapResultSetToObject($resultSet, $this->entityMetadata, $this->entityMetadata->getTableName());
        $collections = [];

        do {
            foreach ($collectionProperties as $collectionMetadata) {
                if (!isset($collections[$collectionMetadata->getPath()])) {
                    $collections[$collectionMetadata->getPath()] = array();
                }

                $keyValue = $this->mapResultSetToKeyValue($resultSet, $collectionMetadata->getField());
                if (!$keyValue) {
                    continue;
                }

                list ($key, $value) = $keyValue;

                $collections[$collectionMetadata->getPath()][$key] = $value;
            }
        } while ($resultSet->untilNext());

        foreach ($collections as $propertyName => $collection) {
            $this->entityMetadata->setPropertyValue($entity, $propertyName, $collection);
        }

        return $entity;
    }
    
    private function mapResultSetToObject(DistinctResultSet $resultSet, Class_ $classMetadata, string $tableName): object
    {
        $properties = array();
        foreach ($classMetadata->getProperties() as $propertyMetadata) {
            if ($propertyMetadata->isCollection()) {
                continue;
            }

            $properties[$propertyMetadata->getName()] = $this->mapResultSetToValue($resultSet, $propertyMetadata->getField(), $tableName);
        }

        return $classMetadata->populateNewInstance($properties);
    }

    private function mapResultSetToValue(DistinctResultSet $resultSet, Field $fieldMetadata, string $tableName)
    {
        switch (true) {
            default:
                throw new \RuntimeException(sprintf('Field "%s" not supported.', get_class($fieldMetadata)));
            case $fieldMetadata instanceof EmbeddedField:
                return $this->mapResultSetToEmbeddedValue($resultSet, $fieldMetadata, $tableName);
            case $fieldMetadata instanceof BasicField:
                return $this->mapResultSetToBasicValue($resultSet, $fieldMetadata, $tableName);
        }
    }

    private function mapResultSetToKeyValue(DistinctResultSet $resultSet, CollectionField $collectionMetadata): ?array
    {
        $row = $resultSet->current();
        $joinColumnName = $this->queryMapping->getColumn($collectionMetadata->getTableName(), $collectionMetadata->getJoinColumnName())->getResultName();

        if (null === $row[$joinColumnName]) {
            return null;
        }

        $key = $this->mapResultSetToBasicValue($resultSet, $collectionMetadata->getKey(), $collectionMetadata->getTableName());
        $element = $this->mapResultSetToValue($resultSet, $collectionMetadata->getElement(), $collectionMetadata->getTableName());

        return [$key, $element];
    }

    private function mapResultSetToEmbeddedValue(DistinctResultSet $resultSet, EmbeddedField $embeddedMetadata, string $tableName): ?object
    {
        $row = $resultSet->current();

        if ($embeddedMetadata->isNullable()) {
            $nullColumnName = $this->queryMapping->getColumn($tableName, $embeddedMetadata->getNullField()->getColumnName())->getResultName();
            if (!isset($row[$nullColumnName])) {
                throw new \RuntimeException(sprintf('Embeddable null column "%s" not present in the data.', $nullColumnName));
            }

            if ($row[$nullColumnName]) {
                return null;
            }
        }

        return $this->mapResultSetToObject($resultSet, $embeddedMetadata->getEmbeddable(), $tableName);
    }

    private function mapResultSetToBasicValue(DistinctResultSet $resultSet, BasicField $basicMetadata, string $tableName)
    {
        $row = $resultSet->current();
        $columnName = $this->queryMapping->getColumn($tableName, $basicMetadata->getColumnName())->getResultName();

        if (!array_key_exists($columnName, $row)) {
            throw new \RuntimeException(sprintf('Unable to find column "%s" in the database data.', $columnName));
        }

        $value = $basicMetadata->getType()->convertToPHPValue(
            $row[$columnName],
            $this->connection->getDatabasePlatform()
        );

        if (!$basicMetadata->isNullable() && is_null($value)) {
            throw new \UnexpectedValueException(sprintf('Column "%s" cannot be null.', $columnName));
        }

        return $value;
    }
}

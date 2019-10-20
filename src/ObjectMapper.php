<?php

namespace Jungi\Orm;

use Doctrine\DBAL\Connection;
use Jungi\Orm\Mapping\BasicField;
use Jungi\Orm\Mapping\Class_;
use Jungi\Orm\Mapping\ClassMetadataFactory;
use Jungi\Orm\Mapping\CollectionField;
use Jungi\Orm\Mapping\EmbeddedField;
use Jungi\Orm\Mapping\Field;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class ObjectMapper
{
    private $connection;
    private $classMappingFactory;

    public function __construct(Connection $connection, ClassMetadataFactory $classMappingFactory)
    {
        $this->connection = $connection;
        $this->classMappingFactory = $classMappingFactory;
    }

    public function mapEntityTo(object $entity, array &$bindings = []): array
    {
        $entityMetadata = $this->classMappingFactory->getEntityMetadata(get_class($entity));

        $data = array();
        $this->mapObjectToDataFrom($data, $entity, $entityMetadata, $bindings);

        return $data;
    }

    public function mapCollectionTo(object $entity, string $propertyName, array &$bindings = []): array
    {
        $entityMetadata = $this->classMappingFactory->getEntityMetadata(get_class($entity));
        $entityIdMetadata = $entityMetadata->getIdProperty();
        $collectionFieldMetadata = $entityMetadata->getProperty($propertyName)->getField();
        $joinField = new BasicField($collectionFieldMetadata->getJoinColumnName(), $entityIdMetadata->getField()->getType());

        if (!$collectionFieldMetadata instanceof CollectionField) {
            throw new \InvalidArgumentException(sprintf(
                'Expected the property "%s::%s" to be of collection type.',
                $entityMetadata->getClassName(),
                $propertyName
            ));
        }

        $data = [];
        $collection = $entityMetadata->getPropertyValue($entity, $propertyName);

        foreach ($collection as $key => $element) {
            $elementData = array();

            $this->mapFieldValueToData($elementData, $entityIdMetadata->getValue($entity), $joinField, $bindings);
            $this->mapFieldValueToData($elementData, $key, $collectionFieldMetadata->getKey(), $bindings);
            $this->mapFieldValueToData($elementData, $element, $collectionFieldMetadata->getElement(), $bindings);

            $data[] = $elementData;
        }

        return $data;
    }

    private function mapObjectToDataFrom(array &$data, object $object, Class_ $classMetadata, array &$bindings): void
    {
        foreach ($classMetadata->getProperties() as $propertyMetadata) {
            // not part of the object data, mapped separately
            if ($propertyMetadata->getField() instanceof CollectionField) {
                continue;
            }

            $propertyName = $propertyMetadata->getName();
            $propertyValue = $propertyMetadata->getValue($object);

            if (is_null($propertyValue) && !$propertyMetadata->isNullable()) {
                throw new \InvalidArgumentException(sprintf(
                    'Property "%s::%s" cannot be null.',
                    $classMetadata->getClassName(),
                    $propertyName
                ));
            }

            $this->mapFieldValueToData($data, $propertyValue, $propertyMetadata->getField(), $bindings);
        }
    }

    private function mapFieldValueToData(array &$data, $value, Field $fieldMetadata, array &$bindings): void
    {
        if (is_null($value) && !$fieldMetadata->isNullable()) {
            throw new \InvalidArgumentException('Field cannot be null.');
        }

        switch (true) {
            default:
                throw new \RuntimeException(sprintf('Field "%s" not supported.', get_class($fieldMetadata)));
            case $fieldMetadata instanceof EmbeddedField:
                if (is_null($value)) {
                    $this->makeEmbeddedFieldNull($data, $fieldMetadata, $bindings);
                    break;
                }

                if ($fieldMetadata->isNullable()) {
                    $nullField = $fieldMetadata->getNullField();

                    $data[$nullField->getColumnName()] = false;
                    $bindings[$nullField->getColumnName()] = $nullField->getType()->getBindingType();
                }

                $this->mapObjectToDataFrom($data, $value, $fieldMetadata->getEmbeddable(), $bindings);

                break;
            case $fieldMetadata instanceof BasicField:
                $data[$fieldMetadata->getColumnName()] = $fieldMetadata->getType()->convertToDatabaseValue(
                    $value,
                    $this->connection->getDatabasePlatform()
                );
                $bindings[$fieldMetadata->getColumnName()] = $fieldMetadata->getType()->getBindingType();

                break;
        }
    }

    private function makeEmbeddedFieldNull(array &$data, EmbeddedField $embeddedMetadata, array &$bindings): void
    {
        if ($embeddedMetadata->isNullable()) {
            $nullField = $embeddedMetadata->getNullField();

            $data[$nullField->getColumnName()] = true;
            $bindings[$nullField->getColumnName()] = $nullField->getType()->getBindingType();
        }

        foreach ($embeddedMetadata->getEmbeddable()->getProperties() as $propertyMetadata) {
            $fieldMetadata = $propertyMetadata->getField();

            switch (true) {
                default:
                    throw new \RuntimeException(sprintf('Field "%s" not supported.', get_class($propertyMetadata)));
                case $fieldMetadata instanceof EmbeddedField:
                    $this->makeEmbeddedFieldNull($data, $fieldMetadata, $bindings);
                    break;
                case $fieldMetadata instanceof CollectionField:
                    // not applicable
                    break;
                case $fieldMetadata instanceof BasicField:
                    $this->mapFieldValueToData($data, null, $fieldMetadata, $bindings);
                    break;
            }
        }
    }
}

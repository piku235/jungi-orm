<?php

namespace Jungi\Orm\Mapping;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class Entity extends Class_
{
    private $tableName;
    private $idProperty;

    /**
     * @param string     $className
     * @param string     $tableName
     * @param string     $idPropertyName
     * @param Property[] $properties
     */
    public function __construct(string $className, string $tableName, string $idPropertyName, array $properties)
    {
        parent::__construct($className, $properties);

        $this->tableName = $tableName;
        $this->setIdProperty($idPropertyName);
    }

    public function getIdProperty(): Property
    {
        return $this->idProperty;
    }

    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    private function setIdProperty(string $identifierPropertyName)
    {
        $this->idProperty = $this->getProperty($identifierPropertyName);

        if (!$this->idProperty->getField() instanceof BasicField) {
            throw new \InvalidArgumentException('Identifier property should be of basic type.');
        }
    }
}

<?php

namespace Jungi\Orm\Mapping;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class CollectionFieldDefinition extends FieldDefinition
{
    /**
     * @var string|null
     */
    private $tableName;

    /**
     * @var string|null
     */
    private $joinColumnName;

    /**
     * @var BasicFieldDefinition|null
     */
    private $keyDefinition;

    /**
     * @var FieldDefinition|null
     */
    private $elementDefinition;

    /**
     * @var string|null
     */
    private $virtualColumnName;

    /**
     * @param string $columnPrefix
     *
     * @return self
     */
    public function applyColumnPrefix(string $columnPrefix): self
    {
        $clone = clone $this;
        $clone->virtualColumnName = $columnPrefix.$clone->virtualColumnName;

        return $clone;
    }

    /**
     * @return string|null
     */
    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    /**
     * @param string|null $tableName
     */
    public function setTableName(?string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string|null
     */
    public function getJoinColumnName(): ?string
    {
        return $this->joinColumnName;
    }

    /**
     * @param string|null $joinColumnName
     */
    public function setJoinColumnName(?string $joinColumnName): void
    {
        $this->joinColumnName = $joinColumnName;
    }

    /**
     * @return BasicFieldDefinition|null
     */
    public function getKeyDefinition(): ?BasicFieldDefinition
    {
        return $this->keyDefinition;
    }

    /**
     * @param BasicFieldDefinition|null $keyDefinition
     */
    public function setKeyDefinition(?BasicFieldDefinition $keyDefinition): void
    {
        $this->keyDefinition = $keyDefinition;
    }

    /**
     * @return FieldDefinition|null
     */
    public function getElementDefinition(): ?FieldDefinition
    {
        return $this->elementDefinition;
    }

    /**
     * @param FieldDefinition|null $elementDefinition
     */
    public function setElementDefinition(?FieldDefinition $elementDefinition): void
    {
        $this->elementDefinition = $elementDefinition;
    }

    /**
     * @return string|null
     */
    public function getVirtualColumnName(): ?string
    {
        return $this->virtualColumnName;
    }

    /**
     * @param string|null $virtualColumnName
     */
    public function setVirtualColumnName(?string $virtualColumnName): void
    {
        $this->virtualColumnName = $virtualColumnName;
    }
}

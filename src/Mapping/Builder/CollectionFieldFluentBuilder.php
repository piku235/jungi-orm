<?php

namespace Jungi\Orm\Mapping\Builder;

use Jungi\Orm\Mapping\BasicFieldDefinition;
use Jungi\Orm\Mapping\CollectionFieldDefinition;
use Jungi\Orm\Mapping\FieldDefinition;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class CollectionFieldFluentBuilder
{
    private $tableName;
    private $joinColumnName;
    private $keyDefinition;
    private $elementDefinition;
    private $virtualColumnName;

    public function tableName(string $tableName): self
    {
        $this->tableName = $tableName;

        return $this;
    }

    public function joinColumnName(string $joinColumnName): self
    {
        $this->joinColumnName = $joinColumnName;

        return $this;
    }

    public function key(BasicFieldDefinition $definition): self
    {
        $this->keyDefinition = $definition;

        return $this;
    }

    public function element(FieldDefinition $definition): self
    {
        $this->elementDefinition = $definition;

        return $this;
    }

    public function virtualColumnName(string $columnName): self
    {
        $this->virtualColumnName = $columnName;

        return $this;
    }

    public function build(): CollectionFieldDefinition
    {
        $definition = new CollectionFieldDefinition();
        $definition->setTableName($this->tableName);
        $definition->setJoinColumnName($this->joinColumnName);
        $definition->setKeyDefinition($this->keyDefinition);
        $definition->setElementDefinition($this->elementDefinition);
        $definition->setVirtualColumnName($this->virtualColumnName);

        return $definition;
    }
}

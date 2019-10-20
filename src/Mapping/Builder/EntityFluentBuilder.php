<?php

namespace Jungi\Orm\Mapping\Builder;

use Jungi\Orm\Mapping\EntityDefinition;
use Jungi\Orm\Mapping\FieldDefinition;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class EntityFluentBuilder
{
    use ClassFluentBuilderTrait;

    private $tableName;
    private $idPropertyName;

    public function tableName(string $tableName): self
    {
        $this->tableName = $tableName;

        return $this;
    }

    public function id(string $propertyName, FieldDefinition $fieldDefinition): self
    {
        $this->property($propertyName, $fieldDefinition);

        $this->idPropertyName = $propertyName;

        return $this;
    }

    public function build(): EntityDefinition
    {
        $definition = new EntityDefinition();
        $definition->setClassName($this->className);
        $definition->setPropertyDefinitions($this->propertyDefinitions);
        $definition->setTableName($this->tableName);
        $definition->setIdPropertyName($this->idPropertyName);

        return $definition;
    }
}

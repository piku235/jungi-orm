<?php

namespace Jungi\Orm\Mapping\Builder;

use Jungi\Orm\Mapping\FieldDefinition;
use Jungi\Orm\Mapping\PropertyDefinition;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
trait ClassFluentBuilderTrait
{
    private $className;
    private $propertyDefinitions = [];

    public function className(string $className): self
    {
        $this->className = $className;

        return $this;
    }

    public function property(string $name, FieldDefinition $fieldDefinition): self
    {
        $definition = new PropertyDefinition();
        $definition->setName($name);
        $definition->setFieldDefinition($fieldDefinition);

        $this->propertyDefinitions[$name] = $definition;

        return $this;
    }
}

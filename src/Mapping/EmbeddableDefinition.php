<?php

namespace Jungi\Orm\Mapping;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class EmbeddableDefinition extends ClassDefinition
{
    /**
     * @param string $columnPrefix
     *
     * @return self
     */
    public function applyColumnPrefix(string $columnPrefix): self
    {
        $clone = clone $this;
        $clone->propertyDefinitions = array_map(function (PropertyDefinition $definition) use ($columnPrefix) {
            return $definition->applyColumnPrefix($columnPrefix);
        }, $clone->propertyDefinitions);

        return $clone;
    }

    /**
     * @return self
     */
    public function null(): self
    {
        $clone = clone $this;
        $clone->propertyDefinitions = array_map(function (PropertyDefinition $definition) {
            return $definition->null();
        }, $clone->propertyDefinitions);

        return $clone;
    }
}

<?php

namespace Jungi\Orm\Mapping\Builder;

use Jungi\Orm\Mapping\EmbeddableDefinition;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class EmbeddableFluentBuilder
{
    use ClassFluentBuilderTrait;

    public function build(): EmbeddableDefinition
    {
        $definition = new EmbeddableDefinition();
        $definition->setClassName($this->className);
        $definition->setPropertyDefinitions($this->propertyDefinitions);

        return $definition;
    }
}

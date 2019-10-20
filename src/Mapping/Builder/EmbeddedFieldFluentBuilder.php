<?php

namespace Jungi\Orm\Mapping\Builder;

use Jungi\Orm\Mapping\EmbeddableDefinition;
use Jungi\Orm\Mapping\EmbeddedFieldDefinition;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class EmbeddedFieldFluentBuilder
{
    private $embeddableDefinition;
    private $embeddableClassName;
    private $columnPrefix;
    private $nullable = false;

    public function embeddable(EmbeddableDefinition $definition): self
    {
        $this->embeddableDefinition = $definition;

        return $this;
    }

    public function embeddableOf(string $className): self
    {
        $this->embeddableClassName = $className;

        return $this;
    }

    public function columnPrefix(string $columnPrefix): self
    {
        $this->columnPrefix = $columnPrefix;

        return $this;
    }

    public function nullable(): self
    {
        $this->nullable = true;

        return $this;
    }

    public function notNullable(): self
    {
        $this->nullable = false;

        return $this;
    }

    public function build(): EmbeddedFieldDefinition
    {
        $definition = new EmbeddedFieldDefinition();

        if ($this->embeddableDefinition) {
            $definition->setEmbeddableDefinition($this->embeddableDefinition);
        } else {
            $definition->setEmbeddableClassName($this->embeddableClassName);
        }

        $definition->setColumnPrefix($this->columnPrefix);
        $definition->setNullable($this->nullable);

        return $definition;
    }
}

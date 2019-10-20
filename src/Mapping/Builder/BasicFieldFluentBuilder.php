<?php

namespace Jungi\Orm\Mapping\Builder;

use Jungi\Orm\Mapping\BasicFieldDefinition;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class BasicFieldFluentBuilder
{
    private $type;
    private $columnName;
    private $nullable = false;

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function columnName(string $columnName): self
    {
        $this->columnName = $columnName;

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

    public function build(): BasicFieldDefinition
    {
        $definition = new BasicFieldDefinition();
        $definition->setColumnName($this->columnName);
        $definition->setType($this->type);
        $definition->setNullable($this->nullable);

        return $definition;
    }
}

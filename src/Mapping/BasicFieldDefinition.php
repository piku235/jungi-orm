<?php

namespace Jungi\Orm\Mapping;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class BasicFieldDefinition extends FieldDefinition
{
    /**
     * @var string|null
     */
    private $type;

    /**
     * @var string|null
     */
    private $columnName;

    /**
     * @param string $columnPrefix
     *
     * @return self
     */
    public function applyColumnPrefix(string $columnPrefix): self
    {
        $clone = clone $this;
        $clone->columnName = $columnPrefix.$clone->columnName;

        return $clone;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType(?string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getColumnName(): ?string
    {
        return $this->columnName;
    }

    /**
     * @param string|null $columnName
     */
    public function setColumnName(?string $columnName): void
    {
        $this->columnName = $columnName;
    }
}

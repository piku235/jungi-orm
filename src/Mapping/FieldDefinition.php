<?php

namespace Jungi\Orm\Mapping;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
abstract class FieldDefinition
{
    /**
     * @var bool
     */
    protected $nullable = false;

    /**
     * @return $this
     */
    public function null(): self
    {
        $clone = clone $this;
        $clone->setNullable(true);

        return $clone;
    }

    /**
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->nullable;
    }

    /**
     * @param bool $nullable
     */
    public function setNullable(bool $nullable): void
    {
        $this->nullable = $nullable;
    }
}

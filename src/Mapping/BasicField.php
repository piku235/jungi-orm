<?php

namespace Jungi\Orm\Mapping;

use Doctrine\DBAL\Types\Type;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class BasicField implements Field
{
    private $columnName;
    private $type;
    private $nullable;

    public function __construct(string $columnName, Type $type, bool $nullable = false)
    {
        $this->columnName = $columnName;
        $this->type = $type;
        $this->nullable = $nullable;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function getColumnName(): string
    {
        return $this->columnName;
    }

    public function getType(): Type
    {
        return $this->type;
    }
}

<?php

namespace Jungi\Orm\Mapping;

use Doctrine\DBAL\Types\Type;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class EmbeddedField implements Field
{
    private $embeddable;
    private $nullField;

    public function __construct(Embeddable $embeddable, ?string $nullColumnName = null)
    {
        $this->embeddable = $embeddable;
        $this->nullField = $nullColumnName ? new BasicField($nullColumnName, Type::getType(Type::BOOLEAN)) : null;
    }

    public function getEmbeddable(): Embeddable
    {
        return $this->embeddable;
    }

    public function isNullable(): bool
    {
        return null !== $this->nullField;
    }

    public function getNullField(): ?BasicField
    {
        return $this->nullField;
    }
}

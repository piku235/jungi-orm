<?php

namespace Jungi\Orm\Mapping;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class CollectionField implements Field
{
    private $tableName;
    private $joinColumnName;
    private $key;
    private $element;
    private $virtualColumnName;
    private $nullable;

    public function __construct(string $tableName, string $joinColumnName, BasicField $key, Field $element, string $virtualColumnName, bool $nullable = false)
    {
        if ($key->isNullable()) {
            throw new \InvalidArgumentException(sprintf('Collection key cannot be nullable.'));
        }
        if ($element instanceof self) {
            throw new \InvalidArgumentException('A collection element cannot be of collection type.');
        }
        if ($element instanceof EmbeddedField && $element->getEmbeddable()->hasPropertyOf(CollectionField::class)) {
            throw new \InvalidArgumentException('Embeddables with collection properties are not supported as the collection element.');
        }

        $this->tableName = $tableName;
        $this->joinColumnName = $joinColumnName;
        $this->key = $key;
        $this->element = $element;
        $this->virtualColumnName = $virtualColumnName;
        $this->nullable = $nullable;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getJoinColumnName(): string
    {
        return $this->joinColumnName;
    }

    public function getKey(): BasicField
    {
        return $this->key;
    }

    public function getElement(): Field
    {
        return $this->element;
    }

    public function getVirtualColumnName(): string
    {
        return $this->virtualColumnName;
    }
}

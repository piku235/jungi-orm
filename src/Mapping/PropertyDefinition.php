<?php

namespace Jungi\Orm\Mapping;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class PropertyDefinition
{
    /**
     * @var string|null
     */
    private $name;

    /**
     * @var FieldDefinition|null
     */
    private $fieldDefinition;

    /**
     * @param string $columnPrefix
     *
     * @return self
     */
    public function applyColumnPrefix(string $columnPrefix): self
    {
        $clone = clone $this;
        $field = $clone->fieldDefinition;

        switch (true) {
            default:
                throw new \RuntimeException(sprintf('Field "%s" not supported.', get_class($field)));
            case $field instanceof EmbeddedFieldDefinition:
                $clone->fieldDefinition = $field->applyColumnPrefix($columnPrefix);
                break;
            case $field instanceof BasicFieldDefinition:
                $clone->fieldDefinition = $field->applyColumnPrefix($columnPrefix);
                break;
            case $field instanceof CollectionFieldDefinition:
                $clone->fieldDefinition = $field->applyColumnPrefix($columnPrefix);
                break;
        }

        return $clone;
    }

    /**
     * @return self
     */
    public function null(): self
    {
        $clone = clone $this;
        $clone->fieldDefinition = $clone->fieldDefinition->null();

        return $clone;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return FieldDefinition|null
     */
    public function getFieldDefinition(): ?FieldDefinition
    {
        return $this->fieldDefinition;
    }

    /**
     * @param FieldDefinition|null $fieldDefinition
     */
    public function setFieldDefinition(?FieldDefinition $fieldDefinition): void
    {
        switch (true) {
            case $fieldDefinition instanceof BasicFieldDefinition && null === $fieldDefinition->getColumnName():
                $fieldDefinition->setColumnName($this->name);
                break;
            case $fieldDefinition instanceof EmbeddedFieldDefinition && null === $fieldDefinition->getColumnPrefix():
                $fieldDefinition->setColumnPrefix($this->name.'_');
                break;
            case $fieldDefinition instanceof CollectionFieldDefinition && null === $fieldDefinition->getVirtualColumnName():
                $fieldDefinition->setVirtualColumnName($this->name);
                break;
        }

        $this->fieldDefinition = $fieldDefinition;
    }
}

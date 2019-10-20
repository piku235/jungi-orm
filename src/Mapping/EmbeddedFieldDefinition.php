<?php

namespace Jungi\Orm\Mapping;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class EmbeddedFieldDefinition extends FieldDefinition
{
    /**
     * @var EmbeddableDefinition|null
     */
    private $embeddableDefinition;

    /**
     * @var string|null
     */
    private $embeddableClassName;

    /**
     * @var string|null
     */
    private $columnPrefix;

    /**
     * @var string|null
     */
    private $nullColumnName;

    /**
     * @param string $columnPrefix
     *
     * @return self
     */
    public function applyColumnPrefix(string $columnPrefix): self
    {
        $clone = clone $this;
        $clone->setColumnPrefix($columnPrefix.$clone->columnPrefix);

        return $clone;
    }

    /**
     * @return EmbeddableDefinition|null
     */
    public function getEmbeddableDefinition(): ?EmbeddableDefinition
    {
        return $this->embeddableDefinition;
    }

    /**
     * @param EmbeddableDefinition|null $embeddableDefinition
     */
    public function setEmbeddableDefinition(?EmbeddableDefinition $embeddableDefinition): void
    {
        $this->embeddableDefinition = $embeddableDefinition;
        $this->embeddableClassName = null;
    }

    /**
     * @return string|null
     */
    public function getEmbeddableClassName(): ?string
    {
        return $this->embeddableClassName;
    }

    /**
     * @param string|null $embeddableClassName
     */
    public function setEmbeddableClassName(?string $embeddableClassName): void
    {
        if (null !== $embeddableClassName && !class_exists($embeddableClassName)) {
            throw new \InvalidArgumentException(sprintf('Class "%s" not found.', $embeddableClassName));
        }

        $this->embeddableClassName = $embeddableClassName;
        $this->embeddableDefinition = null;
    }

    /**
     * @return string|null
     */
    public function getNullColumnName(): ?string
    {
        return $this->nullColumnName;
    }

    /**
     * @return string|null
     */
    public function getColumnPrefix(): ?string
    {
        return $this->columnPrefix;
    }

    /**
     * @param string|null $columnPrefix
     */
    public function setColumnPrefix(?string $columnPrefix): void
    {
        $this->columnPrefix = $columnPrefix;

        if ($columnPrefix && $this->nullable) {
            $this->nullColumnName = $columnPrefix.'_null';
        }
    }
}

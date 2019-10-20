<?php

namespace Jungi\Orm\Mapping;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
abstract class ClassDefinition
{
    /**
     * @var string|null
     */
    protected $className;

    /**
     * @var PropertyDefinition[]
     */
    protected $propertyDefinitions = [];

    /**
     * @return string|null
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * @param string|null $className
     */
    public function setClassName(?string $className): void
    {
        $this->className = $className;
    }

    /**
     * @param PropertyDefinition[] $propertyDefinitions
     */
    public function setPropertyDefinitions(array $propertyDefinitions): void
    {
        $this->propertyDefinitions = [];
        foreach ($propertyDefinitions as $propertyDefinition) {
            $this->addPropertyDefinition($propertyDefinition);
        }
    }

    /**
     * @param PropertyDefinition $propertyDefinition
     */
    public function addPropertyDefinition(PropertyDefinition $propertyDefinition): void
    {
        $this->propertyDefinitions[] = $propertyDefinition;
    }

    /**
     * @return PropertyDefinition[]
     */
    public function getPropertyDefinitions(): array
    {
        return array_values($this->propertyDefinitions);
    }
}

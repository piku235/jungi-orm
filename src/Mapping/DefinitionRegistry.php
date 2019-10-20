<?php

namespace Jungi\Orm\Mapping;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class DefinitionRegistry
{
    /**
     * @var EntityDefinition[]
     */
    private $entityDefinitions = [];

    /**
     * @var EmbeddableDefinition[]
     */
    private $embeddableDefinitions = [];

    public function addEntityDefinition(EntityDefinition $definition): void
    {
        if (isset($this->entityDefinitions[$definition->getClassName()])) {
            throw new \RuntimeException(sprintf('Entity definition "%s" already exist.', $definition->getClassName()));
        }

        $this->entityDefinitions[$definition->getClassName()] = $definition;
    }

    public function hasEntityDefinition(string $className): bool
    {
        return isset($this->entityDefinitions[$className]);
    }

    public function getEntityDefinition(string $className): EntityDefinition
    {
        if (!isset($this->entityDefinitions[$className])) {
            throw new \RuntimeException(sprintf('Could not find entity definition "%s".', $className));
        }

        return $this->entityDefinitions[$className];
    }

    public function getEntityDefinitions(): array
    {
        return $this->entityDefinitions;
    }

    public function addEmbeddableDefinition(EmbeddableDefinition $definition): void
    {
        if (isset($this->embeddableDefinitions[$definition->getClassName()])) {
            throw new \RuntimeException(sprintf('Embeddable definition "%s" already exist.', $definition->getClassName()));
        }

        $this->embeddableDefinitions[$definition->getClassName()] = $definition;
    }

    public function hasEmbeddableDefinition(string $className): bool
    {
        return isset($this->embeddableDefinitions[$className]);
    }

    public function getEmbeddableDefinition(string $className): EmbeddableDefinition
    {
        if (!isset($this->embeddableDefinitions[$className])) {
            throw new \RuntimeException(sprintf('Could not find embeddable definition "%s".', $className));
        }

        return $this->embeddableDefinitions[$className];
    }

    public function getEmbeddableDefinitions(): array
    {
        return $this->embeddableDefinitions;
    }
}

<?php

namespace Jungi\Orm\Mapping;

use Doctrine\DBAL\Types\Type;
use Jungi\Orm\Mapping\Loader\LoaderInterface;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class ClassMetadataFactory
{
    private $loader;
    private $definitionRegistry;
    private $mappings;

    public function __construct(LoaderInterface $loader)
    {
        $this->loader = $loader;
        $this->definitionRegistry = new DefinitionRegistry();
        $this->mappings = [];
    }

    public function getEntityMetadata(string $type): Entity
    {
        if (isset($this->mappings[$type])) {
            return $this->mappings[$type];
        }

        return $this->mappings[$type] = $this->createEntity($this->getEntityMetadataDefinition($type));
    }

    private function getEntityMetadataDefinition(string $type): EntityDefinition
    {
        if ($this->definitionRegistry->hasEntityDefinition($type)) {
            return $this->definitionRegistry->getEntityDefinition($type);
        }

        $definition = $this->loader->load($type);
        if (!$definition instanceof EntityDefinition) {
            throw new \UnexpectedValueException(sprintf(
                'Expected to get "%s", got: "%s".',
                EntityDefinition::class,
                get_class($definition)
            ));
        }

        $this->definitionRegistry->addEntityDefinition($definition);

        return $definition;
    }

    private function getEmbeddableMetadataDefinition(string $type): EmbeddableDefinition
    {
        if ($this->definitionRegistry->hasEmbeddableDefinition($type)) {
            return $this->definitionRegistry->getEmbeddableDefinition($type);
        }

        $definition = $this->loader->load($type);
        if (!$definition instanceof EmbeddableDefinition) {
            throw new \UnexpectedValueException(sprintf(
                'Expected to get "%s", got: "%s".',
                EmbeddableDefinition::class,
                get_class($definition)
            ));
        }

        $this->definitionRegistry->addEmbeddableDefinition($definition);

        return $definition;
    }

    private function createEmbeddable(EmbeddableDefinition $definition, string $propertyPath): Embeddable
    {
        return new Embeddable($definition->getClassName(), $this->createProperties($definition, $propertyPath));
    }

    private function createEntity(EntityDefinition $definition): Entity
    {
        return new Entity(
            $definition->getClassName(),
            $definition->getTableName(),
            $definition->getIdPropertyName(),
            $this->createProperties($definition, '')
        );
    }

    private function createProperties(ClassDefinition $classDefinition, string $propertyPath): array
    {
        return array_map(function (PropertyDefinition $definition) use ($classDefinition, $propertyPath) {
            $propertyPath = $propertyPath.$definition->getName();
            $fieldDefinition = $definition->getFieldDefinition();

            switch (true) {
                case $fieldDefinition instanceof EmbeddedFieldDefinition:
                    $field = $this->createEmbeddedField($fieldDefinition, $propertyPath.'.');
                    break;
                case $fieldDefinition instanceof BasicFieldDefinition:
                    $field = $this->createBasicField($fieldDefinition);
                    break;
                default:
                    $field = $this->createField($fieldDefinition);
                    break;
            }

            return new Property(
                $definition->getName(),
                $propertyPath,
                $field
            );
        }, $classDefinition->getPropertyDefinitions());
    }

    private function createField(FieldDefinition $definition): Field
    {
        switch (true) {
            default:
                throw new \RuntimeException(sprintf(
                    'Property definition "%s" is not supported.',
                    get_class($definition)
                ));
            case $definition instanceof EmbeddedFieldDefinition:
                return $this->createEmbeddedField($definition);
            case $definition instanceof CollectionFieldDefinition:
                return $this->createCollectionField($definition);
            case $definition instanceof BasicFieldDefinition:
                return $this->createBasicField($definition);
        }
    }

    private function createCollectionField(CollectionFieldDefinition $definition): CollectionField
    {
        return new CollectionField(
            $definition->getTableName(),
            $definition->getJoinColumnName(),
            $this->createBasicField($definition->getKeyDefinition()),
            $this->createField($definition->getElementDefinition()),
            $definition->getVirtualColumnName(),
            $definition->isNullable()
        );
    }

    private function createEmbeddedField(EmbeddedFieldDefinition $definition, string $propertyPath = ''): EmbeddedField
    {
        $embeddableDefinition = $definition->getEmbeddableDefinition() ?: $this->getEmbeddableMetadataDefinition($definition->getEmbeddableClassName());
        $embeddableDefinition = $embeddableDefinition->applyColumnPrefix($definition->getColumnPrefix());

        if ($definition->isNullable()) {
            $embeddableDefinition = $embeddableDefinition->null();
        }

        return new EmbeddedField(
            $this->createEmbeddable($embeddableDefinition, $propertyPath),
            $definition->getNullColumnName()
        );
    }

    private function createBasicField(BasicFieldDefinition $definition): BasicField
    {
        return new BasicField(
            $definition->getColumnName(),
            Type::getType($definition->getType()),
            $definition->isNullable()
        );
    }
}

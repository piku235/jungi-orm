<?php

namespace Jungi\Orm\Mapping\Builder;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class FluentMappingBuilderContext
{
    public function buildEntity(string $className): EntityFluentBuilder
    {
        return (new EntityFluentBuilder())->className($className);
    }

    public function buildEmbeddable(string $className): EmbeddableFluentBuilder
    {
        return (new EmbeddableFluentBuilder())->className($className);
    }

    public function buildBasicField(): BasicFieldFluentBuilder
    {
        return new BasicFieldFluentBuilder();
    }

    public function buildEmbeddedField(): EmbeddedFieldFluentBuilder
    {
        return new EmbeddedFieldFluentBuilder();
    }

    public function buildCollectionField(): CollectionFieldFluentBuilder
    {
        return new CollectionFieldFluentBuilder();
    }
}

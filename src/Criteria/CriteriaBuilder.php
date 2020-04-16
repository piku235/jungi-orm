<?php

namespace Jungi\Orm\Criteria;

use Jungi\Orm\Mapping\Entity;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class CriteriaBuilder
{
    private $entityMetadata;

    public function __construct(Entity $entityMetadata)
    {
        $this->entityMetadata = $entityMetadata;
    }

    public function and($expression, ...$expressions): Composite
    {
        return Composite::and(...func_get_args());
    }

    public function or($expression, ...$expressions): Composite
    {
        return Composite::or(...func_get_args());
    }

    public function eq(string $propertyName, $value): Comparison
    {
        $this->assertPropertyForEquality($propertyName);

        return Comparison::eq($propertyName, $value);
    }

    public function neq(string $propertyName, $value): Comparison
    {
        $this->assertPropertyForEquality($propertyName);

        return Comparison::neq($propertyName, $value);
    }

    public function isNull(string $propertyName): Comparison
    {
        $this->assertPropertyForNullability($propertyName);

        return Comparison::isNull($propertyName);
    }

    public function isNotNull(string $propertyName): Comparison
    {
        $this->assertPropertyForNullability($propertyName);

        return Comparison::isNotNull($propertyName);
    }

    public function asc(string $propertyName): Order
    {
        $this->assertPropertyIsSortable($propertyName);

        return Order::asc($propertyName);
    }

    public function desc(string $propertyName): Order
    {
        $this->assertPropertyIsSortable($propertyName);

        return Order::desc($propertyName);
    }
    
    private function assertPropertyForEquality(string $propertyName): void
    {
        $property = $this->entityMetadata->getProperty($propertyName);
        if (!$property->isBasic()) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot use property "%s::%s" for equality, only basic properties are supported.',
                $this->entityMetadata->getClassName(),
                $property->getName()
            ));
        }
    }

    private function assertPropertyForNullability(string $propertyName): void
    {
        $property = $this->entityMetadata->getProperty($propertyName);
        if (!$property->isBasic() && (!$property->isEmbedded() || !$property->isNullable())) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot use property "%s::%s" for nullability, only basic properties, embeddables are supported.',
                $this->entityMetadata->getClassName(),
                $property->getName()
            ));
        }
    }

    private function assertPropertyIsSortable(string $propertyName): void
    {
        $property = $this->entityMetadata->getProperty($propertyName);
        if (!$property->isBasic()) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot sort by property "%s::%s", only basic properties are sortable.',
                $this->entityMetadata->getClassName(),
                $property->getName()
            ));
        }
    }
}

<?php

namespace Jungi\Orm\Mapping;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
abstract class Class_
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @var Property[]
     */
    protected $properties;

    /**
     * @var \ReflectionClass
     */
    protected $reflection;

    /**
     * @param string     $className
     * @param Property[] $properties
     */
    public function __construct(string $className, array $properties)
    {
        $this->className = $className;
        $this->reflection = new \ReflectionClass($className);
        $this->setProperties($properties);
    }

    /**
     * @example fooProp.barProp.zetProp
     * @example fooProp
     *
     * @param string $name
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public function isPropertyMapped(string $name): bool
    {
        try {
            return $this->traverseProperty($name, function () { return true; });
        } catch (PropertyNotFoundException $e) {
            return false;
        }
    }

    /**
     * @example fooProp.barProp.zetProp
     * @example fooProp
     *
     * @param string $name
     *
     * @return Property
     *
     * @throws \InvalidArgumentException
     * @throws PropertyNotFoundException
     */
    public function getProperty(string $name): Property
    {
        return $this->traverseProperty($name, function ($initialValue, Property $property) { return $property; });
    }

    /**
     * @example fooProp.barProp.zetProp
     * @example fooProp
     *
     * @param object $object
     * @param string $name
     * @param mixed  $value
     *
     * @throws \InvalidArgumentException
     * @throws PropertyNotFoundException
     */
    public function setPropertyValue(object $object, string $name, $value): void
    {
        /** @var Property $property */
        list ($property, $object, ) = $this->traverseProperty($name, function (array $tuple, Property $property) {
            list (, $prevOwningObject, $owningObject) = $tuple;

            return [$property, $prevOwningObject, $property->getValue($owningObject)];
        }, [null, $object, $object]);

        $property->setValue($object, $value);
    }

    /**
     *
     * @example fooProp.barProp.zetProp
     * @example fooProp
     *
     * @param object $object
     * @param string $name
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     * @throws PropertyNotFoundException
     */
    public function getPropertyValue(object $object, string $name)
    {
        return $this->traverseProperty($name, function (object $owningObject, Property $property) {
            return $property->getValue($owningObject);
        }, $object);
    }

    /**
     * @param string $fieldType
     *
     * @return bool
     *
     * @throws \InvalidArgumentException
     */
    public function hasPropertyOf(string $fieldType): bool
    {
        if (!is_subclass_of($fieldType, Field::class)) {
            throw new \InvalidArgumentException('Expected a type inheriting the base field type.');
        }

        foreach ($this->properties as $property) {
            if ($property->getField() instanceof $fieldType) {
                return true;
            }
        }

        return false;
    }

    /**
     * @example fooProp.barProp.zetProp
     * @example fooProp
     *
     * @param string   $name
     * @param callable $cb
     * @param mixed    $initialValue
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     * @throws PropertyNotFoundException
     */
    public function traverseProperty(string $name, callable $cb, $initialValue = null)
    {
        if (isset($this->properties[$name])) {
            return $cb($initialValue, $this->properties[$name]);
        }

        if (false !== strpos($name, '.')) { // composite type
            $parts = explode('.', $name, 2);
            $nameLeadingPart = $parts[0];
            $rest = $parts[1] ?? null;

            if (isset($this->properties[$nameLeadingPart])) {
                $property = $this->properties[$nameLeadingPart];
                $result = $cb($initialValue, $property);

                if ($rest) {
                    $field = $property->getField();
                    if (!$field instanceof EmbeddedField) {
                        throw new \InvalidArgumentException(sprintf(
                            'Requested a property "%s" of non embedded property "%s".',
                            $name,
                            $nameLeadingPart
                        ));
                    }

                    return $field->getEmbeddable()->traverseProperty($rest, $cb, $result);
                }

                return $result;
            }
        }

        throw new PropertyNotFoundException(sprintf('Property "%s" not found.', $name));
    }

    /**
     * @return string
     */
    public function getClassName(): string
    {
        return $this->className;
    }

    /**
     * @return Property[]
     */
    public function getProperties(): array
    {
        return array_values($this->properties);
    }

    /**
     * @return Property[]|\Iterator
     */
    public function getNestedProperties(): \Iterator
    {
        foreach ($this->properties as $propertyMetadata) {
            $fieldMetadata = $propertyMetadata->getField();

            switch (true) {
                case $fieldMetadata instanceof EmbeddedField:
                    yield from $fieldMetadata->getEmbeddable()->getNestedProperties();
                    break;
                default:
                    yield $propertyMetadata;
                    break;
            }
        }
    }

    /**
     * @param string $fieldType
     *
     * @return Property[]|\Iterator
     */
    public function getNestedPropertiesOf(string $fieldType): \Iterator
    {
        foreach ($this->properties as $propertyMetadata) {
            $fieldMetadata = $propertyMetadata->getField();

            switch (true) {
                case $fieldMetadata instanceof $fieldType:
                    yield $propertyMetadata;
                    break;
                case $fieldMetadata instanceof EmbeddedField:
                    yield from $fieldMetadata->getEmbeddable()->getNestedPropertiesOf($fieldType);
                    break;
            }
        }
    }

    /**
     * @return object
     */
    public function newInstance(): object
    {
        return $this->reflection->newInstanceWithoutConstructor();
    }

    /**
     * @param array $propertyValues
     *
     * @return object
     */
    public function populateNewInstance(array $propertyValues): object
    {
        $instance = $this->newInstance();
        $this->setPropertyValues($instance, $propertyValues);

        return $instance;
    }

    /**
     * @param object $object
     * @param array  $values
     */
    public function setPropertyValues(object $object, array $values): void
    {
        foreach ($values as $propertyName => $value) {
            if (!isset($this->properties[$propertyName])) {
                throw new \InvalidArgumentException(sprintf('Property "%s" is not mapped.', $propertyName));
            }

            $property = $this->properties[$propertyName];
            $property->setValue($object, $value);
        }
    }

    /**
     * @return \ReflectionClass
     */
    public function getReflection(): \ReflectionClass
    {
        return $this->reflection;
    }

    /**
     * @param Property[] $properties
     */
    protected function setProperties(array $properties): void
    {
        $this->properties = [];
        foreach ($properties as $property) {
            $property->setDeclaringClass($this);
            $this->properties[$property->getName()] = $property;
        }
    }
}

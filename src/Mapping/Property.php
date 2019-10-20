<?php

namespace Jungi\Orm\Mapping;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class Property
{
    private $declaringClass;
    private $name;
    private $path;
    private $field;
    private $reflection;

    public function __construct(string $name, string $path, Field $field)
    {
        $this->name = $name;
        $this->path = $path;
        $this->field = $field;
    }

    /** @internal */
    public function setDeclaringClass(Class_ $declaringClass): void
    {
        if ($this->declaringClass) {
            throw new \BadMethodCallException('Can be called only once.');
        }

        $this->declaringClass = $declaringClass;
        $this->reflection = $this->declaringClass->getReflection()->getProperty($this->name);
        $this->reflection->setAccessible(true);
    }

    public function isNullable(): bool
    {
        return $this->field->isNullable();
    }

    public function isBasic(): bool
    {
        return $this->field instanceof BasicField;
    }

    public function isCollection(): bool
    {
        return $this->field instanceof CollectionField;
    }

    public function isEmbedded(): bool
    {
        return $this->field instanceof EmbeddedField;
    }

    public function getDeclaringClass(): Class_
    {
        if (!$this->declaringClass) {
            throw new \BadMethodCallException('Can be called only when the declaring class has been set.');
        }

        return $this->declaringClass;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getField(): Field
    {
        return $this->field;
    }

    public function setValue(object $object, $value): void
    {
        $this->reflection->setValue($object, $value);
    }

    public function getValue(object $object)
    {
        return $this->reflection->getValue($object);
    }

    public function getReflection(): \ReflectionProperty
    {
        return $this->reflection;
    }
}

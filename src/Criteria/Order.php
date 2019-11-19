<?php

namespace Jungi\Orm\Criteria;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class Order
{
    public const ASC = 'ASC';
    public const DESC = 'DESC';

    private $direction;
    private $propertyName;

    public static function asc(string $propertyName): self
    {
        return new self(self::ASC, $propertyName);
    }

    public static function desc(string $propertyName): self
    {
        return new self(self::DESC, $propertyName);
    }

    private function __construct(string $direction, string $propertyName)
    {
        $this->direction = $direction;
        $this->propertyName = $propertyName;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }
}

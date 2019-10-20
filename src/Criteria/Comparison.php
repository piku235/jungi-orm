<?php

namespace Jungi\Orm\Criteria;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class Comparison implements ConditionalExpression
{
    public const EQUALS = '=';
    public const NOT_EQUALS = '!=';
    public const IS = 'IS';
    public const IS_NOT = 'IS NOT';

    private $operator;
    private $propertyName;
    private $comparedValue;

    public static function eq(string $propertyName, $comparedValue): self
    {
        return new self(self::EQUALS, $propertyName, $comparedValue);
    }

    public static function neq(string $propertyName, $comparedValue): self
    {
        return new self(self::NOT_EQUALS, $propertyName, $comparedValue);
    }

    public static function isNull(string $propertyName): self
    {
        return new self(self::IS, $propertyName, null);
    }

    public static function isNotNull(string $propertyName): self
    {
        return new self(self::IS_NOT, $propertyName, null);
    }

    private function __construct(string $operator, string $propertyName, $comparedValue)
    {
        $this->operator = $operator;
        $this->propertyName = $propertyName;
        $this->comparedValue = $comparedValue;
    }

    /**
     * @return self
     */
    public function negate(): ConditionalExpression
    {
        static $negateMap = array(
            self::EQUALS => self::NOT_EQUALS,
            self::IS => self::IS_NOT,
            self::NOT_EQUALS => self::EQUALS,
            self::IS_NOT => self::IS,
        );

        return new self($negateMap[$this->operator], $this->propertyName, $this->comparedValue);
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function getComparedValue()
    {
        return $this->comparedValue;
    }
}

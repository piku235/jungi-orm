<?php

namespace Jungi\Orm\Criteria;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class Composite implements ConditionalExpression
{
    public const AND = 'AND';
    public const OR = 'OR';

    private $negated;
    private $condition;
    private $expressions;

    public static function and(ConditionalExpression $expression, ConditionalExpression...$expressions)
    {
        return new self(self::AND, func_get_args());
    }

    public static function or(ConditionalExpression $expression, ConditionalExpression...$expressions)
    {
        return new self(self::OR, func_get_args());
    }

    private function __construct(string $condition, array $expressions, ?self $negated = null)
    {
        $this->condition = $condition;
        $this->expressions = $expressions;
        $this->negated = $negated;
    }

    /**
     * @return self
     */
    public function negate(): ConditionalExpression
    {
        static $negateMap = [
            self::AND => self::OR,
            self::OR => self::AND,
        ];

        if (null !== $this->negated) {
            return $this->negated;
        }

        $negatedExpressions = array_map(function (ConditionalExpression $expression) {
            return $expression->negate();
        }, $this->expressions);

        return $this->negated = new self($negateMap[$this->condition], $negatedExpressions, $this);
    }

    public function getCondition(): string
    {
        return $this->condition;
    }

    public function getExpressions(): array
    {
        return $this->expressions;
    }
}

<?php

namespace Jungi\Orm\Criteria;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Jungi\Orm\QueryMapping;
use Jungi\Orm\Mapping\BasicField;
use Jungi\Orm\Mapping\Entity;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class DbalConditionalExpressionVisitor
{
    private $qb;
    private $entityMetadata;
    private $queryMapping;

    public function __construct(QueryBuilder $qb, Entity $entityMetadata, QueryMapping $queryMapping)
    {
        $this->qb = $qb;
        $this->entityMetadata = $entityMetadata;
        $this->queryMapping = $queryMapping;
    }

    public function visit(ConditionalExpression $expression): void
    {
        $this->qb->andWhere($this->visitConditionalExpression($expression));
    }

    private function visitConditionalExpression(ConditionalExpression $expression): string
    {
        switch (true) {
            case $expression instanceof Comparison:
                return $this->visitComparison($expression);
            case $expression instanceof Composite:
                return $this->visitComposite($expression);
            default:
                throw new \RuntimeException(sprintf('Unknown expression "%s".', get_class($expression)));
        }
    }

    private function visitComparison(Comparison $expression): string
    {
        static $comparisonOperatorMap = array(
            Comparison::EQUALS => ExpressionBuilder::EQ,
            Comparison::NOT_EQUALS => ExpressionBuilder::NEQ,
        );

        /** @var BasicField $fieldMetadata */
        $fieldMetadata = $this->entityMetadata->getProperty($expression->getPropertyName())->getField();
        $columnName = $this->queryMapping->getColumn($this->entityMetadata->getTableName(), $fieldMetadata->getColumnName())->getQualifiedName();

        if (isset($comparisonOperatorMap[$expression->getOperator()])) {
            $comparedValue = $this->qb->createPositionalParameter(
                $expression->getComparedValue(),
                $fieldMetadata->getType()->getBindingType()
            );

            return $this->qb->expr()->comparison($columnName, $comparisonOperatorMap[$expression->getOperator()], $comparedValue);
        }

        switch ($expression->getOperator()) {
            case Comparison::IS:
                return $this->qb->expr()->isNull($columnName);
            case Comparison::IS_NOT:
                return $this->qb->expr()->isNotNull($columnName);
        }

        throw new \RuntimeException(sprintf('Unknown comparision operator "%s".', $expression->getOperator()));
    }

    private function visitComposite(Composite $expression): string
    {
        $dbalComposite = null;

        switch ($expression->getCondition()) {
            case Composite::AND:
                $dbalComposite = $this->qb->expr()->andX();
                break;
            case Composite::OR:
                $dbalComposite = $this->qb->expr()->orX();
                break;
            default:
                throw new \RuntimeException(sprintf('Unknown composite condition "%s".', $expression->getCondition()));
        }

        foreach ($expression->getExpressions() as $expression) {
            $dbalComposite->add($this->visitConditionalExpression($expression));
        }

        return $dbalComposite;
    }
}

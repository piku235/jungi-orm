<?php

namespace Jungi\Orm\Criteria;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Jungi\Orm\Mapping\EmbeddedField;
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
        $fieldMetadata = $this->entityMetadata->getProperty($expression->getPropertyName())->getField();

        switch ($expression->getOperator()) {
            case Comparison::EQUALS:
            case Comparison::NOT_EQUALS:
                if (!$fieldMetadata instanceof BasicField) {
                    throw new \RuntimeException(sprintf('Expected to get basic field for comparison, got: "%s".', get_class($fieldMetadata)));
                }

                $columnName = $this->queryMapping->getColumn(
                    $this->entityMetadata->getTableName(),
                    $fieldMetadata->getColumnName()
                )->getQualifiedName();
                $comparedValue = $this->qb->createPositionalParameter(
                    $expression->getComparedValue(),
                    $fieldMetadata->getType()->getBindingType()
                );

                return $this->qb->expr()->comparison($columnName, $expression->getOperator(), $comparedValue);
            case Comparison::IS:
            case Comparison::IS_NOT:
                if (null !== $expression->getComparedValue()) {
                    throw new \RuntimeException('Only IS [NOT] NULL comparisons are supported.');
                }

                if ($fieldMetadata instanceof EmbeddedField) {
                    if (!$fieldMetadata->isNullable()) {
                        throw new \LogicException('Embedded field should be nullable for IS [NOT] NULL comparison.');
                    }

                    $columnName = $this->queryMapping->getColumn(
                        $this->entityMetadata->getTableName(),
                        $fieldMetadata->getNullField()->getColumnName()
                    )->getQualifiedName();
                    $comparedValue = $this->qb->createPositionalParameter(
                        Comparison::IS === $expression->getOperator(),
                        ParameterType::BOOLEAN
                    );

                    return $this->qb->expr()->eq($columnName, $comparedValue);
                }

                if (!$fieldMetadata instanceof BasicField) {
                    throw new \RuntimeException(sprintf('Expected to get basic field for IS [NOT] comparison, got: "%s".', get_class($fieldMetadata)));
                }

                $columnName = $this->queryMapping->getColumn(
                    $this->entityMetadata->getTableName(),
                    $fieldMetadata->getColumnName()
                )->getQualifiedName();

                return $this->qb->expr()->comparison($columnName, $expression->getOperator(), 'NULL');
            default:
                throw new \RuntimeException(sprintf('Unknown comparision operator "%s".', $expression->getOperator()));
        }
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

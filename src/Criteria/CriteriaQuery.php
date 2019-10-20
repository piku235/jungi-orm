<?php

namespace Jungi\Orm\Criteria;

use Jungi\Orm\InternalEntityManager;
use Jungi\Orm\Mapping\Entity;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class CriteriaQuery
{
    /**
     * @var Entity
     */
    private $queriedEntity;

    /**
     * @var InternalEntityManager
     */
    private $em;

    /**
     * @var ConditionalExpression
     */
    private $conditionalExpression;

    /**
     * @var CriteriaBuilder
     */
    private $builder;

    /**
     * @var int|null
     */
    private $firstResult;

    /**
     * @var int|null
     */
    private $maxResults;

    /**
     * @var Order[]
     */
    private $orderings;

    public function __construct(Entity $from, InternalEntityManager $em)
    {
        $this->queriedEntity = $from;
        $this->em = $em;
        $this->builder = new CriteriaBuilder($this->queriedEntity);
        $this->orderings = [];
    }

    public function builder(): CriteriaBuilder
    {
        return $this->builder;
    }

    public function where(ConditionalExpression $expression, ConditionalExpression...$expressions): self
    {
        if ($expressions) {
            $expression = $this->builder->and(...func_get_args());
        }

        $this->conditionalExpression = $expression;

        return $this;
    }

    public function orderBy(Order $order, Order...$orderings): self
    {
        $this->orderings = func_get_args();

        return $this;
    }

    public function firstResult(?int $firstResult): self
    {
        if (null !== $firstResult && $firstResult < 0) {
            throw new \OutOfBoundsException('The first result should not be lower than 0');
        }

        $this->firstResult = $firstResult;

        return $this;
    }

    public function maxResults(?int $maxResults): self
    {
        if (null !== $maxResults && $maxResults < 1) {
            throw new \OutOfBoundsException('The max results should not be greater than 0');
        }

        $this->maxResults = $maxResults;

        return $this;
    }

    public function getQueriedEntity(): Entity
    {
        return $this->queriedEntity;
    }

    public function getConditionalExpression(): ?ConditionalExpression
    {
        return $this->conditionalExpression;
    }

    public function getFirstResult(): ?int
    {
        return $this->firstResult;
    }

    public function getMaxResults(): ?int
    {
        return $this->maxResults;
    }

    /**
     * @return Order[]
     */
    public function getOrderings(): array
    {
        return $this->orderings;
    }

    public function getSingleResult(): ?object
    {
        $it = $this->em->executeCriteriaQuery($this);
        $entity = $it->valid() ? $it->current() : null;

        $it->next();
        if ($it->valid()) {
            throw new \InvalidArgumentException('Expected to get one result, more have been returned.');
        }

        return $entity;
    }

    public function getResult(): \Iterator
    {
        return $this->em->executeCriteriaQuery($this);
    }
}

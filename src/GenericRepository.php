<?php

namespace Jungi\Orm;

use Jungi\Orm\Criteria\CriteriaQuery;

/**
 * A generic repository, not intended to be used publicly.
 *
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class GenericRepository
{
    private $type;
    private $em;

    public function __construct(string $type, InternalEntityManager $em)
    {
        $this->type = $type;
        $this->em = $em;
    }

    public function createCriteriaQuery(): CriteriaQuery
    {
        return $this->em->createCriteriaQuery($this->type);
    }

    public function find($id): ?object
    {
        return $this->em->find($this->type, $id);
    }

    public function save(object $object): void
    {
        $this->assertObjectIsSupported($object);

        $this->em->save($object);
    }

    public function remove(object $object): void
    {
        $this->assertObjectIsSupported($object);

        $this->em->remove($object);
    }

    public function commit(): void
    {
        $this->em->commit();
    }

    private function assertObjectIsSupported(object $object): void
    {
        if (!$object instanceof $this->type) {
            throw new \InvalidArgumentException(sprintf('Objects of "%s" are not supported.', get_class($object)));
        }
    }
}

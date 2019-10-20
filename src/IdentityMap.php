<?php

namespace Jungi\Orm;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class IdentityMap
{
    private $classMap = [];

    public function get(string $type, $id): ?object
    {
        return $this->classMap[$type][$id] ?? null;
    }

    public function put($id, object $object): object
    {
        $type = get_class($object);

        if (!isset($this->classMap[$type])) {
            $this->classMap[$type] = array();
        }

        $this->classMap[$type][$id] = $object;

        return $object;
    }

    public function contains(string $type, $id): bool
    {
        return isset($this->classMap[$type][$id]);
    }

    public function containsObject(object $object): bool
    {
        $identityMap = $this->classMap[get_class($object)] ?? null;

        return $identityMap && false !== array_search($object, $identityMap, true);
    }

    public function remove(string $type, $id): void
    {
        unset($this->classMap[$type][$id]);
    }
}

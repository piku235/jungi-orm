<?php

namespace Jungi\Orm;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
interface EntityResultSetInterface
{
    public function next(): bool;
    public function valid(): bool;
    public function readEntity(): ?object;
    public function readEntityId();
    public function getEntityClass(): string;
}

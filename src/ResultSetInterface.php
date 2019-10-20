<?php

namespace Jungi\Orm;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
interface ResultSetInterface
{
    /**
     * @return mixed False on no more rows
     */
    public function next();

    /**
     * @return mixed False on no more rows
     */
    public function current();
}

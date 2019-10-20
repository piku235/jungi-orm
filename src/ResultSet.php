<?php

namespace Jungi\Orm;

use Doctrine\DBAL\Driver\ResultStatement;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class ResultSet implements ResultSetInterface
{
    private $stmt;
    private $current;

    public function __construct(ResultStatement $stmt)
    {
        $this->stmt = $stmt;
    }

    public function current()
    {
        return $this->current;
    }

    public function next()
    {
        return $this->current = $this->stmt->fetch();
    }
}

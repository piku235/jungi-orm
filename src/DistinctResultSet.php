<?php

namespace Jungi\Orm;

use Doctrine\DBAL\Driver\ResultStatement;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class DistinctResultSet implements ResultSetInterface
{
    private $stmt;
    private $previousRow;
    private $currentRow;
    private $nextRow;
    private $distinctColumnName;

    public function __construct(ResultStatement $stmt, string $distinctColumnName)
    {
        $this->stmt = $stmt;
        $this->distinctColumnName = $distinctColumnName;
    }

    public function current()
    {
        return $this->currentRow;
    }

    public function next()
    {
        do {
            $nextRow = $this->nextRow();
        } while ($nextRow && !$this->hasReachedNext());

        return $nextRow;
    }

    public function untilNext()
    {
        $nextRow = $this->nextRow();

        if ($this->hasReachedNext()) {
            $this->previousRow();
            return false;
        }

        return $nextRow;
    }

    private function hasReachedNext(): bool
    {
        if (null === $this->previousRow) {
            return true;
        }

        return $this->previousRow && $this->currentRow && $this->currentRow[$this->distinctColumnName] !== $this->previousRow[$this->distinctColumnName];
    }

    private function nextRow()
    {
        $this->previousRow = $this->currentRow;

        if (null !== $this->nextRow) {
            $this->currentRow = $this->nextRow;
            $this->nextRow = null;
        } else {
            $this->currentRow = $this->stmt->fetch();
        }

        return $this->currentRow;
    }

    private function previousRow()
    {
        if (null === $this->previousRow) {
            throw new \LogicException('No previous value.');
        }

        $this->nextRow = $this->currentRow;
        $this->currentRow = $this->previousRow;
        $this->previousRow = null;

        return $this->currentRow;
    }
}

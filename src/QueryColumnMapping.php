<?php

namespace Jungi\Orm;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class QueryColumnMapping
{
    private $qualifiedName;
    private $resultName;

    public function __construct(string $qualifiedName, string $resultName)
    {
        $this->qualifiedName = $qualifiedName;
        $this->resultName = $resultName;
    }

    public function getQualifiedName(): string
    {
        return $this->qualifiedName;
    }

    public function getResultName(): string
    {
        return $this->resultName;
    }
}

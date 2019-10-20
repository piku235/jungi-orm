<?php

namespace Jungi\Orm;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class QueryMapping
{
    private $tableAliasMap;
    private $tableColumnMap;

    /**
     * @param array                  $tableAliasMap
     * @param QueryColumnMapping[][] $tableColumnMap
     */
    public function __construct(array $tableAliasMap, array $tableColumnMap)
    {
        $this->tableAliasMap = $tableAliasMap;
        $this->tableColumnMap = $tableColumnMap;
    }

    /** @return QueryColumnMapping[] */
    public function getColumns(): array
    {
        $columns = [];
        foreach ($this->tableColumnMap as $tableColumns) {
            $columns = array_merge($columns, $tableColumns);
        }

        return $columns;
    }

    public function getColumn(string $tableName, string $columnName): QueryColumnMapping
    {
        if (!isset($this->tableColumnMap[$tableName][$columnName])) {
            throw new \InvalidArgumentException(sprintf('Column "%s" of table "%s" is not mapped.', $columnName, $tableName));
        }

        return $this->tableColumnMap[$tableName][$columnName];
    }

    public function getTableAlias(string $tableName): string
    {
        if (!isset($this->tableAliasMap[$tableName])) {
            throw new \InvalidArgumentException(sprintf('No alias for table "%s".', $tableName));
        }

        return $this->tableAliasMap[$tableName];
    }
}

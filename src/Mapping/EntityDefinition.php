<?php

namespace Jungi\Orm\Mapping;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class EntityDefinition extends ClassDefinition
{
    /**
     * @var string|null
     */
    private $tableName;

    /**
     * @var string|null
     */
    private $idPropertyName;

    /**
     * @return string|null
     */
    public function getTableName(): ?string
    {
        return $this->tableName;
    }

    /**
     * @param string|null $tableName
     */
    public function setTableName(?string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * @return string|null
     */
    public function getIdPropertyName(): ?string
    {
        return $this->idPropertyName;
    }

    /**
     * @param string|null $idPropertyName
     */
    public function setIdPropertyName(?string $idPropertyName): void
    {
        $this->idPropertyName = $idPropertyName;
    }
}

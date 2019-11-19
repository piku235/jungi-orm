<?php

namespace Jungi\Orm;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\Query\QueryBuilder as DbalQueryBuilder;
use Jungi\Orm\Criteria\CriteriaQuery;
use Jungi\Orm\Criteria\DbalConditionalExpressionVisitor;
use Jungi\Orm\Criteria\Order;
use Jungi\Orm\Mapping\BasicField;
use Jungi\Orm\Mapping\Class_;
use Jungi\Orm\Mapping\ClassMetadataFactory;
use Jungi\Orm\Mapping\CollectionField;
use Jungi\Orm\Mapping\EmbeddedField;
use Jungi\Orm\Mapping\Entity;
use Jungi\Orm\Mapping\Field;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
final class CriteriaQueryExecutor implements CriteriaQueryExecutorInterface
{
    private $connection;
    private $classMetadataFactory;
    private $queryMappings;

    public function __construct(Connection $connection, ClassMetadataFactory $classMetadataFactory)
    {
        $this->connection = $connection;
        $this->classMetadataFactory = $classMetadataFactory;
        $this->queryMappings = array();
    }

    /**
     * {@inheritdoc}
     */
    public function executeCriteriaQuery(CriteriaQuery $query): EntityResultSetInterface
    {
        $entityMetadata = $query->getQueriedEntity();
        $queryMapping = $this->getEntityQueryMapping($entityMetadata->getClassName());
        $idColumnName = $this->getEntityIdColumnMapping($entityMetadata, $queryMapping)->getResultName();
        $collectionProperties = $entityMetadata->getNestedPropertiesOf(CollectionField::class);

        if ($collectionProperties->valid()) {
            $stmt = $this->executeQueryWithCollections($query, $collectionProperties);
        } else {
            $stmt = $this->executeQueryFromEntityOnly($query);
        }

        $resultSet = new DistinctResultSet($stmt, $idColumnName);
        $resultSetMapper = new DistinctResultSetMapper($this->connection, $entityMetadata, $queryMapping);

        return new DistinctEntityResultSet($entityMetadata->getClassName(), $resultSet, $resultSetMapper);
    }

    private function executeQueryFromEntityOnly(CriteriaQuery $query): ResultStatement
    {
        $entityMetadata = $query->getQueriedEntity();
        $queryMapping = $this->getEntityQueryMapping($entityMetadata->getClassName());
        $tableAlias = $queryMapping->getTableAlias($entityMetadata->getTableName());

        $qb = $this->connection->createQueryBuilder();
        $qb->from($entityMetadata->getTableName(), $tableAlias);

        $this->applySelectColumns($queryMapping, $qb);
        $this->applyOrderings($qb, $query, $queryMapping);

        if ($query->getConditionalExpression()) {
            $conditionVisitor = new DbalConditionalExpressionVisitor($qb, $entityMetadata, $queryMapping);
            $conditionVisitor->visit($query->getConditionalExpression());
        }

        if (null !== $query->getFirstResult()) {
            $qb->setFirstResult($query->getFirstResult());
        }
        if (null !== $query->getMaxResults()) {
            $qb->setMaxResults($query->getMaxResults());
        }

        return $qb->execute();
    }

    private function executeQueryWithCollections(CriteriaQuery $query, iterable $collectionProperties): ResultStatement
    {
        $entityMetadata = $query->getQueriedEntity();
        $queryMapping = $this->getEntityQueryMapping($entityMetadata->getClassName());
        $tableAlias = $queryMapping->getTableAlias($entityMetadata->getTableName());
        $idColumnName = $this->getEntityIdColumnMapping($entityMetadata, $queryMapping)->getQualifiedName();

        $qb = $this->connection->createQueryBuilder();
        $this->applySelectColumns($queryMapping, $qb);

        // FROM (SELECT * FROM foo [...])
        if (null !== $query->getFirstResult() || null !== $query->getMaxResults()) {
            $sqb = $this->connection->createQueryBuilder();
            $sqb
                ->select('*')
                ->from($entityMetadata->getTableName(), $tableAlias);

            $this->applyOrderings($sqb, $query, $queryMapping);

            // VERY IMPORTANT
            // without that the DistinctResultSetMapper will not work correctly
            $sqb->addOrderBy($idColumnName, Order::ASC);

            if (null !== $query->getFirstResult()) {
                $sqb->setFirstResult($query->getFirstResult());
            }
            if (null !== $query->getMaxResults()) {
                $sqb->setMaxResults($query->getMaxResults());
            }

            $qb->from('('.$sqb->getSQL().')', $tableAlias);
        } else { // FROM foo [...]
            $qb->from($entityMetadata->getTableName(), $tableAlias);

            $this->applyOrderings($qb, $query, $queryMapping);

            // VERY IMPORTANT
            // without that the DistinctResultSetMapper will not work correctly
            $qb->addOrderBy($idColumnName, Order::ASC);
        }

        foreach ($collectionProperties as $collectionMetadata) {
            /** @var CollectionField $collectionFieldMetadata */
            $collectionFieldMetadata = $collectionMetadata->getField();
            $joinColumnName = $queryMapping->getColumn($collectionFieldMetadata->getTableName(), $collectionFieldMetadata->getJoinColumnName())->getQualifiedName();

            $qb->leftJoin(
                $tableAlias,
                $collectionFieldMetadata->getTableName(),
                $queryMapping->getTableAlias($collectionFieldMetadata->getTableName()),
                $qb->expr()->eq($idColumnName, $joinColumnName)
            );
        }

        if ($query->getConditionalExpression()) {
            $conditionVisitor = new DbalConditionalExpressionVisitor($qb, $entityMetadata, $queryMapping);
            $conditionVisitor->visit($query->getConditionalExpression());
        }

        return $qb->execute();
    }

    private function applySelectColumns(QueryMapping $queryMapping, DbalQueryBuilder $qb): void
    {
        foreach ($queryMapping->getColumns() as $column) {
            $qb->addSelect(sprintf('%s AS %s', $column->getQualifiedName(), $column->getResultName()));
        }
    }

    private function applyOrderings(DbalQueryBuilder $qb, CriteriaQuery $query, QueryMapping $queryMapping): void
    {
        $entityMetadata = $query->getQueriedEntity();

        foreach ($query->getOrderings() as $order) {
            $property = $entityMetadata->getProperty($order->getPropertyName());
            $columnName = $queryMapping->getColumn($entityMetadata->getTableName(), $property->getField()->getColumnName())->getQualifiedName();

            $qb->addOrderBy($columnName, $order->getDirection());
        }
    }

    private function getEntityIdColumnMapping(Entity $entityMetadata, QueryMapping $queryMapping): QueryColumnMapping
    {
        return $queryMapping->getColumn($entityMetadata->getTableName(), $entityMetadata->getIdProperty()->getField()->getColumnName());
    }

    private function getEntityQueryMapping(string $type): QueryMapping
    {
        if (isset($this->queryMappings[$type])) {
            return $this->queryMappings[$type];
        }

        $entityMetadata = $this->classMetadataFactory->getEntityMetadata($type);
        $tableAliasMap = $this->generateTableAliasMap($entityMetadata);

        $tableColumnMap = array(
            $entityMetadata->getTableName() => array()
        );
        $this->mapClassColumns($tableColumnMap, $entityMetadata, $entityMetadata, $entityMetadata->getTableName(), $tableAliasMap);

        return $this->queryMappings[$type] = new QueryMapping($tableAliasMap, $tableColumnMap);
    }

    private function generateTableAliasMap(Entity $entityMetadata): array
    {
        $tableAliasMap = [];
        $indexes = [];

        $tableNames = [$entityMetadata->getTableName()];

        $properties = $entityMetadata->getNestedPropertiesOf(CollectionField::class);
        foreach ($properties as $property) {
            $tableNames[] = $property->getField()->getTableName();
        }

        foreach ($tableNames as $tableName) {
            $alias = $tableName[0];

            if (!isset($indexes[$alias])) {
                $indexes[$alias] = 0;
            }

            $alias .= $indexes[$alias]++;

            $tableAliasMap[$tableName] = $alias;
        }

        return $tableAliasMap;
    }

    private function mapClassColumns(array &$tableColumnMap, Class_ $classMetadata, Entity $entity, string $tableName, array $tableAliasMap): void
    {
        $entityIdFieldMetadata = $entity->getIdProperty()->getField();

        foreach ($classMetadata->getProperties() as $propertyMetadata) {
            $fieldMetadata = $propertyMetadata->getField();

            switch (true) {
                case $fieldMetadata instanceof CollectionField:
                    $tableColumnMap[$fieldMetadata->getTableName()] = array();
                    $joinField = new BasicField($fieldMetadata->getJoinColumnName(), $entityIdFieldMetadata->getType());

                    $this->mapFieldColumns($tableColumnMap, $joinField, $entity, $fieldMetadata->getTableName(), $tableAliasMap);
                    $this->mapFieldColumns($tableColumnMap, $fieldMetadata->getKey(), $entity, $fieldMetadata->getTableName(), $tableAliasMap);
                    $this->mapFieldColumns($tableColumnMap, $fieldMetadata->getElement(), $entity, $fieldMetadata->getTableName(), $tableAliasMap);
                    break;
                default:
                    $this->mapFieldColumns($tableColumnMap, $fieldMetadata, $entity, $tableName, $tableAliasMap);
                    break;
            }
        }
    }

    private function mapFieldColumns(array &$tableColumnMap, Field $fieldMetadata, Entity $entity, string $tableName, array $tableAliasMap): void
    {
        $tableAlias = $tableAliasMap[$tableName];

        switch (true) {
            case $fieldMetadata instanceof EmbeddedField:
                if (null !== $nullField = $fieldMetadata->getNullField()) {
                    $this->mapFieldColumns($tableColumnMap, $nullField, $entity, $tableName, $tableAliasMap);
                }

                $this->mapClassColumns($tableColumnMap, $fieldMetadata->getEmbeddable(), $entity, $tableName, $tableAliasMap);
                break;
            case $fieldMetadata instanceof BasicField:
                $tableColumnMap[$tableName][$fieldMetadata->getColumnName()] = $this->getColumnFor($tableAlias, $fieldMetadata->getColumnName());
                break;
        }
    }

    private function getColumnFor(string $tableAlias, string $columnName): QueryColumnMapping
    {
        return new QueryColumnMapping($tableAlias.'.'.$columnName, $tableAlias.'_'.$columnName);
    }
}

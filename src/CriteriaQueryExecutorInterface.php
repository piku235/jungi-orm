<?php

namespace Jungi\Orm;

use Jungi\Orm\Criteria\CriteriaQuery;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
interface CriteriaQueryExecutorInterface
{
    /**
     * @param CriteriaQuery $query
     *
     * @return EntityResultSetInterface
     */
    public function executeCriteriaQuery(CriteriaQuery $query): EntityResultSetInterface;
}

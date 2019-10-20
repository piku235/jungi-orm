<?php

namespace Jungi\Orm\Criteria;

/**
 * @author Piotr Kugla <piku235@gmail.com>
 */
interface ConditionalExpression
{
    public function negate(): ConditionalExpression;
}

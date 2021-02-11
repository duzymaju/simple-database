<?php

namespace SimpleDatabase\Client\MySql\Condition;

use SimpleDatabase\Client\Condition\ConditionGroupInterface;
use SimpleStructure\Tool\ArrayObject;

/**
 * Or condition
 */
class OrCondition extends ArrayObject implements ConditionGroupInterface
{
    /**
     * To query
     *
     * @return string
     */
    public function toQuery()
    {
        return sprintf('(%s)', implode(' || ', array_map(function ($condition) {
            return $condition instanceof ConditionGroupInterface ? $condition->toQuery() : $condition;
        }, $this->getArrayCopy())));
    }
}

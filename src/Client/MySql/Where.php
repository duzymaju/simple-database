<?php

namespace SimpleDatabase\Client\MySql;

use SimpleDatabase\Client\Condition\ConditionGroupInterface;
use SimpleDatabase\Client\MySql\Condition\AndCondition;
use SimpleDatabase\Client\WhereInterface;

/**
 * Where
 */
class Where implements WhereInterface
{
    /** @var ConditionGroupInterface */
    private $where;

    /**
     * Construct
     *
     * @param ConditionGroupInterface|string[] $where where
     */
    public function __construct($where)
    {
        $this->where = $where instanceof ConditionGroupInterface ? $where : new AndCondition((array) $where);
    }

    /**
     * To string
     *
     * @return string
     */
    public function toString()
    {
        if ($this->where->count() === 0) {
            return '';
        }

        $statement = ' WHERE ' . $this->where->toQuery();

        return $statement;
    }
}

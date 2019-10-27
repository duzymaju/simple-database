<?php

namespace SimpleDatabase\Client;

use SimpleDatabase\Client\Condition\ConditionGroupInterface;
use SimpleDatabase\Tool\ToStringInterface;

/**
 * Where interface
 */
interface WhereInterface extends ToStringInterface
{
    /**
     * Construct
     *
     * @param ConditionGroupInterface|string[] $where where
     */
    public function __construct($where);
}

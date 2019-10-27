<?php

namespace SimpleDatabase\Client\Condition;

use Countable;

/**
 * Condition group interface
 */
interface ConditionGroupInterface extends Countable
{
    /**
     * To query
     *
     * @return string
     */
    public function toQuery();
}

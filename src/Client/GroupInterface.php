<?php

namespace SimpleDatabase\Client;

use SimpleDatabase\Tool\ToStringInterface;

/**
 * Group interface
 */
interface GroupInterface extends ToStringInterface
{
    /**
     * Construct
     *
     * @param string[] $columns columns
     * @param string[] $having  having
     */
    public function __construct(array $columns, array $having = []);
}

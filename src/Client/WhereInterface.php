<?php

namespace SimpleDatabase\Client;

use SimpleDatabase\Tool\ToStringInterface;

/**
 * Where interface
 */
interface WhereInterface extends ToStringInterface
{
    /**
     * Construct
     *
     * @param string[] $where where
     */
    public function __construct(array $where);
}

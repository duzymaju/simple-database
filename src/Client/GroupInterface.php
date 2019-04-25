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
     * @param array $columns columns
     */
    public function __construct(array $columns);
}

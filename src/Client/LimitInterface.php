<?php

namespace SimpleDatabase\Client;

use SimpleDatabase\Tool\ToStringInterface;

/**
 * Limit interface
 */
interface LimitInterface extends ToStringInterface
{
    /**
     * Construct
     *
     * @param int      $limit  limit
     * @param int|null $offset offset
     */
    public function __construct($limit, $offset = null);
}

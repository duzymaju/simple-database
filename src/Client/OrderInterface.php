<?php

namespace SimpleDatabase\Client;

use SimpleDatabase\Tool\ToStringInterface;

/**
 * Order interface
 */
interface OrderInterface extends ToStringInterface
{
    /**
     * Construct
     *
     * @param string[] $columns columns
     */
    public function __construct(array $columns);
}

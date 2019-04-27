<?php

namespace SimpleDatabase\Client;

use SimpleDatabase\Tool\ToStringInterface;

/**
 * Set interface
 */
interface SetInterface extends ToStringInterface
{
    /**
     * Construct
     *
     * @param string[] $set set
     */
    public function __construct(array $set);
}

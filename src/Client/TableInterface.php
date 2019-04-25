<?php

namespace SimpleDatabase\Client;

use SimpleDatabase\Tool\ToStringInterface;

/**
 * Table interface
 */
interface TableInterface extends ToStringInterface
{
    /** @const int */
    const TYPE_MAIN = 1;

    /** @const int */
    const TYPE_JOIN = 2;

    /** @const int */
    const TYPE_LEFT_JOIN = 4;

    /** @const int */
    const TYPE_RIGHT_JOIN = 8;

    /** @const int */
    const TYPE_OUTER_JOIN = 16;

    /**
     * Construct
     *
     * @param int               $type      type
     * @param string            $tableName table name
     * @param string|null       $tableSlug table slug
     * @param array|string|null $condition condition
     */
    public function __construct($type, $tableName, $tableSlug = null, $condition = null);
}

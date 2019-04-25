<?php

namespace SimpleDatabase\Client;

use SimpleDatabase\Tool\ToStringInterface;

/**
 * Command interface
 */
interface CommandInterface extends ToStringInterface
{
    /** @var int */
    const TYPE_SELECT = 1;

    /** @var int */
    const TYPE_INSERT = 2;

    /** @var int */
    const TYPE_UPDATE = 4;

    /** @var int */
    const TYPE_DELETE = 8;

    /**
     * Construct
     *
     * @param int        $type  type
     * @param array|null $items items
     */
    public function __construct($type, array $items = null);
}

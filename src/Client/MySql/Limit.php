<?php

namespace SimpleDatabase\Client\MySql;

use SimpleDatabase\Client\LimitInterface;

/**
 * Limit
 */
class Limit implements LimitInterface
{
    /** @var int[] */
    private $limit;

    /**
     * Construct
     *
     * @param int      $limit  limit
     * @param int|null $offset offset
     */
    public function __construct($limit, $offset = null)
    {
        $this->limit = [$limit];
        if (isset($offset)) {
            array_unshift($this->limit, $offset);
        }
    }

    /**
     * To string
     *
     * @return string
     */
    public function toString()
    {
        if (count($this->limit) === 0) {
            return '';
        }

        $statement = ' LIMIT ' . implode(', ', $this->limit);

        return $statement;
    }
}

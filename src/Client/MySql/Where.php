<?php

namespace SimpleDatabase\Client\MySql;

use SimpleDatabase\Client\WhereInterface;

/**
 * Where
 */
class Where implements WhereInterface
{
    /** @var string */
    private $where;

    /**
     * Construct
     *
     * @param string|array $where where
     */
    public function __construct($where)
    {
        $this->where = is_array($where) ? implode(' && ', $where) : $where;
    }

    /**
     * To string
     *
     * @return string
     */
    public function toString()
    {
        $statement = ' WHERE ' . $this->where;

        return $statement;
    }
}

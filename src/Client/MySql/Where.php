<?php

namespace SimpleDatabase\Client\MySql;

use SimpleDatabase\Client\WhereInterface;

/**
 * Where
 */
class Where implements WhereInterface
{
    /** @var string[] */
    private $where;

    /**
     * Construct
     *
     * @param string[] $where where
     */
    public function __construct(array $where)
    {
        $this->where = $where;
    }

    /**
     * To string
     *
     * @return string
     */
    public function toString()
    {
        if (count($this->where) === 0) {
            return '';
        }

        $statement = ' WHERE ' . implode(' && ', $this->where);

        return $statement;
    }
}
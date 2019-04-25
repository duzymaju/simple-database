<?php

namespace SimpleDatabase\Client\MySql;

use SimpleDatabase\Client\GroupInterface;

/**
 * Class Group
 */
class Group implements GroupInterface
{
    /** @var array */
    private $columns = [];

    /**
     * Construct
     *
     * @param array $columns columns
     */
    public function __construct(array $columns)
    {
        $this->columns = array_filter($columns, function ($column) {
            return is_string($column) && !empty($column);
        });
    }

    /**
     * To string
     *
     * @return string
     */
    public function toString()
    {
        if (count($this->columns) === 0) {
            return '';
        }

        $statement = ' GROUP BY ' . implode(', ', $this->columns);

        return $statement;
    }
}

<?php

namespace SimpleDatabase\Client\MySql;

use SimpleDatabase\Client\OrderInterface;

/**
 * Class Order
 */
class Order implements OrderInterface
{
    /** @var string */
    const ASC = 'ASC';

    /** @var string */
    const DESC = 'DESC';

    /** @var string[] */
    private $columns = [];

    /**
     * Construct
     *
     * @param string[] $columns columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
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

        $statement = ' ORDER BY ' . implode(', ', array_map(function ($direction, $column) {
            if (is_numeric($column) && is_string($direction) && !empty($direction)) {
                return $direction;
            }
            return $column . ' ' . strtoupper($direction);
        }, array_values($this->columns), array_keys($this->columns)));

        return $statement;
    }
}

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

    /** @var array */
    private $columns = [];

    /**
     * Construct
     *
     * @param array $columns columns
     */
    public function __construct(array $columns)
    {
        $this->columns = array_filter($columns, function ($order, $column) {
            return ($order === self::ASC || $order === self::DESC) && !empty($column);
        }, ARRAY_FILTER_USE_BOTH);
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

        $statement = ' ORDER BY ' . implode(', ', array_map(function ($column, $order) {
            return $column . ' ' . $order;
        }, array_keys($this->columns), array_values($this->columns)));

        return $statement;
    }
}

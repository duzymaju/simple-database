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

    /** @var string|null */
    private $having;

    /**
     * Construct
     *
     * @param string[] $columns columns
     * @param string[] $having  having
     */
    public function __construct(array $columns, array $having = [])
    {
        $this->columns = $columns;
        $this->having = $having;
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
        if (count($this->having) > 0) {
            $statement .= ' HAVING ' . implode(' && ', $this->having);
        }

        return $statement;
    }
}

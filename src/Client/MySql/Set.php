<?php

namespace SimpleDatabase\Client\MySql;

use SimpleDatabase\Client\SetInterface;

/**
 * Set
 */
class Set implements SetInterface
{
    /** @var string[] */
    private $set;

    /**
     * Construct
     *
     * @param string[] $set set
     */
    public function __construct(array $set)
    {
        $this->set = $set;
    }

    /**
     * To string
     *
     * @return string
     */
    public function toString()
    {
        if (count($this->set) === 0) {
            return '';
        }

        $statement = ' SET ' . implode(', ', $this->set);

        return $statement;
    }
}

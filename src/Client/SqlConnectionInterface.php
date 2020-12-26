<?php

namespace SimpleDatabase\Client;

/**
 * SQL Connection interface
 */
interface SqlConnectionInterface extends ConnectionInterface
{
    /**
     * Query
     *
     * @param string $statement statement
     *
     * @return array
     */
    public function query($statement);
}

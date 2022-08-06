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
     * @deprecated To be removed in 0.4.0. Use rawQuery instead.
     *
     * @param string $statement statement
     * @param bool   $fetchAll  fetch all
     *
     * @return array
     */
    public function query($statement, $fetchAll = false);

    /**
     * Raw query
     *
     * @param string $queryString query string
     * @param bool   $fetchAll    fetch all
     *
     * @return RawQueryInterface
     */
    public function rawQuery($queryString, $fetchAll = false);
}

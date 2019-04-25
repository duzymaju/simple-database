<?php

namespace SimpleDatabase\Client;

/**
 * Connection interface
 */
interface ConnectionInterface
{
    /**
     * Construct
     *
     * @param string      $host     host
     * @param string      $dbName   DB name
     * @param string      $user     user
     * @param string      $password password
     * @param int|null    $port     port
     * @param string|null $charset  charset
     */
    public function __construct($host, $dbName, $user, $password, $port = null, $charset = null);

    /**
     * Select
     *
     * @param string|array $items     items
     * @param string       $tableName table name
     * @param string|null  $tableSlug table slug
     *
     * @return QueryInterface
     */
    public function select($items, $tableName, $tableSlug = null);

    /**
     * Insert
     *
     * @param string      $tableName table name
     * @param string|null $tableSlug table slug
     *
     * @return QueryInterface
     */
    public function insert($tableName, $tableSlug = null);

    /**
     * Update
     *
     * @param string      $tableName table name
     * @param string|null $tableSlug table slug
     *
     * @return QueryInterface
     */
    public function update($tableName, $tableSlug = null);

    /**
     * Delete
     *
     * @param string      $tableName table name
     * @param string|null $tableSlug table slug
     *
     * @return QueryInterface
     */
    public function delete($tableName, $tableSlug = null);

    /**
     * Begin transaction
     *
     * @return self
     */
    public function beginTransaction();

    /**
     * Commit
     *
     * @return self
     */
    public function commit();

    /**
     * Roll back
     *
     * @return self
     */
    public function rollBack();

    /**
     * Get client
     *
     * @return mixed
     */
    public function getClient();

    /**
     * Escape
     *
     * @param string $text text
     *
     * @return string
     */
    public function escape($text);

    /**
     * Escape LIKE
     *
     * @param string $text text
     *
     * @return string
     */
    public function escapeLike($text);
}

<?php

namespace SimpleDatabase\Client\MySql;

use Exception;
use PDO;
use SimpleDatabase\Client\CommandInterface;
use SimpleDatabase\Client\ConnectionInterface;
use SimpleDatabase\Client\QueryInterface;
use SimpleDatabase\Exception\DatabaseException;

/**
 * Class Connection
 */
class Connection implements ConnectionInterface
{
    /** @var PDO */
    private $client;

    /**
     * Construct
     *
     * @param string      $host     host
     * @param string      $dbName   DB name
     * @param string      $user     user
     * @param string      $password password
     * @param int|null    $port     port
     * @param string|null $charset  charset
     *
     * @throws DatabaseException
     */
    public function __construct($host, $dbName, $user, $password, $port = null, $charset = null)
    {
        try {
            $dsnParts = [
                'dbname' => $dbName,
                'host' => $host,
                'port' => isset($port) ? (int) $port : 3306,
            ];
            $dsn = 'mysql:' . implode(';', array_map(function ($key, $value) {
                    return sprintf('%s=%s', $key, $value);
                }, array_keys($dsnParts), $dsnParts));

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => sprintf('SET NAMES %s', isset($charset) ? $charset : 'utf8'),
            ];

            $this->client = new PDO($dsn, $user, $password, $options);
        } catch (Exception $e) {
            throw new DatabaseException(
                sprintf('Error occurred during connection trial: %s', $e->getMessage()), $e->getCode(), $e
            );
        }
    }

    /**
     * Select
     *
     * @param string|array $items     items
     * @param string       $tableName table name
     * @param string|null  $tableSlug table slug
     *
     * @return QueryInterface
     */
    public function select($items, $tableName, $tableSlug = null)
    {
        if (!is_array($items)) {
            $itemsList = explode(', ', (string) $items);
            foreach ($itemsList as $key => $item) {
                $itemsList[$key] = trim($item);
            }
            $items = $itemsList;
        }
        $query = new Query($this, CommandInterface::TYPE_SELECT, $tableName, $tableSlug, $items);

        return $query;
    }

    /**
     * Insert
     *
     * @param string      $tableName table name
     * @param string|null $tableSlug table slug
     *
     * @return QueryInterface
     */
    public function insert($tableName, $tableSlug = null)
    {
        $query = new Query($this, CommandInterface::TYPE_INSERT, $tableName, $tableSlug);

        return $query;
    }

    /**
     * Update
     *
     * @param string      $tableName table name
     * @param string|null $tableSlug table slug
     *
     * @return QueryInterface
     */
    public function update($tableName, $tableSlug = null)
    {
        $query = new Query($this, CommandInterface::TYPE_UPDATE, $tableName, $tableSlug);

        return $query;
    }

    /**
     * Delete
     *
     * @param string      $tableName table name
     * @param string|null $tableSlug table slug
     *
     * @return QueryInterface
     */
    public function delete($tableName, $tableSlug = null)
    {
        $query = new Query($this, CommandInterface::TYPE_DELETE, $tableName, $tableSlug);

        return $query;
    }

    /**
     * Begin transaction
     *
     * @return self
     */
    public function beginTransaction()
    {
        $this->client->beginTransaction();

        return $this;
    }

    /**
     * Commit
     *
     * @return self
     */
    public function commit()
    {
        $this->client->commit();

        return $this;
    }

    /**
     * Roll back
     *
     * @return self
     */
    public function rollBack()
    {
        $this->client->rollBack();

        return $this;
    }

    /**
     * Get client
     *
     * @return PDO
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Escape
     *
     * @param string $text text
     *
     * @return string
     */
    public function escape($text)
    {
        if (!function_exists('mysql_real_escape_string')) {
            return $text;
        }

        return mysql_real_escape_string($text);
    }

    /**
     * Escape LIKE
     *
     * @param string $text text
     *
     * @return string
     */
    public function escapeLike($text)
    {
        return addcslashes($text, '_%\\');
    }
}

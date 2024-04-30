<?php

namespace SimpleDatabase\Client\MySql;

use DateTime;
use Exception;
use PDO;
use SimpleDatabase\Client\CommandInterface;
use SimpleDatabase\Client\QueryInterface;
use SimpleDatabase\Client\RawQueryInterface;
use SimpleDatabase\Client\SqlConnectionInterface;
use SimpleDatabase\Exception\DatabaseException;
use SimpleStructure\Tool\Parser;

/**
 * Class Connection
 */
class Connection implements SqlConnectionInterface
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
            $this->client->query(sprintf('SET time_zone = "%s"', $this->getOffsetString()));
        } catch (Exception $exception) {
            throw new DatabaseException(
                sprintf('Error occurred during connection trial: %s', $exception->getMessage()),
                (int) $exception->getCode(), $exception
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

        return new Query($this, CommandInterface::TYPE_SELECT, $tableName, $tableSlug, $items);
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
        return new Query($this, CommandInterface::TYPE_INSERT, $tableName, $tableSlug);
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
        return new Query($this, CommandInterface::TYPE_UPDATE, $tableName, $tableSlug);
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
        return new Query($this, CommandInterface::TYPE_DELETE, $tableName, $tableSlug);
    }

    /**
     * Query
     *
     * @deprecated To be removed in 0.5.0. Use rawQuery instead.
     *
     * @param string $statement statement
     * @param bool   $fetchAll  fetch all
     *
     * @return array|null
     *
     * @throws DatabaseException
     */
    public function query($statement, $fetchAll = false)
    {
        try {
            $pdoStatement = $this->client->query($statement);
            return $fetchAll ? $pdoStatement->fetchAll() : null;
        } catch (Exception $exception) {
            throw new DatabaseException(
                sprintf('Statement\'s execution failed: %s', $exception->getMessage()),
                (int) $exception->getCode(), $exception
            );
        }
    }

    /**
     * Raw query
     *
     * @param string $queryString query string
     * @param bool   $fetchAll    fetch all
     *
     * @return RawQueryInterface
     */
    public function rawQuery($queryString, $fetchAll = false)
    {
        return new RawQuery($this, $queryString, $fetchAll);
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
     * @param boolean $force force
     *
     * @return self
     */
    public function commit($force = true)
    {
        if ($force || $this->client->inTransaction()) {
            $this->client->commit();
        }

        return $this;
    }

    /**
     * Roll back
     *
     * @param boolean $force force
     *
     * @return self
     */
    public function rollBack($force = true)
    {
        if ($force || $this->client->inTransaction()) {
            $this->client->rollBack();
        }

        return $this;
    }

    /**
     * Get last insert ID
     *
     * @return int
     */
    public function getLastInsertId()
    {
        $id = $this->client->lastInsertId();

        return is_numeric($id) ? (int) $id : 0;
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
        return Parser::parseSlug($text, '_', false);
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

    /**
     * Get offset string
     *
     * @return string
     *
     * @throws Exception
     */
    private function getOffsetString()
    {
        $now = new DateTime('now');
        $offset = $now->getOffset();
        $absoluteOffset = abs($offset);

        $hours = (int) floor($absoluteOffset / 3600);
        $minutes = (int) round($absoluteOffset / 60 - $hours * 60);
        $sign = $offset >= 0 ? 1 : -1;

        return sprintf('%+d:%02d', $sign * $hours, $minutes);
    }
}

<?php

namespace SimpleDatabase\Client\MySql;

use PDO;
use PDOStatement;
use SimpleDatabase\Client\ConnectionInterface;
use SimpleDatabase\Client\RawQueryInterface;
use SimpleDatabase\Exception\DatabaseException;
use SimpleDatabase\Exception\DataException;

/**
 * Class Raw Query
 */
class RawQuery implements RawQueryInterface
{
    /** @var PDO */
    private $client;

    /** @var PDOStatement|null */
    private $statement;

    /** @var string */
    private $queryString;

    /** @var bool */
    private $fetchAll;

    /** @var string[] */
    private $params = [];

    /**
     * Construct
     *
     * @param ConnectionInterface $connection  connection
     * @param string              $queryString query string
     * @param bool                $fetchAll    fetch all
     */
    public function __construct(ConnectionInterface $connection, $queryString, $fetchAll = false)
    {
        $this->client = $connection->getClient();
        $this->queryString = $queryString;
        $this->fetchAll = $fetchAll;
    }

    /**
     * To string
     *
     * @return string
     */
    public function toString()
    {
        return $this->queryString;
    }

    /**
     * Bind param
     *
     * @param string $name name
     * @param string $type type
     *
     * @return self
     */
    public function bindParam($name, $type)
    {
        $this->resetStatement();
		$this->params[$name] = $type;

        return $this;
	}

    /**
     * Bind params
     *
     * @param array $params params
     *
     * @return self
     */
    public function bindParams(array $params)
    {
        $this->resetStatement();
        foreach ($params as $name => $type) {
            $this->params[$name] = $type;
        }

        return $this;
    }

    /**
     * Execute
     *
     * @param array $params params
     *
     * @return array|null
     *
     * @throws DatabaseException
     * @throws DataException
     */
    public function execute(array $params = [])
    {
        $paramNames = array_keys($this->params);
        $valueNames = array_keys($params);
        $namesCount = count($paramNames);
        if ($namesCount !== count($valueNames) || $namesCount !== count(array_intersect($paramNames, $valueNames))) {
            throw new DataException('Params should be equal to declared before.');
        }

        if (!isset($this->statement)) {
            $this->statement = $this->client->prepare($this->toString());
        }
        foreach ($this->params as $name => $type) {
            $pdoType = $this->getPdoType($type);
            if (array_key_exists($name, $params) && isset($pdoType)) {
                $this->statement->bindValue($name, $params[$name], $pdoType);
            }
        }
        if (!$this->statement->execute()) {
            throw new DatabaseException('Statement\'s execution failed.');
        }

        return $this->fetchAll ? $this->statement->fetchAll(PDO::FETCH_ASSOC) : null;
    }

    /**
     * Get PDO type
     *
     * @param string $type type
     *
     * @return int|null
     */
    private function getPdoType($type)
    {
        switch ($type) {
            case self::PARAM_BOOL:
                return PDO::PARAM_BOOL;

            case self::PARAM_FLOAT:
            case self::PARAM_STRING:
                return PDO::PARAM_STR;

            case self::PARAM_INT:
                return PDO::PARAM_INT;

            case self::PARAM_NULL:
                return PDO::PARAM_NULL;

            default:
                return null;
        }
    }

    /**
     * Reset statement
     */
    private function resetStatement()
    {
        if (isset($this->statement)) {
            $this->statement = null;
        }
    }
}

<?php

namespace SimpleDatabase\Client\MySql;

use PDO;
use PDOStatement;
use SimpleDatabase\Client\CommandInterface;
use SimpleDatabase\Client\ConnectionInterface;
use SimpleDatabase\Client\GroupInterface;
use SimpleDatabase\Client\LimitInterface;
use SimpleDatabase\Client\OrderInterface;
use SimpleDatabase\Client\QueryInterface;
use SimpleDatabase\Client\TableInterface;
use SimpleDatabase\Client\WhereInterface;

/**
 * Class Query
 */
class Query implements QueryInterface
{
    /** @var PDO */
    private $client;

    /** @var PDOStatement|null */
    private $statement;

    /** @var CommandInterface */
    private $command;

    /** @var TableInterface[] */
    private $tables = [];

    /** @var WhereInterface|null */
    private $where;

    /** @var OrderInterface|null */
    private $order;

    /** @var GroupInterface|null */
    private $group;

    /** @var LimitInterface|null */
    private $limit;

    /** @var string[] */
    private $params = [];

    /**
     * Construct
     *
     * @param ConnectionInterface $connection  connection
     * @param int                 $commandType command type
     * @param string              $tableName   table name
     * @param string|null         $tableSlug   table slug
     * @param array|null          $items       items
     */
    public function __construct(ConnectionInterface $connection, $commandType, $tableName, $tableSlug = null,
        array $items = null)
    {
        $this->client = $connection->getClient();
        $this->command = new Command($commandType, $items);
        $this->tables[] = new Table(Table::TYPE_MAIN, $tableName, $tableSlug);
    }

    /**
     * Join
     *
     * @param string       $tableName table name
     * @param string       $tableSlug table slug
     * @param array|string $condition condition
     *
     * @return self
     */
    public function join($tableName, $tableSlug, $condition)
    {
        $this->resetStatement();
        $this->tables[] = new Table(Table::TYPE_JOIN, $tableName, $tableSlug, $condition);

        return $this;
	}

    /**
     * Left join
     *
     * @param string       $tableName table name
     * @param string       $tableSlug table slug
     * @param array|string $condition condition
     *
     * @return self
     */
    public function leftJoin($tableName, $tableSlug, $condition)
    {
        $this->resetStatement();
        $this->tables[] = new Table(Table::TYPE_LEFT_JOIN, $tableName, $tableSlug, $condition);

        return $this;
	}

    /**
     * Right join
     *
     * @param string       $tableName table name
     * @param string       $tableSlug table slug
     * @param array|string $condition condition
     *
     * @return self
     */
    public function rightJoin($tableName, $tableSlug, $condition)
    {
        $this->resetStatement();
        $this->tables[] = new Table(Table::TYPE_RIGHT_JOIN, $tableName, $tableSlug, $condition);

        return $this;
	}

    /**
     * Outer join
     *
     * @param string       $tableName table name
     * @param string       $tableSlug table slug
     * @param array|string $condition condition
     *
     * @return self
     */
    public function outerJoin($tableName, $tableSlug, $condition)
    {
        $this->resetStatement();
        $this->tables[] = new Table(Table::TYPE_OUTER_JOIN, $tableName, $tableSlug, $condition);

        return $this;
	}

    /**
     * Where
     *
     * @param string|array $where where
     *
     * @return self
     */
    public function where($where)
    {
        $this->resetStatement();
        $this->where = new Where($where);

        return $this;
    }

    /**
     * Order by
     *
     * @param string[]|string $order order
     *
     * @return self
     */
    public function orderBy($order)
    {
        $this->resetStatement();
        $orderList = is_array($order) ? $order : array_combine([ $order ], [ Order::ASC ]);
        $this->order = new Order($orderList);

        return $this;
    }

    /**
     * Group by
     *
     * @param string[]|string $group group
     *
     * @return self
     */
    public function groupBy($group)
    {
        $this->resetStatement();
        $groupList = is_array($group) ? array_values($group) : [ $group ];
        $this->group = new Group($groupList);

        return $this;
    }

    /**
     * Limit
     *
     * @param int      $limit  limit
     * @param int|null $offset offset
     *
     * @return self
     */
    public function limit($limit, $offset = null)
    {
        $this->resetStatement();
        $this->limit = new Limit($limit, $offset);

        return $this;
    }

    /**
     * Get query string
     *
     * @return string
     */
    public function getQueryString()
    {
        $queryString = $this->command->toString() . implode('', array_map(function (TableInterface $table) {
            return $table->toString();
        }, $this->tables));

        if (isset($this->where)) {
            $queryString .= $this->where->toString();
        }
        if (isset($this->order)) {
            $queryString .= $this->order->toString();
        }
        if (isset($this->group)) {
            $queryString .= $this->group->toString();
        }
        if (isset($this->limit)) {
            $queryString .= $this->limit->toString();
        }

        return $queryString;
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
     * Execute
     *
     * @param array|null $params params
     *
     * @return array
     */
    public function execute(array $params = null)
    {
        if (!isset($this->statement)) {
            $this->statement = $this->client->prepare($this->getQueryString());
        }
        foreach ($this->params as $name => $type) {
            $pdoType = $this->getPdoType($type);
            if (array_key_exists($name, $params) && isset($pdoType)) {
                $this->statement->bindValue($name, $params[$name], $pdoType);
            }
        }
        $results = $this->statement->fetchAll(PDO::FETCH_ASSOC);

        return $results;
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

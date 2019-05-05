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
use SimpleDatabase\Client\SetInterface;
use SimpleDatabase\Client\TableInterface;
use SimpleDatabase\Client\WhereInterface;
use SimpleDatabase\Exception\DataException;

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

    /** @var SetInterface|null */
    private $set;

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
     * @param string[]|string     $items       items
     */
    public function __construct(ConnectionInterface $connection, $commandType, $tableName, $tableSlug = null,
        $items = [])
    {
        $this->client = $connection->getClient();
        $this->command = new Command($commandType, $this->getStringList($items));
        $this->tables[] = new Table(Table::TYPE_MAIN, $tableName, $tableSlug);
    }

    /**
     * Join
     *
     * @param string          $tableName table name
     * @param string          $tableSlug table slug
     * @param string[]|string $condition condition
     *
     * @return self
     */
    public function join($tableName, $tableSlug, $condition)
    {
        $this->resetStatement();
        $this->tables[] = new Table(Table::TYPE_JOIN, $tableName, $tableSlug, $this->getStringList($condition));

        return $this;
	}

    /**
     * Left join
     *
     * @param string          $tableName table name
     * @param string          $tableSlug table slug
     * @param string[]|string $condition condition
     *
     * @return self
     */
    public function leftJoin($tableName, $tableSlug, $condition)
    {
        $this->resetStatement();
        $this->tables[] = new Table(Table::TYPE_LEFT_JOIN, $tableName, $tableSlug, $this->getStringList($condition));

        return $this;
	}

    /**
     * Right join
     *
     * @param string          $tableName table name
     * @param string          $tableSlug table slug
     * @param string[]|string $condition condition
     *
     * @return self
     */
    public function rightJoin($tableName, $tableSlug, $condition)
    {
        $this->resetStatement();
        $this->tables[] = new Table(Table::TYPE_RIGHT_JOIN, $tableName, $tableSlug, $this->getStringList($condition));

        return $this;
	}

    /**
     * Outer join
     *
     * @param string          $tableName table name
     * @param string          $tableSlug table slug
     * @param string[]|string $condition condition
     *
     * @return self
     */
    public function outerJoin($tableName, $tableSlug, $condition)
    {
        $this->resetStatement();
        $this->tables[] = new Table(Table::TYPE_OUTER_JOIN, $tableName, $tableSlug, $this->getStringList($condition));

        return $this;
	}

    /**
     * Set
     *
     * @param string[]|string $set set
     *
     * @return self
     */
    public function set($set)
    {
        $this->resetStatement();
        $this->set = new Set($this->getStringList($set));

        return $this;
    }

    /**
     * Where
     *
     * @param string[]|string $where where
     *
     * @return self
     */
    public function where($where)
    {
        $this->resetStatement();
        $this->where = new Where($this->getStringList($where));

        return $this;
    }

    /**
     * Group by
     *
     * @param string[]|string $group  group
     * @param string[]|string $having having
     *
     * @return self
     */
    public function groupBy($group, $having = [])
    {
        $this->resetStatement();
        $this->group = new Group($this->getStringList($group), $this->getStringList($having));

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
        if (is_string($order)) {
            $this->order = new Order(array_combine([ $order ], [ Order::ASC ]));
        } elseif (is_array($order)) {
            $this->order = new Order(array_filter($order, function ($direction, $column) {
                $unifiedDirection = strtoupper($direction);
                return ($unifiedDirection === Order::ASC || $unifiedDirection === Order::DESC) && is_string($column) &&
                    !empty($column);
            }, ARRAY_FILTER_USE_BOTH));
        }

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
     * To string
     *
     * @return string
     */
    public function toString()
    {
        $queryString = $this->command->toString();

        $type = $this->command->getType();
        $isSelect = $type === CommandInterface::TYPE_SELECT;
        $isInsert = $type === CommandInterface::TYPE_INSERT;
        $isUpdate = $type === CommandInterface::TYPE_UPDATE;

        $tables = $isSelect ? $this->tables : array_filter($this->tables, function (TableInterface $table) {
            return $table->isMain();
        });
        if (count($tables) > 0) {
            $queryString .= implode('', array_map(function (TableInterface $table) {
                return $table->toString();
            }, $tables));
        }
        if ($isInsert || $isUpdate) {
            if (isset($this->set)) {
                $queryString .= $this->set->toString();
            }
        }
        if (!$isInsert) {
            if (isset($this->where)) {
                $queryString .= $this->where->toString();
            }
        }
        if ($isSelect) {
            if (isset($this->group)) {
                $queryString .= $this->group->toString();
            }
            if (isset($this->order)) {
                $queryString .= $this->order->toString();
            }
            if (isset($this->limit)) {
                $queryString .= $this->limit->toString();
            }
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
     * @return array|null
     *
     * @throws DataException
     */
    public function execute(array $params = null)
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
        $this->statement->execute();
        $results = $this->command->getType() === Command::TYPE_SELECT ?
            $this->statement->fetchAll(PDO::FETCH_ASSOC) : null;

        return $results;
    }

    /**
     * Get string list
     *
     * @param mixed $values values
     *
     * @return string[]
     */
    private function getStringList($values)
    {
        $valuesList = is_array($values) ? array_values($values) : [ $values ];
        $stringList = array_filter($valuesList, function ($value) {
            return is_string($value) && !empty($value);
        });

        return $stringList;
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

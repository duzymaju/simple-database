<?php

namespace SimpleDatabase\Client\MySql;

use ReflectionClass;
use SimpleDatabase\Client\CommandInterface;
use SimpleDatabase\Client\Condition\ConditionGroupInterface;
use SimpleDatabase\Client\ConnectionInterface;
use SimpleDatabase\Client\GroupInterface;
use SimpleDatabase\Client\LimitInterface;
use SimpleDatabase\Client\MySql\Condition\AndCondition;
use SimpleDatabase\Client\MySql\Condition\OrCondition;
use SimpleDatabase\Client\OrderInterface;
use SimpleDatabase\Client\QueryInterface;
use SimpleDatabase\Client\SetInterface;
use SimpleDatabase\Client\TableInterface;
use SimpleDatabase\Client\WhereInterface;
use SimpleDatabase\Exception\DatabaseException;
use SimpleDatabase\Exception\DataException;

/**
 * Class Query
 */
class Query implements QueryInterface
{
    /** @var ConnectionInterface */
    private $connection;

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

    /** @var RawQuery|null */
    private $rawQuery;

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
        $this->connection = $connection;
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
        $this->resetRawQuery();
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
        $this->resetRawQuery();
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
        $this->resetRawQuery();
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
        $this->resetRawQuery();
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
        $this->resetRawQuery();
        $this->set = new Set($this->getStringList($set));

        return $this;
    }

    /**
     * Where
     *
     * @param ConditionGroupInterface|string[]|string $where where
     *
     * @return self
     */
    public function where($where)
    {
        $this->resetRawQuery();
        $this->where = new Where($where instanceof ConditionGroupInterface ? $where : $this->getStringList($where));

        return $this;
    }

    /**
     * All of
     *
     * @param array $conditions conditions
     *
     * @return ConditionGroupInterface
     */
    public static function allOf(array $conditions)
    {
        return new AndCondition($conditions);
    }

    /**
     * Any of
     *
     * @param array $conditions conditions
     *
     * @return ConditionGroupInterface
     */
    public static function anyOf(array $conditions)
    {
        return new OrCondition($conditions);
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
        $this->resetRawQuery();
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
        $this->resetRawQuery();
        if (is_string($order)) {
            $this->order = new Order(array_combine([$order], [Order::ASC]));
        } elseif (is_array($order)) {
            $this->order = new Order(array_filter($order, function ($direction, $column) {
                if (is_numeric($column) && is_string($direction) && !empty($direction)) {
                    return true;
                }
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
        $this->resetRawQuery();
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
        $this->resetRawQuery();
		$this->params[$name] = $type;

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
        if (!isset($this->rawQuery)) {
            $fetchAll = $this->command->getType() === CommandInterface::TYPE_SELECT;
            $this->rawQuery = new RawQuery($this->connection, $this->toString(), $fetchAll);
            $this->rawQuery->bindParams($this->params);
        }

        return $this->rawQuery->execute($params);
    }

    /**
     * Clone select
     *
     * @param string[]|string $items items
     *
     * @return self
     */
    public function cloneSelect($items)
    {
        $reflection = new ReflectionClass(self::class);
        /** @var self $queryClone */
        $queryClone = $reflection->newInstanceWithoutConstructor();
        $queryClone->connection = $this->connection;
        $queryClone->command = new Command(CommandInterface::TYPE_SELECT, $this->getStringList($items));
        $queryClone->tables = $this->tables;
        $queryClone->set = $this->set;
        $queryClone->where = $this->where;
        $queryClone->order = $this->order;
        $queryClone->group = $this->group;
        $queryClone->limit = $this->limit;
        $queryClone->params = $this->params;

        return $queryClone;
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
        $valuesList = is_array($values) ? array_values($values) : [$values];

        return array_filter($valuesList, function ($value) {
            return is_string($value) && !empty($value);
        });
    }

    /**
     * Reset raw query
     */
    private function resetRawQuery()
    {
        if (isset($this->rawQuery)) {
            $this->rawQuery = null;
        }
    }
}

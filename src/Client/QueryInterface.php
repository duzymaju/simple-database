<?php

namespace SimpleDatabase\Client;

use SimpleDatabase\Client\Condition\ConditionGroupInterface;
use SimpleDatabase\Exception\DatabaseException;
use SimpleDatabase\Exception\DataException;
use SimpleDatabase\Tool\ToStringInterface;

/**
 * Query interface
 */
interface QueryInterface extends ToStringInterface
{
    /** @var string */
    const PARAM_BOOL = RawQueryInterface::PARAM_BOOL;

    /** @var string */
    const PARAM_FLOAT = RawQueryInterface::PARAM_FLOAT;

    /** @var string */
    const PARAM_INT = RawQueryInterface::PARAM_INT;

    /** @var string */
    const PARAM_NULL = RawQueryInterface::PARAM_NULL;

    /** @var string */
    const PARAM_STRING = RawQueryInterface::PARAM_STRING;

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
        $items = []);

    /**
     * Add to select
     *
     * @param string[]|string $items items
     *
     * @return self
     */
    public function addToSelect($items);

    /**
     * Join
     *
     * @param string          $tableName table name
     * @param string          $tableSlug table slug
     * @param string[]|string $condition condition
     *
     * @return self
     */
    public function join($tableName, $tableSlug, $condition);

    /**
     * Left join
     *
     * @param string          $tableName table name
     * @param string          $tableSlug table slug
     * @param string[]|string $condition condition
     *
     * @return self
     */
    public function leftJoin($tableName, $tableSlug, $condition);

    /**
     * Right join
     *
     * @param string          $tableName table name
     * @param string          $tableSlug table slug
     * @param string[]|string $condition condition
     *
     * @return self
     */
    public function rightJoin($tableName, $tableSlug, $condition);

    /**
     * Outer join
     *
     * @param string          $tableName table name
     * @param string          $tableSlug table slug
     * @param string[]|string $condition condition
     *
     * @return self
     */
    public function outerJoin($tableName, $tableSlug, $condition);

    /**
     * Set
     *
     * @param string[]|string $set set
     *
     * @return self
     */
    public function set($set);

    /**
     * Where
     *
     * @param string[]|string $where where
     *
     * @return self
     */
    public function where($where);

    /**
     * All of
     *
     * @param array $conditions conditions
     *
     * @return ConditionGroupInterface
     */
    public static function allOf(array $conditions);

    /**
     * Any of
     *
     * @param array $conditions conditions
     *
     * @return ConditionGroupInterface
     */
    public static function anyOf(array $conditions);

    /**
     * Group by
     *
     * @param string[]|string $group  group
     * @param string[]|string $having having
     *
     * @return self
     */
    public function groupBy($group, $having = []);

    /**
     * Order by
     *
     * @param string[]|string $order order
     *
     * @return self
     */
    public function orderBy($order);

    /**
     * Limit
     *
     * @param int      $limit  limit
     * @param int|null $offset offset
     *
     * @return self
     */
    public function limit($limit, $offset = null);

    /**
     * Bind param
     *
     * @param string $name name
     * @param string $type type
     *
     * @return self
     */
    public function bindParam($name, $type);

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
    public function execute(array $params = []);

    /**
     * Clone select
     *
     * @param string[]|string $items items
     *
     * @return self
     */
    public function cloneSelect($items);
}

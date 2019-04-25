<?php

namespace SimpleDatabase\Client;

/**
 * Query interface
 */
interface QueryInterface
{
    /** @var string */
    const PARAM_BOOL = 'bool';

    /** @var string */
    const PARAM_FLOAT = 'float';

    /** @var string */
    const PARAM_INT = 'int';

    /** @var string */
    const PARAM_NULL = 'null';

    /** @var string */
    const PARAM_STRING = 'string';

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
        array $items = null);

    /**
     * Join
     *
     * @param string       $tableName table name
     * @param string       $tableSlug table slug
     * @param array|string $condition condition
     *
     * @return self
     */
    public function join($tableName, $tableSlug, $condition);

    /**
     * Left join
     *
     * @param string       $tableName table name
     * @param string       $tableSlug table slug
     * @param array|string $condition condition
     *
     * @return self
     */
    public function leftJoin($tableName, $tableSlug, $condition);

    /**
     * Right join
     *
     * @param string       $tableName table name
     * @param string       $tableSlug table slug
     * @param array|string $condition condition
     *
     * @return self
     */
    public function rightJoin($tableName, $tableSlug, $condition);

    /**
     * Outer join
     *
     * @param string       $tableName table name
     * @param string       $tableSlug table slug
     * @param array|string $condition condition
     *
     * @return self
     */
    public function outerJoin($tableName, $tableSlug, $condition);

    /**
     * Where
     *
     * @param string|array $where where
     *
     * @return self
     */
    public function where($where);

    /**
     * Order by
     *
     * @param string[]|string $order order
     *
     * @return self
     */
    public function orderBy($order);

    /**
     * Group by
     *
     * @param string[]|string $group group
     *
     * @return self
     */
    public function groupBy($group);

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
     * @param array|null $params params
     *
     * @return array
     */
    public function execute(array $params = null);

    /**
     * Get query string
     *
     * @return string
     */
    public function getQueryString();
}

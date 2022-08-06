<?php

namespace SimpleDatabase\Client;

use SimpleDatabase\Exception\DatabaseException;
use SimpleDatabase\Exception\DataException;
use SimpleDatabase\Tool\ToStringInterface;

/**
 * Raw query interface
 */
interface RawQueryInterface extends ToStringInterface
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
     * @param string              $queryString query string
     * @param bool                $fetchAll    fetch all
     */
    public function __construct(ConnectionInterface $connection, $queryString, $fetchAll = false);

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
     * Bind params
     *
     * @param array $params params
     *
     * @return self
     */
    public function bindParams(array $params);

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
}

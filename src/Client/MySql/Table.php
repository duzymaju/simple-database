<?php

namespace SimpleDatabase\Client\MySql;

use SimpleDatabase\Client\TableInterface;
use SimpleDatabase\Exception\DatabaseException;

/**
 * Class Table
 */
class Table implements TableInterface
{
    /** @var int */
    private $type;

    /** @var string */
    private $tableName;

    /** @var string|null */
    private $tableSlug;

    /** @var string[] */
    private $condition;

    /**
     * Construct
     *
     * @param int         $type      type
     * @param string      $tableName table name
     * @param string|null $tableSlug table slug
     * @param string[]    $condition condition
     */
    public function __construct($type, $tableName, $tableSlug = null, array $condition = [])
    {
        $this->type = $type;
        $this->tableName = $tableName;
        $this->tableSlug = $tableSlug;
        $this->condition = $condition;
    }

    /**
     * Is main
     *
     * @return bool
     */
    public function isMain()
    {
        return $this->type === self::TYPE_MAIN;
    }

    /**
     * To string
     *
     * @return string
     *
     * @throws DatabaseException
     */
    public function toString()
    {
        $statement = $this->tableName . (isset( $this->tableSlug) ? ' ' . $this->tableSlug : '');

        if ($this->type !== self::TYPE_MAIN) {
            if (count($this->condition) === 1 && strpos(reset($this->condition), '=') === false) {
                $statement .= ' USING(' . reset($this->condition) . ')';
            } else {
                $statement .= ' ON ' . implode(' && ', $this->condition);
            }
        }

        switch ($this->type) {
            case self::TYPE_MAIN:
				return ' ' . $statement;

            case self::TYPE_JOIN:
				return ' INNER JOIN ' . $statement;

            case self::TYPE_LEFT_JOIN:
				return ' LEFT OUTER JOIN ' . $statement;

            case self::TYPE_RIGHT_JOIN:
				return ' RIGHT OUTER JOIN ' . $statement;

            case self::TYPE_OUTER_JOIN:
				return ' FULL OUTER JOIN ' . $statement;

            default:
                throw new DatabaseException(sprintf('Table type "%s" doesn\'t exist.', $this->type));
        }
    }
}

<?php

namespace SimpleDatabase\Client\MySql;

use SimpleDatabase\Client\CommandInterface;
use SimpleDatabase\Exception\DatabaseException;

/**
 * Command
 */
class Command implements CommandInterface
{
    /** @var int */
    private $type;

    /** @var array */
    private $items;

    /**
     * Construct
     *
     * @param int      $type  type
     * @param string[] $items items
     */
    public function __construct($type, array $items = [])
    {
        $this->type = $type;
        $this->items = count($items) > 0 ? $items : [ '*' ];
    }

    /**
     * Get type
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
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
        switch ($this->type) {
            case self::TYPE_SELECT:
				return 'SELECT ' . implode(', ', $this->items) . ' FROM';

            case self::TYPE_INSERT:
				return 'INSERT INTO';

            case self::TYPE_UPDATE:
				return 'UPDATE';

            case self::TYPE_DELETE:
                return 'DELETE FROM';

            default:
                throw new DatabaseException(sprintf('Command type "%s" doesn\'t exist.', $this->type));
        }
    }
}

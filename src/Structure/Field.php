<?php

namespace SimpleDatabase\Structure;

use DateTime;
use DateTimeZone;
use Exception;
use ReflectionClass;
use ReflectionException;
use SimpleDatabase\Client\QueryInterface;
use SimpleDatabase\Exception\DataException;
use SimpleDatabase\Exception\RepositoryException;
use SimpleDatabase\Model\ModelInterface;
use stdClass;

class Field
{
    /** @var string */
    const TYPE_BOOL = 'bool';

    /** @var string */
    const TYPE_DATE_TIME = 'date-time';

    /** @var string */
    const TYPE_DATE_TIME_TIMESTAMP = 'date-time-timestamp';

    /** @var string */
    const TYPE_FLOAT = 'float';

    /** @var string */
    const TYPE_INT = 'int';

    /** @var string */
    const TYPE_JSON = 'json';

    /** @var string */
    const TYPE_JSON_ASSOC = 'json-assoc';

    /** @var string */
    const TYPE_STRING = 'string';

    /** @var string */
    const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

    /** @var string */
    private $name;

    /** @var string */
    private $dbName;

    /** @var string */
    private $type;

    /** @var array */
    private $options;

    /**
     * Construct
     *
     * @param string      $name    name
     * @param string      $type    type
     * @param string|null $dbName  DB name
     * @param array       $options options
     */
    public function __construct($name, $type = self::TYPE_STRING, $dbName = null, array $options = [])
    {
        $this->name = $name;
        $this->type = $type;
        $this->dbName = empty($dbName) ? $name : $dbName;
        $this->options = $options;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get DB name
     *
     * @return string
     */
    public function getDbName()
    {
        return $this->dbName;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Is JSON type
     *
     * @return bool
     */
    public function isJsonType()
    {
        $jsonType = in_array($this->type, [ self::TYPE_JSON, self::TYPE_JSON_ASSOC ]);

        return $jsonType;
    }

    /**
     * Get DB type
     *
     * @param mixed $value value
     *
     * @return string
     */
    public function getDbType($value)
    {
        if (!isset($value)) {
            return QueryInterface::PARAM_NULL;
        }

        if ($this->isJsonType()) {
            return QueryInterface::PARAM_STRING;
        }

        switch ($this->type) {
            case self::TYPE_BOOL:
                return QueryInterface::PARAM_BOOL;

            case self::TYPE_FLOAT:
                return QueryInterface::PARAM_FLOAT;

            case self::TYPE_INT:
            case self::TYPE_DATE_TIME_TIMESTAMP:
                return QueryInterface::PARAM_INT;

            case self::TYPE_STRING:
            case self::TYPE_DATE_TIME:
            default:
                return QueryInterface::PARAM_STRING;
        }
    }

    /**
     * Get value
     *
     * @param mixed $dbValue DB value
     *
     * @return mixed
     *
     * @throws DataException
     */
    public function getValue($dbValue)
    {
        if (!isset($dbValue)) {
            return null;
        }

        if ($this->isJsonType()) {
            return is_string($dbValue) ? json_decode($dbValue, $this->type === self::TYPE_JSON_ASSOC) : null;
        }

        switch ($this->type) {
            case self::TYPE_BOOL:
                return !in_array($dbValue, [ 'false', 'null', '', '0' ]) || false;

            case self::TYPE_DATE_TIME:
                try {
                    return new DateTime($dbValue);
                } catch (Exception $exception) {
                    throw new DataException(
                        sprintf('String "%s" in "%s" field can not be converted into a date.', $dbValue, $this->name),
                        $exception->getCode(), $exception
                    );
                }

            case self::TYPE_DATE_TIME_TIMESTAMP:
                try {
                    return new DateTime('@' . $dbValue, new DateTimeZone(date_default_timezone_get()));
                } catch (Exception $exception) {
                    throw new DataException(
                        sprintf('Timestamp "%s" in "%s" field can not be converted into date.', $dbValue, $this->name),
                        $exception->getCode(), $exception
                    );
                }

            case self::TYPE_FLOAT:
                return (float) $dbValue;

            case self::TYPE_INT:
                return (int) $dbValue;

            case self::TYPE_STRING:
                return (string) $dbValue;

            default:
                return $dbValue;
        }
    }

    /**
     * Get DB value
     *
     * @param mixed $value value
     *
     * @return mixed
     */
    public function getDbValue($value)
    {
        if ($this->isJsonType() && ($value instanceof stdClass || is_array($value))) {
            return json_encode($value);
        } elseif ($this->type === self::TYPE_DATE_TIME && $value instanceof DateTime) {
            return $value->format(self::DATE_TIME_FORMAT);
        } elseif ($this->type === self::TYPE_DATE_TIME_TIMESTAMP && $value instanceof DateTime) {
            return $value->getTimestamp();
        }

        return $value;
    }

    /**
     * Get value from model
     *
     * @param ModelInterface $model model
     *
     * @return mixed
     */
    public function getValueFromModel(ModelInterface $model)
    {
        $getterName = ucfirst($this->name);
        foreach (['get', 'has', 'is'] as $getterPrefix) {
            $getterMethod = $getterPrefix . $getterName;
            if (method_exists($model, $getterMethod)) {
                return $model->$getterMethod();
            }
        }

        return null;
    }

    /**
     * Set value to model
     *
     * @param ModelInterface $model model
     * @param mixed          $value value
     *
     * @return self
     *
     * @throws RepositoryException
     */
    public function setValueToModel(ModelInterface $model, $value)
    {
        try {
            $setterMethod = 'set' . ucfirst($this->name);
            if (method_exists($model, $setterMethod)) {
                $model->$setterMethod($value);
            } else {
                $modelReflector = new ReflectionClass($model);
                $property = $modelReflector->getProperty($this->name);
                $property->setAccessible(true);
                $property->setValue($model, $value);
            }
        } catch (ReflectionException $e) {
            throw new RepositoryException(
                sprintf('There was impossible to set value "%s".', $this->name), $e->getCode(), $e
            );
        }

        return $this;
    }

    /**
     * Is ID
     *
     * @return bool
     */
    public function isId()
    {
        return array_key_exists('id', $this->options) && $this->options['id'] === true;
    }

    /**
     * Is created at
     *
     * @return bool
     */
    public function isCreatedAt()
    {
        return array_key_exists('createdAt', $this->options) && $this->options['createdAt'] === true;
    }

    /**
     * Is updated at
     *
     * @return bool
     */
    public function isUpdatedAt()
    {
        return array_key_exists('updatedAt', $this->options) && $this->options['updatedAt'] === true;
    }

    /**
     * Is settable
     *
     * @return bool
     */
    public function isSettable()
    {
        return !array_key_exists('settable', $this->options) || $this->options['settable'] !== false;
    }

    /**
     * Is addable
     *
     * @return bool
     */
    public function isAddable()
    {
        return (!array_key_exists('addable', $this->options) || $this->options['addable'] !== false) &&
            $this->isSettable();
    }

    /**
     * Is editable
     *
     * @return bool
     */
    public function isEditable()
    {
        return (!array_key_exists('editable', $this->options) || $this->options['editable'] !== false) &&
            $this->isSettable() && !$this->isCreatedAt();
    }
}

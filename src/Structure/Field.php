<?php

namespace SimpleDatabase\Structure;

use ReflectionClass;
use ReflectionException;
use SimpleDatabase\Client\QueryInterface;
use SimpleDatabase\Exception\RepositoryException;
use SimpleDatabase\Model\ModelInterface;
use stdClass;

class Field
{
    /** @var string */
    const TYPE_BOOL = 'bool';

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
    private $name;

    /** @var string */
    private $dbName;

    /** @var string */
    private $type;

    /** @var string */
    private $isId = false;

    /**
     * Construct
     *
     * @param string      $name   name
     * @param string      $type   type
     * @param string|null $dbName DB name
     * @param bool        $isId   is ID
     */
    public function __construct($name, $type = self::TYPE_STRING, $dbName = null, $isId = false)
    {
        $this->name = $name;
        $this->type = $type;
        $this->dbName = empty($dbName) ? $name : $dbName;
        $this->isId = $isId;
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
                return QueryInterface::PARAM_INT;

            case self::TYPE_STRING:
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
        $getterMethod = 'get' . ucfirst($this->name);
        if (!method_exists($model, $getterMethod)) {
            return null;
        }
        $value = $model->$getterMethod();

        return $value;
    }

    /**
     * Set value to model
     *
     * @param ModelInterface $model model
     * @param mixed          $value value
     *
     * @return self
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
        return $this->isId;
    }
}
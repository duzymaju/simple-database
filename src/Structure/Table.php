<?php

namespace SimpleDatabase\Structure;

class Table
{
    /** @var string */
    private $name;

    /** @var Field[] */
    private $fields = [];

    /** @var Field|null */
    private $autoIncrementedField;

    /**
     * Construct
     *
     * @param string $name name
     */
    public function __construct($name)
    {
        $this->name = $name;
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
     * Add string
     *
     * @param string $name    name
     * @param null   $dbName  DB name
     * @param array  $options options
     *
     * @return self
     */
    public function addString($name, $dbName = null, array $options = [])
    {
        return $this->addField($name, Field::TYPE_STRING, $dbName, $options);
    }

    /**
     * Add int
     *
     * @param string $name              name
     * @param null   $dbName            DB name
     * @param array  $options           options
     * @param bool   $isAutoIncremented is auto incremented
     *
     * @return self
     */
    public function addInt($name, $dbName = null, array $options = [], $isAutoIncremented = false)
    {
        if ($isAutoIncremented) {
            $options['id'] = true;
        }
        $this->addField($name, Field::TYPE_INT, $dbName, $options);
        if ($isAutoIncremented) {
            $this->autoIncrementedField = $this->getField($name);
        }

        return $this;
    }

    /**
     * Add float
     *
     * @param string $name    name
     * @param null   $dbName  DB name
     * @param array  $options options
     *
     * @return self
     */
    public function addFloat($name, $dbName = null, array $options = [])
    {
        return $this->addField($name, Field::TYPE_FLOAT, $dbName, $options);
    }

    /**
     * Add bool
     *
     * @param string $name    name
     * @param null   $dbName  DB name
     * @param array  $options options
     *
     * @return self
     */
    public function addBool($name, $dbName = null, array $options = [])
    {
        return $this->addField($name, Field::TYPE_BOOL, $dbName, $options);
    }

    /**
     * Add date time
     *
     * @param string $name    name
     * @param null   $dbName  DB name
     * @param array  $options options
     *
     * @return self
     */
    public function addDateTime($name, $dbName = null, array $options = [])
    {
        return $this->addField($name, Field::TYPE_DATE_TIME, $dbName, $options);
    }

    /**
     * Add date time timestamp
     *
     * @param string $name    name
     * @param null   $dbName  DB name
     * @param array  $options options
     *
     * @return self
     */
    public function addDateTimeTimestamp($name, $dbName = null, array $options = [])
    {
        return $this->addField($name, Field::TYPE_DATE_TIME_TIMESTAMP, $dbName, $options);
    }

    /**
     * Add JSON
     *
     * @param string $name    name
     * @param null   $dbName  DB name
     * @param array  $options options
     *
     * @return self
     */
    public function addJson($name, $dbName = null, array $options = [])
    {
        return $this->addField($name, Field::TYPE_JSON, $dbName, $options);
    }

    /**
     * Add associative JSON
     *
     * @param string $name    name
     * @param null   $dbName  DB name
     * @param array  $options options
     *
     * @return self
     */
    public function addJsonAssoc($name, $dbName = null, array $options = [])
    {
        return $this->addField($name, Field::TYPE_JSON_ASSOC, $dbName, $options);
    }

    /**
     * Get field
     *
     * @param string $name name
     *
     * @return Field|null
     */
    public function getField($name)
    {
        return array_key_exists($name, $this->fields) ? $this->fields[$name] : null;
    }

    /**
     * Get fields
     *
     * @return Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Get ID fields
     *
     * @return Field[]
     */
    public function getIdFields()
    {
        return array_filter($this->fields, function (Field $field) {
            return $field->isId();
        });
    }

    /**
     * Get auto incremented field
     *
     * @return Field|null
     */
    public function getAutoIncrementedField()
    {
        return $this->autoIncrementedField;
    }

    /**
     * Get addable fields
     *
     * @return Field[]
     */
    public function getAddableFields()
    {
        return array_filter($this->fields, function (Field $field) {
            return $field !== $this->autoIncrementedField && $field->isAddable();
        });
    }

    /**
     * Get editable fields
     *
     * @return Field[]
     */
    public function getEditableFields()
    {
        return array_filter($this->fields, function (Field $field) {
            return $field !== $this->autoIncrementedField && $field->isEditable();
        });
    }

    /**
     * Add field
     *
     * @param string $name    name
     * @param string $type    type
     * @param null   $dbName  DB name
     * @param array  $options options
     *
     * @return self
     */
    private function addField($name, $type = Field::TYPE_STRING, $dbName = null, array $options = [])
    {
        $this->fields[$name] = new Field($name, $type, $dbName, $options);

        return $this;
    }
}

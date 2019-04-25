<?php

namespace SimpleDatabase\Structure;

class Table
{
    /** @var string */
    private $name;

    /** @var Field[] */
    private $fields = [];

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
     * @param string $name   name
     * @param null   $dbName DB name
     * @param bool   $isId   is ID
     *
     * @return self
     */
    public function addString($name, $dbName = null, $isId = false)
    {
        return $this->addField($name, Field::TYPE_STRING, $dbName, $isId);
    }

    /**
     * Add int
     *
     * @param string $name   name
     * @param null   $dbName DB name
     * @param bool   $isId   is ID
     *
     * @return self
     */
    public function addInt($name, $dbName = null, $isId = false)
    {
        return $this->addField($name, Field::TYPE_INT, $dbName, $isId);
    }

    /**
     * Add float
     *
     * @param string $name   name
     * @param null   $dbName DB name
     * @param bool   $isId   is ID
     *
     * @return self
     */
    public function addFloat($name, $dbName = null, $isId = false)
    {
        return $this->addField($name, Field::TYPE_FLOAT, $dbName, $isId);
    }

    /**
     * Add bool
     *
     * @param string $name   name
     * @param null   $dbName DB name
     *
     * @return self
     */
    public function addBool($name, $dbName = null)
    {
        return $this->addField($name, Field::TYPE_BOOL, $dbName);
    }

    /**
     * Add JSON
     *
     * @param string $name   name
     * @param null   $dbName DB name
     *
     * @return self
     */
    public function addJson($name, $dbName = null)
    {
        return $this->addField($name, Field::TYPE_JSON, $dbName);
    }

    /**
     * Add associative JSON
     *
     * @param string $name   name
     * @param null   $dbName DB name
     *
     * @return self
     */
    public function addJsonAssoc($name, $dbName = null)
    {
        return $this->addField($name, Field::TYPE_JSON_ASSOC, $dbName);
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
        $field = array_key_exists($name, $this->fields) ? $this->fields[$name] : null;

        return $field;
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
        $idFields = array_filter($this->fields, function (Field $field) {
            return $field->isId();
        });

        return $idFields;
    }

    /**
     * Add field
     *
     * @param string $name   name
     * @param string $type   type
     * @param null   $dbName DB name
     * @param bool   $isId   is ID
     *
     * @return self
     */
    private function addField($name, $type = Field::TYPE_STRING, $dbName = null, $isId = false)
    {
        $this->fields[$name] = new Field($name, $type, $dbName, $isId);

        return $this;
    }
}

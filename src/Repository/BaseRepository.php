<?php

namespace SimpleDatabase\Repository;

use SimpleDatabase\Client\ConnectionInterface;
use SimpleDatabase\Client\QueryInterface;
use SimpleDatabase\Exception\DatabaseException;
use SimpleDatabase\Exception\RepositoryException;
use SimpleDatabase\Model\ModelInterface;
use SimpleDatabase\Structure\Field;
use SimpleDatabase\Structure\Table;
use SimpleStructure\Exception\NotFoundException;
use SimpleStructure\Tool\Paginator;

/**
 * Base repository
 */
abstract class BaseRepository
{
    /** @var string|null */
    private $modelClass;

    /** @var Table|null */
    private $table;

    /** @var ModelInterface[] */
    private $dbModelInstances = [];

    /** @var ConnectionInterface */
    protected $connection;

    /**
     * Construct
     *
     * @param ConnectionInterface $connection connection
     */
    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Set model class
     *
     * @param string $modelClass model class
     *
     * @return self
     */
    protected function setModelClass($modelClass)
    {
        $this->modelClass = $modelClass;

        return $this;
    }

    /**
     * Set structure
     *
     * @param string $tableName table name
     *
     * @return Table
     */
    protected function setStructure($tableName)
    {
        $this->table = new Table($tableName);

        return $this->table;
    }

    /**
     * Begin transaction
     *
     * @return self
     */
    protected function beginTransaction()
    {
        $this->connection->beginTransaction();

        return $this;
    }

    /**
     * Commit
     *
     * @return self
     */
    protected function commit()
    {
        $this->connection->commit();

        return $this;
    }

    /**
     * Roll back
     *
     * @return self
     */
    protected function rollBack()
    {
        $this->connection->rollBack();

        return $this;
    }

    /**
     * Count by
     *
     * @param array $conditions conditions
     *
     * @return int
     */
    protected function countBy(array $conditions)
    {
        $query = $this->connection->select('COUNT(*) AS count', $this->table->getName());

        $params = [];
        try {
            $this->bindParamsWithQuery($query, $conditions, $params);
        } catch (DatabaseException $e) {
            unset($e);
            return 0;
        }

        $results = $query->execute($params);
        $result = array_shift($results);
        $count = isset($result) ? (int) $result['count'] : 0;

        return $count;
    }

    /**
     * Get by ID
     *
     * @param mixed[] ...$ids IDs
     *
     * @return ModelInterface|null
     *
     * @throws RepositoryException
     */
    protected function getById(...$ids)
    {
        if (!isset($this->table)) {
            throw new RepositoryException(sprintf('Table structure for %s is not defined.', __CLASS__));
        }
        if (count($ids) > 0) {
            throw new RepositoryException(
                sprintf('Number of IDs defined for %s method should be at least 1.', __METHOD__)
            );
        }

        $names = array_map(function (Field $field) {
            return $field->getName();
        }, $this->table->getIdFields());
        if (count($names) !== count($ids)) {
            throw new RepositoryException(
                sprintf('Number of IDs defined for %s method should be equal %d.', __METHOD__, count($names))
            );
        }

        $conditions = array_combine($names, $ids);
        $item = $this->getOneBy($conditions);

        return $item;
    }

    /**
     * Get by ID or 404
     *
     * @param mixed[] ...$ids IDs
     *
     * @return ModelInterface
     *
     * @throws NotFoundException
     */
    protected function getByIdOr404(...$ids)
    {
        $item = $this->getById(...$ids);
        if (!isset($item)) {
            $id = count($ids) === 1 ? (string) $ids[0] : '[' . implode(', ', $ids) . ']';
            throw new NotFoundException(sprintf('Element with ID %s not found.', $id));
        }

        return $item;
    }

    /**
     * Get one by
     *
     * @param array $conditions conditions
     * @param array $order      order
     *
     * @return ModelInterface|null
     */
    protected function getOneBy(array $conditions, array $order = [])
    {
        $items = $this->getBy($conditions, $order, 1);
        $item = array_shift($items);

        return $item;
    }

    /**
     * Get one by or 404
     *
     * @param array $conditions conditions
     * @param array $order      order
     *
     * @return ModelInterface
     *
     * @throws NotFoundException
     */
    protected function getOneByOr404(array $conditions, array $order = [])
    {
        $item = $this->getOneBy($conditions, $order);
        if (!isset($item)) {
            throw new NotFoundException('Element not found.');
        }

        return $item;
    }

    /**
     * Get by
     *
     * @param array    $conditions conditions
     * @param array    $order      order
     * @param int|null $limit      limit
     * @param int      $offset     offset
     *
     * @return array
     */
    protected function getBy(array $conditions, array $order = [], $limit = null, $offset = 0)
    {
        $query = $this->connection->select('*', $this->table->getName());

        $params = [];
        try {
            $this->bindParamsWithQuery($query, $conditions, $params);
        } catch (DatabaseException $e) {
            unset($e);
            return [];
        }

        $queryOrder = [];
        foreach ($order as $key => $direction) {
            $direction = strtoupper($direction);
            if ($direction === 'RAND') {
                $queryOrder[] = 'RAND()';
            } else {
                $field = $this->table->getField($key);
                if (isset($field) && ($direction === 'ASC' || $direction === 'DESC')) {
                    $queryOrder[] = $this->connection->escape($field->getDbName()) . ' ' . $direction;
                }
            }
        }
        if (count($queryOrder) > 0) {
            $query->orderBy($queryOrder);
        }

        if (is_int($limit) && $limit >= 0 && is_int($offset) && $offset >= 0) {
            $query->limit($limit, $offset);
        }

        $results = $query->execute($params);
        $items = [];
        foreach ($results as $result) {
            $items[] = $this->createModelInstanceFromDb($result);
        }

        return $items;
    }

    /**
     * Get paginated
     *
     * @param array $conditions conditions
     * @param array $order      order
     * @param int   $page       page
     * @param int   $pack       pack
     *
     * @return Paginator
     */
    protected function getPaginated(array $conditions, array $order = [], $page = 1, $pack = null)
    {
        $page = max(1, $page);

        if (isset($pack)) {
            $limit = max(1, $pack);
            $offset = $limit * ($page - 1);
        } else {
            $limit = null;
            $offset = 0;
        }

        $items = $this->getBy($conditions, $order, $limit, $offset);
        $paginator = new Paginator($items, $page, $pack);

        return $paginator;
    }

    /**
     * Save
     *
     * @param ModelInterface $model
     *
     * @return ModelInterface
     */
    protected function save(ModelInterface $model)
    {
        if ($this->isDbModelInstance($model)) {
            $this->update($model);
            return $model;
        }

        $id = $this->insert($model);
        $conditions = [];
        $autoIncrementField = $this->table->getAutoIncrementedField();
        foreach ($this->table->getIdFields() as $field) {
            $conditions[$field->getName()] = $field === $autoIncrementField ? $id : $field->getValueFromModel($model);
        }

        return $this->getOneBy($conditions);
    }

    /**
     * Insert
     *
     * @param ModelInterface $model
     *
     * @return int
     */
    protected function insert(ModelInterface $model)
    {
        $params = [];
        $query = $this->connection->insert($this->table->getName());
        $set = $this->bindModelParamsWithQuery($query, $model, $this->table->getNonAutoIncrementedFields(), $params);
        $query->set($set);
        $query->execute($params);

        return $this->connection->getLastInsertId();
    }

    /**
     * Update
     *
     * @param ModelInterface $model
     *
     * @return self
     */
    protected function update(ModelInterface $model)
    {
        $params = [];
        $query = $this->connection->update($this->table->getName());
        $set = $this->bindModelParamsWithQuery($query, $model, $this->table->getNonAutoIncrementedFields(), $params);
        $query->set($set);
        $where = $this->bindModelParamsWithQuery($query, $model, $this->table->getIdFields(), $params);
        $query->where($where);
        $query->execute($params);

        return $this;
    }

    /**
     * Delete
     *
     * @param ModelInterface $model model
     *
     * @return self
     */
    protected function delete(ModelInterface $model)
    {
        if (!$this->isDbModelInstance($model)) {
            throw new RepositoryException('Model instance hasn\'t been created from DB and can not be deleted.');
        }

        $params = [];
        $query = $this->connection->delete($this->table->getName());
        $where = $this->bindModelParamsWithQuery($query, $model, $this->table->getIdFields(), $params);
        $query->where($where);
        $query->execute($params);

        return $this;
    }

    /**
     * Create model instance from DB
     *
     * @param array $data data
     *
     * @return mixed
     */
    protected function createModelInstanceFromDb(array $data)
    {
        $model = new $this->modelClass();
        foreach ($this->table->getFields() as $field) {
            if (array_key_exists($field->getDbName(), $data)) {
                $dbValue = $data[$field->getDbName()];
                $field->setValueToModel($model, $field->getValue($dbValue));
            }
        }
        $this->dbModelInstances[] = $model;

        return $model;
    }

    /**
     * Is DB model instance
     *
     * @param ModelInterface $model model
     *
     * @return bool
     */
    protected function isDbModelInstance(ModelInterface $model)
    {
        foreach ($this->dbModelInstances as $dbModelInstance) {
            if ($dbModelInstance === $model) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bind model params with query
     *
     * @param QueryInterface $query  query
     * @param ModelInterface $model  model
     * @param Field[]        $fields fields
     * @param string[]       $params params
     *
     * @return string[]
     */
    private function bindModelParamsWithQuery(QueryInterface $query, ModelInterface $model, array $fields, &$params)
    {
        $conditions = [];
        foreach ($fields as $field) {
            $value = $field->getValueFromModel($model);
            $conditions[] = $this->bindParamWithQuery($query, $field, $value, $params);
        }

        return $conditions;
    }

    /**
     * Bind params with query
     *
     * @param QueryInterface $query      query
     * @param array          $conditions conditions
     * @param string[]       $params     params
     */
    private function bindParamsWithQuery(QueryInterface $query, array $conditions, &$params)
    {
        $where = [];
        foreach ($conditions as $key => $value) {
            if (is_array($value) && count($value) === 0) {
                throw new DatabaseException(sprintf('Param "%s" shouldn\'t be an empty array.', $key));
            }
            $field = $this->table->getField($key);
            if (isset($field)) {
                $where[] = $this->bindParamWithQuery($query, $field, $value, $params);
            }
        }
        $query->where($where);
    }

    /**
     * Bind param with query
     *
     * @param QueryInterface $query  query
     * @param Field          $field  field
     * @param mixed          $value  value
     * @param string[]       $params params
     *
     * @return string
     */
    private function bindParamWithQuery(QueryInterface $query, Field $field, $value, &$params)
    {
        $dbName = $this->connection->escape($field->getDbName());
        if (!$field->isJsonType() && is_array($value)) {
            $dbNames = [];
            foreach (array_values($value) as $i => $subValue) {
                $dbSubName = $dbName . ($i + 1);
                $query->bindParam($dbSubName, $field->getDbType($subValue));
                $params[$dbSubName] = $field->getDbValue($subValue);
                $dbNames[] = $dbSubName;
            }
            return $dbName . ' IN (:' . implode(', :', $dbNames) . ')';
        } else {
            $query->bindParam($dbName, $field->getDbType($value));
            $params[$dbName] = $field->getDbValue($value);
            return $dbName . ' = :' . $dbName;
        }
    }
}

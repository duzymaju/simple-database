<?php

namespace SimpleDatabase\Repository;

use DateTime;
use SimpleDatabase\Client\ConnectionInterface;
use SimpleDatabase\Client\QueryInterface;
use SimpleDatabase\Client\TableInterface;
use SimpleDatabase\Exception\DatabaseException;
use SimpleDatabase\Exception\RepositoryException;
use SimpleDatabase\Model\ModelInterface;
use SimpleDatabase\Query\ModelsRelationsQuery;
use SimpleDatabase\Structure\Field;
use SimpleDatabase\Structure\Table;
use SimpleStructure\Exception\NotFoundException;
use SimpleStructure\Tool\PagesHelper;
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
     * @param mixed ...$ids IDs
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
        if (count($ids) === 0) {
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
     * @param mixed ...$ids IDs
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
     * @return ModelInterface[]
     */
    protected function getBy(array $conditions, array $order = [], $limit = null, $offset = 0)
    {
        $query = $this->createSelectQuery();

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

        $items = $this->getByQuery($query, $params);

        return $items;
    }

    /**
     * Get paginated
     *
     * @param array $conditions conditions
     * @param array $order      order
     * @param int   $page       page
     * @param int   $pack       pack
     * @param bool  $countAll   count all
     *
     * @return Paginator
     */
    protected function getPaginated(array $conditions, array $order = [], $page = 1, $pack = null, $countAll = false)
    {
        $pagesHelper = new PagesHelper($page, $pack);
        $totalNumber = $countAll ? $this->countBy($conditions) : null;
        $items = $this->getBy($conditions, $order, $pagesHelper->limit, $pagesHelper->offset);
        $paginator = $pagesHelper->getPaginator($items, $totalNumber);

        return $paginator;
    }

    /**
     * Create select query
     *
     * @param string|array $items     items
     * @param string|null  $tableSlug table slug
     * @param string|null  $tableName table name
     *
     * @return QueryInterface
     */
    protected function createSelectQuery($items = '*', $tableSlug = null, $tableName = null)
    {
        $tableName = empty($tableName) ? $this->table->getName() : $tableName;
        $query = $this->connection->select($items, $tableName, $tableSlug);

        return $query;
    }

    /**
     * Prepare select all query
     *
     * @param string $tableSlug table slug
     *
     * @return ModelsRelationsQuery
     */
    protected function prepareSelectAllQuery($tableSlug)
    {
        return new ModelsRelationsQuery($this, $tableSlug);
    }

    /**
     * Create select all query
     *
     * @param ModelsRelationsQuery $modelRelationsQuery model relations query
     *
     * @return QueryInterface
     */
    protected function createSelectAllQuery(ModelsRelationsQuery $modelRelationsQuery)
    {
        $items = [];
        foreach ($modelRelationsQuery->getRepositories() as $tableSlug => $repository) {
            /** @var BaseRepository $repository */
            array_push($items, ...$repository->getFieldsWithPrefix($tableSlug, $tableSlug));
        }
        $query = $this->createSelectQuery($items, $modelRelationsQuery->getTableSlug());
        foreach ($modelRelationsQuery->getRelations() as $relation) {
            switch ($relation->getType()) {
                case TableInterface::TYPE_LEFT_JOIN:
                    $query->leftJoin($relation->getRepository()->getTableName(), $relation->getTableSlug(),
                        $relation->getCondition());
                    break;
                case TableInterface::TYPE_RIGHT_JOIN:
                    $query->rightJoin($relation->getRepository()->getTableName(), $relation->getTableSlug(),
                        $relation->getCondition());
                    break;
                case TableInterface::TYPE_OUTER_JOIN:
                    $query->outerJoin($relation->getRepository()->getTableName(), $relation->getTableSlug(),
                        $relation->getCondition());
                    break;
                case TableInterface::TYPE_JOIN:
                default:
                    $query->join($relation->getRepository()->getTableName(), $relation->getTableSlug(),
                        $relation->getCondition());
            }
        }
        $modelRelationsQuery->setQuery($query);

        return $query;
    }

    /**
     * Get by query
     *
     * @param QueryInterface $query   query
     * @param array          $params  params
     * @param array          $options options
     *
     * @return ModelInterface[]
     */
    protected function getByQuery(QueryInterface $query, array $params = [], array $options = [])
    {
        $options = array_merge([
            'onModelCreate' => null,
            'onModelDataGet' => null,
        ], $options);

        $results = $query->execute($params);
        $items = [];
        $onModelCreateCallback = is_callable($options['onModelCreate']) ? $options['onModelCreate'] : null;
        $onModelDataGetCallback = is_callable($options['onModelDataGet']) ? $options['onModelDataGet'] : null;
        foreach ($results as $result) {
            $itemData = isset($onModelDataGetCallback) ? $onModelDataGetCallback($result) : $result;
            $item = $this->createDbModelInstance($itemData);
            if (isset($onModelCreateCallback)) {
                $onModelCreateCallback($item, $result);
            }
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Get by query paginated
     *
     * @param QueryInterface $query    query
     * @param array          $params   params
     * @param int            $page     page
     * @param int            $pack     pack
     * @param bool           $countAll count all
     * @param array          $options  options
     *
     * @return Paginator
     */
    protected function getByQueryPaginated(QueryInterface $query, array $params = [], $page = 1, $pack = null,
        $countAll = false, array $options = [])
    {
        $pagesHelper = new PagesHelper($page, $pack);
        $totalNumber = $countAll ? $this->countByQuery($query, $params) : null;
        if (isset($pagesHelper->limit)) {
            $query->limit($pagesHelper->limit, $pagesHelper->offset);
        }
        $items = $this->getByQuery($query, $params, $options);
        $paginator = $pagesHelper->getPaginator($items, $totalNumber);

        return $paginator;
    }

    /**
     * Get all by query
     *
     * @param ModelsRelationsQuery $modelRelationsQuery model relations query
     * @param array               $params              params
     * @param array               $options             options
     *
     * @return ModelInterface[]
     */
    protected function getAllByQuery(ModelsRelationsQuery $modelRelationsQuery, array $params = [], array $options = [])
    {
        $options['onModelDataGet'] = function ($data) use ($modelRelationsQuery) {
            return $this->getPrefixedValues($data, $modelRelationsQuery->getTableSlug());
        };

        $options['onModelCreate'] = function ($mainModel, $data) use ($modelRelationsQuery) {
            $relations = $modelRelationsQuery->getRelations();
            foreach ($relations as $relation) {
                $repository = $relation->getRepository();
                $model = $repository->createDbModelInstance(
                    $repository->getPrefixedValues($data, $relation->getTableSlug())
                );
                $relation->setModel($model);
            }
            foreach ($modelRelationsQuery->getRelations() as $relationFrom) {
                $relationFrom->bindWith($modelRelationsQuery->getTableSlug(), $mainModel);
                foreach ($modelRelationsQuery->getRelations() as $relationTo) {
                    $relationFrom->bindWith($relationTo->getTableSlug(), $relationTo->getModel());
                }
            }
        };

        $items = $this->getByQuery($modelRelationsQuery->getQuery(), $params, $options);

        return $items;
    }

    /**
     * Get all by query paginated
     *
     * @param ModelsRelationsQuery $modelRelationsQuery model relations query
     * @param array                $params              params
     * @param int                  $page                page
     * @param int                  $pack                pack
     * @param bool                 $countAll            count all
     * @param array                $options             options
     *
     * @return Paginator
     */
    protected function getAllByQueryPaginated(ModelsRelationsQuery $modelRelationsQuery, array $params = [], $page = 1,
        $pack = null, $countAll = false, array $options = [])
    {
        $pagesHelper = new PagesHelper($page, $pack);
        $query = $modelRelationsQuery->getQuery();
        $totalNumber = $countAll ? $this->countByQuery($query, $params) : null;
        if (isset($pagesHelper->limit)) {
            $query->limit($pagesHelper->limit, $pagesHelper->offset);
        }
        $items = $this->getAllByQuery($modelRelationsQuery, $params, $options);
        $paginator = $pagesHelper->getPaginator($items, $totalNumber);

        return $paginator;
    }

    /**
     * Count by query
     *
     * @param QueryInterface $query  query
     * @param array          $params params
     *
     * @return int
     */
    protected function countByQuery(QueryInterface $query, array $params = [])
    {
        $queryClone = $query->cloneSelect('count(*) as count');
        $results = $queryClone->execute($params);
        $count = count($results) === 1 ? (int) $results[0]['count'] : 0;

        return $count;
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
        $fields = $this->table->getAddableFields();
        foreach ($fields as $field) {
            if ($field->isCreatedAt() || $field->isUpdatedAt()) {
                $field->setValueToModel($model, new DateTime('now'));
            }
        }
        $set = $this->bindModelParamsWithQuery($query, $model, $fields, $params);
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
        $fields = $this->table->getEditableFields();
        foreach ($fields as $field) {
            if ($field->isUpdatedAt()) {
                $field->setValueToModel($model, new DateTime('now'));
            }
        }
        $set = $this->bindModelParamsWithQuery($query, $model, $fields, $params);
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
     *
     * @throws RepositoryException
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
     * Create DB model instance
     *
     * @param array $data data
     *
     * @return ModelInterface|null
     */
    protected function createDbModelInstance(array $data)
    {
        $modelExists = false;
        $model = new $this->modelClass();
        foreach ($this->table->getFields() as $field) {
            if (array_key_exists($field->getDbName(), $data)) {
                $dbValue = $data[$field->getDbName()];
                $value = $field->getValue($dbValue);
                if (!$modelExists && isset($value)) {
                    $modelExists = true;
                }
                $field->setValueToModel($model, $value);
            }
        }
        if (!$modelExists) {
            return null;
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
     * Get table
     *
     * @return Table|null
     */
    protected function getTable()
    {
        return $this->table;
    }

    /**
     * Get table name
     *
     * @return string|null
     */
    protected function getTableName()
    {
        $tableName = isset($this->table) ? $this->table->getName() : null;

        return $tableName;
    }

    /**
     * Get fields with prefix
     *
     * @param string $tableSlug   table slug
     * @param string $fieldPrefix field prefix
     *
     * @return string[]
     *
     * @throws RepositoryException
     */
    protected function getFieldsWithPrefix($tableSlug, $fieldPrefix)
    {
        $fields = array_map(function (Field $field) use ($fieldPrefix, $tableSlug) {
            return $tableSlug . '.' . $field->getDbName() . ' as ' . $fieldPrefix . '_' . $field->getDbName();
        }, array_values($this->table->getFields()));

        return $fields;
    }

    /**
     * Get prefixed values
     *
     * @param array  $data        data
     * @param string $fieldPrefix field prefix
     *
     * @return array
     */
    protected function getPrefixedValues(array $data, $fieldPrefix)
    {
        $fieldPrefix .= '_';
        $prefixLength = strlen($fieldPrefix);
        $filteredData = [];
        foreach ($data as $key => $value) {
            if (strpos($key, $fieldPrefix) === 0) {
                $filteredData[substr($key, $prefixLength)] = $value;
            }
        }

        return $filteredData;
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
        $queryName = ':' . $dbName;
        if (!$field->isJsonType() && is_array($value)) {
            $queryNames = [];
            foreach (array_values($value) as $i => $subValue) {
                $querySubName = $queryName . ($i + 1);
                $query->bindParam($querySubName, $field->getDbType($subValue));
                $params[$querySubName] = $field->getDbValue($subValue);
                $queryNames[] = $querySubName;
            }
            return $dbName . ' IN (' . implode(', ', $queryNames) . ')';
        } else {
            $query->bindParam($queryName, $field->getDbType($value));
            $params[$queryName] = $field->getDbValue($value);
            return $dbName . ' = ' . $queryName;
        }
    }
}

<?php

namespace SimpleDatabase\Relation;

use SimpleDatabase\Cache\QueryModelsCache;
use SimpleDatabase\Model\ModelInterface;
use SimpleDatabase\Repository\BaseRepository;

/**
 * Model's relation
 */
final class ModelsRelation
{
    /** @var int */
    private $type;

    /** @var BaseRepository */
    private $repository;

    /** @var string */
    private $tableSlug;

    /** @var string[]|string */
    private $condition;

    /** @var array */
    private $relationsFor = [];

    /** @var array */
    private $relationsTo = [];

    /** @var ModelInterface|null */
    private $model;

    /**
     * Construct
     *
     * @param int             $type       type
     * @param BaseRepository  $repository repository
     * @param string          $tableSlug  table slug
     * @param string[]|string $condition  condition
     */
    public function __construct($type, BaseRepository $repository, $tableSlug, $condition)
    {
        $this->type = $type;
        $this->repository = $repository;
        $this->tableSlug = $tableSlug;
        $this->condition = $condition;
    }

    /**
     * Add relation for
     *
     * @param string $relationSlug       relation slug
     * @param string $relationMethodName relation method name
     *
     * @return self
     */
    public function addRelationFor($relationSlug, $relationMethodName)
    {
        $this->relationsFor[$relationSlug] = $relationMethodName;

        return $this;
    }

    /**
     * Add relation to
     *
     * @param string $relationSlug       relation slug
     * @param string $relationMethodName relation method name
     *
     * @return self
     */
    public function addRelationTo($relationSlug, $relationMethodName)
    {
        $this->relationsTo[$relationSlug] = $relationMethodName;

        return $this;
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
     * Get repository
     *
     * @return BaseRepository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Get table slug
     *
     * @return string
     */
    public function getTableSlug()
    {
        return $this->tableSlug;
    }

    /**
     * Get condition
     *
     * @return string[]|string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * Get model
     *
     * @return ModelInterface|null
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Set model
     *
     * @param ModelInterface|null $model model
     *
     * @return self
     */
    public function setModel(ModelInterface $model = null)
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Bind with model
     *
     * @param string              $tableSlug        table slug
     * @param ModelInterface|null $model            model
     * @param QueryModelsCache    $queryModelsCache query models cache
     *
     * @return self
     */
    public function bindWith($tableSlug, ModelInterface $model, QueryModelsCache $queryModelsCache)
    {
        if (!isset($this->model) || !isset($model)) {
            return $this;
        }

        if (array_key_exists($tableSlug, $this->relationsFor)) {
            $method = $this->relationsFor[$tableSlug];
            if (!$queryModelsCache->relationExistsOnce(['for', $tableSlug, $method], $model, $this->model)) {
                $model->$method($this->model);
            }
        }
        if (array_key_exists($tableSlug, $this->relationsTo)) {
            $method = $this->relationsTo[$tableSlug];
            if (!$queryModelsCache->relationExistsOnce(['to', $tableSlug, $method], $this->model, $model)) {
                $currentModel = $this->model;
                $currentModel->$method($model);
            }
        }

        return $this;
    }
}

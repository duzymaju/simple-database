<?php

namespace SimpleDatabase\Query;

use SimpleDatabase\Client\QueryInterface;
use SimpleDatabase\Client\TableInterface;
use SimpleDatabase\Relation\ModelsRelation;
use SimpleDatabase\Repository\BaseRepository;

/**
 * Model's relations query
 */
final class ModelsRelationsQuery
{
    /** @var BaseRepository */
    private $mainRepository;

    /** @var string */
    private $tableSlug;

    /** @var ModelsRelation[] */
    private $relations = [];

    /** @var QueryInterface|null */
    private $query;

    /**
     * Construct
     *
     * @param BaseRepository $repository repository
     * @param string         $tableSlug  table slug
     */
    public function __construct(BaseRepository $repository, $tableSlug)
    {
        $this->mainRepository = $repository;
        $this->tableSlug = $tableSlug;
    }

    /**
     * Join
     *
     * @param BaseRepository  $repository   repository
     * @param string          $tableSlug    table slug
     * @param string[]|string $condition    condition
     * @param array           $relationsFor relations for
     * @param array           $relationsTo  relations to
     *
     * @return self
     */
    public function join(BaseRepository $repository, $tableSlug, $condition, array $relationsFor = [],
        array $relationsTo = [])
    {
        return $this->addJoin(TableInterface::TYPE_JOIN, $repository, $tableSlug, $condition, $relationsFor,
            $relationsTo);
    }

    /**
     * Left join
     *
     * @param BaseRepository  $repository   repository
     * @param string          $tableSlug    table slug
     * @param string[]|string $condition    condition
     * @param array           $relationsFor relations for
     * @param array           $relationsTo  relations to
     *
     * @return self
     */
    public function leftJoin(BaseRepository $repository, $tableSlug, $condition, array $relationsFor = [],
        array $relationsTo = [])
    {
        return $this->addJoin(TableInterface::TYPE_LEFT_JOIN, $repository, $tableSlug, $condition, $relationsFor,
            $relationsTo);
    }

    /**
     * Right join
     *
     * @param BaseRepository  $repository   repository
     * @param string          $tableSlug    table slug
     * @param string[]|string $condition    condition
     * @param array           $relationsFor relations for
     * @param array           $relationsTo  relations to
     *
     * @return self
     */
    public function rightJoin(BaseRepository $repository, $tableSlug, $condition, array $relationsFor = [],
        array $relationsTo = [])
    {
        return $this->addJoin(TableInterface::TYPE_RIGHT_JOIN, $repository, $tableSlug, $condition, $relationsFor,
            $relationsTo);
    }

    /**
     * Outer join
     *
     * @param BaseRepository  $repository   repository
     * @param string          $tableSlug    table slug
     * @param string[]|string $condition    condition
     * @param array           $relationsFor relations for
     * @param array           $relationsTo  relations to
     *
     * @return self
     */
    public function outerJoin(BaseRepository $repository, $tableSlug, $condition, array $relationsFor = [],
        array $relationsTo = [])
    {
        return $this->addJoin(TableInterface::TYPE_OUTER_JOIN, $repository, $tableSlug, $condition, $relationsFor,
            $relationsTo);
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
     * Get repositories
     *
     * @return array
     */
    public function getRepositories()
    {
        $allRepositories = [];
        $allRepositories[$this->tableSlug] = $this->mainRepository;
        foreach ($this->relations as $relation) {
            $allRepositories[$relation->getTableSlug()] = $relation->getRepository();
        }

        return $allRepositories;
    }

    /**
     * Get relations
     *
     * @return ModelsRelation[]
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Get query
     *
     * @return QueryInterface|null
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Set query
     *
     * @param QueryInterface $query query
     *
     * @return self
     */
    public function setQuery(QueryInterface $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Add join
     *
     * @param string          $type               type
     * @param BaseRepository  $repository         repository
     * @param string          $tableSlug          table slug
     * @param string[]|string $condition          condition
     * @param array           $relationsFor relations for
     * @param array           $relationsTo  relations to
     * @return $this
     */
    private function addJoin($type, BaseRepository $repository, $tableSlug, $condition, array $relationsFor = [],
        array $relationsTo = [])
    {
        $relation = new ModelsRelation($type, $repository, $tableSlug, $condition);
        foreach ($relationsFor as $relationSlug => $relationMethodName) {
            $relation->addRelationFor($relationSlug, $relationMethodName);
        }
        foreach ($relationsTo as $relationSlug => $relationMethodName) {
            $relation->addRelationTo($relationSlug, $relationMethodName);
        }
        $this->relations[] = $relation;

        return $this;
    }
}

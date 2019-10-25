<?php

namespace SimpleDatabase\Relation;

use SimpleDatabase\Model\ModelInterface;
use SimpleDatabase\Repository\BaseRepository;

/**
 * Models relation
 */
final class ModelsRelation
{
    /** @var BaseRepository */
    private $repository;

    /** @var string */
    private $methodName;

    /** @var ModelInterface */
    private $model;

    /**
     * Construct
     *
     * @param BaseRepository $repository repository
     * @param string         $methodName method name
     * @param ModelInterface $model      model
     */
    public function __construct(BaseRepository $repository, $methodName, ModelInterface $model)
    {
        $this->repository = $repository;
        $this->methodName = $methodName;
        $this->model = $model;
    }

    /**
     * Relates to
     *
     * @param BaseRepository $repository repository
     *
     * @return bool
     */
    public function relatesTo(BaseRepository $repository)
    {
        $relatesTo = $repository === $this->repository;

        return $relatesTo;
    }

    /**
     * Set model
     *
     * @param ModelInterface $model model
     *
     * @return self
     */
    public function setModel(ModelInterface $model)
    {
        $methodName = $this->methodName;
        $this->model->$methodName($model);

        return $this;
    }
}

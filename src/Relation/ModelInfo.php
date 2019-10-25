<?php

namespace SimpleDatabase\Relation;

use SimpleDatabase\Model\ModelInterface;
use SimpleDatabase\Repository\BaseRepository;

/**
 * Model info
 */
final class ModelInfo
{
    /** @var BaseRepository */
    public $repository;

    /** @var ModelInterface */
    public $model;

    /**
     * Construct
     *
     * @param BaseRepository $repository repository
     * @param ModelInterface $model      model
     */
    public function __construct(BaseRepository $repository, ModelInterface $model)
    {
        $this->repository = $repository;
        $this->model = $model;
    }
}

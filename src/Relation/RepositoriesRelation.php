<?php

namespace SimpleDatabase\Relation;

use SimpleDatabase\Model\ModelInterface;
use SimpleDatabase\Repository\BaseRepository;

/**
 * Repositories relation
 */
final class RepositoriesRelation
{
    /** @var BaseRepository */
    private $repository;

    /** @var string */
    private $methodName;

    /**
     * Construct
     *
     * @param BaseRepository $repository repository
     * @param string         $methodName method name
     */
    public function __construct(BaseRepository $repository, $methodName)
    {
        $this->repository = $repository;
        $this->methodName = $methodName;
    }

    /**
     * Relates to one of
     *
     * @param BaseRepository[] $repositories repositories
     *
     * @return bool
     */
    public function relatesToOneOf(array $repositories)
    {
        foreach ($repositories as $repository) {
            if ($repository === $this->repository) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get models relation
     *
     * @param ModelInterface $model model
     *
     * @return ModelsRelation
     */
    public function getModelsRelation(ModelInterface $model)
    {
        $modelsRelation = new ModelsRelation($this->repository, $this->methodName, $model);

        return $modelsRelation;
    }
}

<?php

namespace SimpleDatabase\Cache;

use SimpleDatabase\Model\ModelInterface;

class QueryModelsCache
{
    /** @var array */
    private $cache = [];

    /** @var array */
    private $relations = [];

    /**
     * Support model instance
     *
     * @param string   $category                    category
     * @param array    $idParts                     ID parts
     * @param callback $createModelInstanceCallback create model instance callback
     *
     * @return ModelInterface|null
     */
    public function supportModelInstance($category, $idParts, $createModelInstanceCallback)
    {
        $cacheId = count($idParts) > 0 ? md5(implode('-', $idParts)) : null;
        if (!array_key_exists($category, $this->cache)) {
            $this->cache[$category] = [];
        } elseif ($cacheId && array_key_exists($cacheId, $this->cache[$category])) {
            return $this->cache[$category][$cacheId];
        }

        $dbModelInstance = $createModelInstanceCallback();
        if ($cacheId) {
            $this->cache[$category][$cacheId] = $dbModelInstance;
        }

        return $dbModelInstance;
    }

    /**
     * Relation exists once
     *
     * @param string[] $categoryPath category path
     * @param object   $object1      object 1
     * @param object   $object2      object 2
     *
     * @return bool
     */
    public function relationExistsOnce(array $categoryPath, $object1, $object2)
    {
        $category = implode('/', $categoryPath);
        if (!array_key_exists($category, $this->relations)) {
            $this->relations[$category] = [];
        } else {
            foreach ($this->relations[$category] as $objects) {
                if ($objects[0] === $object1 && $objects[1] === $object2) {
                    return true;
                }
            }
        }
        $this->relations[$category][] = [$object1, $object2];

        return false;
    }
}

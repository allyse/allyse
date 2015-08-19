<?php

class Cache extends Call
{

    /**
     * Connect to filesystem cache
     *
     * @return \Stash\Pool
     */
    private function connect ()
    {
        $driver = new Stash\Driver\FileSystem();
        $driver->setOptions(['path' => CACHE_DIR]);

        return new Stash\Pool($driver);
    }

    /**
     * Get cache item
     *
     * @param string|array $cacheId (optional)
     * @return \Stash\Interfaces\ItemInterface
     */
    public function pool ($cacheId)
    {
        $pool = $this->connect();

        if (is_string($cacheId) === TRUE) {
            return $pool->getItem($cacheId);
        } elseif (is_array($cacheId) === TRUE) {
            return call_user_func_array([$pool, 'getItems'], $cacheId);
        }
    }

    /**
     * Attempt to get the data
     *
     * @param Stash\Item $item
     * @return mixed
     */
    public function get ($item)
    {
        return $item->get();
    }

    /**
     * Check to see if the data is cached
     *
     * @param Stash\Item $item
     * @return bool
     */
    public function isCached ($item)
    {
        return !$item->isMiss();
    }

    /**
     * Let other processes know that this one is rebuilding the data
     *
     * @param Stash\Item $item
     * @return bool
     */
    public function lock ($item)
    {
        return $item->lock();
    }

    /**
     * Store the expensive code so the next time it doesn't miss
     *
     * @param Stash\Item $item
     * @param mixed      $data
     * @param mixed      $lifetime
     * @return bool
     */
    public function set ($item, $data, $lifetime = CACHE)
    {
        return $item->set($data, $lifetime);
    }

    /**
     * Clear out the now invalid data from the cache
     *
     * @param Stash\Item $item
     * @return bool
     */
    public function clear ($item)
    {
        return $item->clear();
    }

    /**
     * Emptying the Entire Cache
     *
     * @return bool
     */
    public function flush ()
    {
        $pool = $this->connect();

        return $pool->flush();
    }

    /**
     * Running Maintenance
     *
     * @return bool
     */
    public function optimize ()
    {
        $pool = $this->connect();

        return $pool->purge();
    }

}
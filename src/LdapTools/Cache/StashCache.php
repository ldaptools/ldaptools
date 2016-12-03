<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Cache;

use Stash\Driver\FileSystem;
use Stash\Interfaces\PoolInterface;
use Stash\Pool;

/**
 * Provides a wrapper around the Stash library to implement the cache interface.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class StashCache implements CacheInterface
{
    use CacheTrait;

    /**
     * @var string The cache folder location.
     */
    protected $cacheFolder;

    /**
     * @var Pool The Stash cache pool.
     */
    protected $pool;

    /**
     * @var bool Whether the cache should auto refresh based on creation/modification times.
     */
    protected $useAutoCache = true;

    public function __construct(PoolInterface $pool = null)
    {
        if ($pool) {
            $this->pool = $pool;
        }
        $this->setCacheFolder(sys_get_temp_dir().$this->cachePrefix);
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        $this->parseOptions($options);
    }

    /**
     * The location to where the cache will be kept.
     *
     * @return string
     */
    public function getCacheFolder()
    {
        return $this->cacheFolder;
    }

    /**
     * Set the location where the cache will be kept.
     *
     * @param $folder
     */
    public function setCacheFolder($folder)
    {
        $this->cacheFolder = $folder;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($key)
    {
        return !$this->getCacheItem($key)->isMiss();
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        if (!$this->contains($key)) {
            return null;
        }
        $item = $this->getCacheItem($key);

        return new CacheItem($key, $item->get(), $item->getExpiration());
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheCreationTime($key)
    {
        return $this->getCacheItem($key)->getCreation();
    }

    /**
     * {@inheritdoc}
     */
    public function getUseAutoCache()
    {
        return $this->useAutoCache;
    }

    /**
     * {@inheritdoc}
     */
    public function set(CacheItem $cacheItem)
    {
        $item = $this->getCacheItem($cacheItem->getKey());
        $item->get();
        $item->lock();
        $item->expiresAt($cacheItem->getExpiresAt());
        $this->getPool()->save($item->set($cacheItem->getValue()));
        $cacheItem->setExpiresAt($item->getExpiration());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        return $this->getPool()->getItem($this->getCacheName($key))->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        return $this->getPool()->clear();
    }

    /**
     * Check for any options that apply to Stash.
     *
     * @param array $options
     */
    protected function parseOptions(array $options)
    {
        if (isset($options['cache_folder'])) {
            $this->setCacheFolder($options['cache_folder']);
        }
        if (isset($options['cache_prefix'])) {
            $this->cachePrefix = $options['cache_prefix'];
        }
        if (isset($options['cache_auto_refresh'])) {
            $this->useAutoCache = $options['cache_auto_refresh'];
        }
    }

    /**
     * @param string $key
     * @return \Stash\Interfaces\ItemInterface
     */
    protected function getCacheItem($key)
    {
        return $this->getPool()->getItem($this->getCacheName($key));
    }

    /**
     * @return Pool
     */
    protected function getPool()
    {
        if (!$this->pool) {
            $this->pool = new Pool(new FileSystem(['path' => $this->cacheFolder]));
        }

        return $this->pool;
    }
}

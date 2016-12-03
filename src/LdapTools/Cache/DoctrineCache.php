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

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\FilesystemCache;

/**
 * A wrapper around the Doctrine Cache.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class DoctrineCache implements CacheInterface
{
    use CacheTrait;

    /**
     * @var string The cache folder location.
     */
    protected $cacheFolder;

    /**
     * @var FileSystemCache
     */
    protected $cache;

    public function __construct(Cache $cache = null)
    {
        $this->cacheFolder = sys_get_temp_dir().'/ldaptools';
        if ($cache) {
            $this->cache = $cache;
        }
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
    public function get($key)
    {
        if (!$this->contains($key)) {
            return null;
        }

        return new CacheItem($key, $this->getCache()->fetch($this->getCacheName($key)));
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheCreationTime($key)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getUseAutoCache()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($key)
    {
        return $this->getCache()->contains($this->getCacheName($key));
    }

    /**
     * {@inheritdoc}
     */
    public function set(CacheItem $cacheItem)
    {
        $lifeTime = $cacheItem->getExpiresAt() ? $cacheItem->getExpiresAt()->getTimestamp() - time() : 0;
        $this->getCache()->save(
            $this->getCacheName($cacheItem->getKey()),
            $cacheItem->getValue(),
            $lifeTime
        );

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        return $this->getCache()->delete($this->getCacheName($key));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        return $this->getCache()->flushAll();
    }

    /**
     * @return FileSystemCache
     */
    protected function getCache()
    {
        if (!$this->cache) {
            $this->cache = new FilesystemCache($this->cacheFolder);
        }

        return $this->cache;
    }

    /**
     * Check for any options that apply to the Doctrine cache.
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
    }
}

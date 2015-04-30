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

use Doctrine\Common\Cache\FilesystemCache;

/**
 * A wrapper around the Doctrine Cache.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class DoctrineCache implements CacheInterface
{
    /**
     * @var string The cache folder location.
     */
    protected $cacheFolder;

    /**
     * @var string The prefix to the cache.
     */
    protected $cachePrefix = '/ldaptools';

    /**
     * @var FileSystemCache
     */
    protected $cache;

    public function __construct()
    {
        $this->cacheFolder = sys_get_temp_dir().'/ldaptools';
    }

    /**
     * {@inheritdoc}
     */
    public function setOptions(array $options)
    {
        $this->parseOptions($options);
    }

    /**
     * The prefix to use for the root Stash directory for the cache.
     *
     * @param $prefix
     */
    public function setCachePrefix($prefix)
    {
        $this->cachePrefix = $prefix;
    }

    /**
     * The prefix used for the root Stash directory for the cache.
     *
     * @return string
     */
    public function getCachePrefix()
    {
        return $this->cachePrefix;
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
    public function get($itemType, $itemName)
    {
        if (!$this->contains($itemType, $itemName)) {
            return null;
        }

        return $this->getCache()->fetch($this->getCacheName($itemType, $itemName));
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheCreationTime($itemType, $itemName)
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
    public function contains($itemType, $itemName)
    {
        return $this->getCache()->contains($this->getCacheName($itemType, $itemName));
    }

    /**
     * {@inheritdoc}
     */
    public function set(CacheableItemInterface $cacheableItem)
    {
        $this->getCache()->save(
            $this->getCacheName($cacheableItem->getCacheType(), $cacheableItem->getCacheName()),
            $cacheableItem
        );

        return $cacheableItem;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($type, $name)
    {
        return $this->getCache()->delete($this->getCacheName($type, $name));
    }

    /**
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        return $this->getCache()->flushAll();
    }

    /**
     * Form the name used to refer to the item for Doctrine cache
     *
     * @param string $itemType
     * @param string $itemName
     * @return string
     */
    protected function getCacheName($itemType, $itemName)
    {
        return $this->cachePrefix.'/'.$itemType.'/'.$itemName;
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

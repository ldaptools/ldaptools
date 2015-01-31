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
use Stash\Pool;

/**
 * Provides a wrapper around the Stash library to implement the cache interface.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class StashCache implements CacheInterface
{
    /**
     * @var string The cache folder location.
     */
    protected $cacheFolder;

    /**
     * @var Pool The Stash cache pool.
     */
    protected $pool;

    /**
     * @var FileSystem The Stash driver.
     */
    protected $driver;

    /**
     * @var string The prefix to the cache.
     */
    protected $cachePrefix = '/ldaptools';

    public function __construct()
    {
        $this->driver = new FileSystem();
        $this->pool = new Pool($this->driver);
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
        $this->driver->setOptions(['path' => $folder]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($itemType, $itemName)
    {
        $item = $this->pool->getItem($this->getCachePath($itemType, $itemName));

        return $item->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheCreationTime($itemType, $itemName)
    {
        return $this->pool->getItem($this->getCachePath($itemType, $itemName))->getCreation();
    }

    /**
     * {@inheritdoc}
     */
    public function set(CacheableItemInterface $cacheableItem)
    {
        $item = $this->pool->getItem($this->getCachePath(
            $cacheableItem->getCacheType(), $cacheableItem->getCacheName()
        ));
        $data = $item->get();

        if ($item->isMiss()) {
            $item->lock();
            $item->set($cacheableItem);
        } else {
            $cacheableItem = $data;
        }

        return $cacheableItem;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($itemType = '')
    {
        $path = $itemType ? $this->cachePrefix.'/'.$itemType : $this->cachePrefix;

        return $this->pool->getItem($path)->clear();
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
    }

    /**
     * Form the "directory" string that Stash uses to look for the item.
     *
     * @param string $itemType
     * @param string $itemName
     * @return string
     */
    protected function getCachePath($itemType, $itemName)
    {
        return $this->cachePrefix.'/'.$itemType.'/'.$itemName;
    }
}

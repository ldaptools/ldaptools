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

/**
 * The interface that any cache mechanism must implement.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface CacheInterface
{
    /**
     * Sets any configured options for the Cache type.
     *
     * @param array $options
     */
    public function setOptions(array $options);

    /**
     * Retrieve an item from the cache. If it is not in the cache it should return null. However, you should call the
     * contains method first rather than relying on that value.
     *
     * @param string $key
     * @return CacheItem|null
     */
    public function get($key);

    /**
     * Retrieve the time a cache item was created. Return false if there is no item in the cache.
     *
     * @param string $key
     * @return bool|\DateTime
     */
    public function getCacheCreationTime($key);

    /**
     * Whether to auto refresh cache based on creation/modification times instead of a manual process.
     *
     * @return bool
     */
    public function getUseAutoCache();

    /**
     * Cache an item.
     *
     * @param CacheItem $cacheItem
     * @return $this
     */
    public function set(CacheItem $cacheItem);

    /**
     * Check whether the item is in the cache by the key name.
     *
     * @param string $key
     * @return bool
     */
    public function contains($key);

    /**
     * Delete a specific item from the cache by the key name.
     *
     * @param string $key The key name for the cache item.
     * @param bool
     */
    public function delete($key);

    /**
     * Delete all items from the cache.
     *
     * @return bool
     */
    public function deleteAll();
}

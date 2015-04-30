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
     * @param $itemType
     * @param $itemName
     * @return mixed
     */
    public function get($itemType, $itemName);

    /**
     * Retrieve the time a cache item was created. Return false if there is no item in the cache.
     *
     * @param $itemType
     * @param $itemName
     * @return bool|\DateTime
     */
    public function getCacheCreationTime($itemType, $itemName);


    /**
     * Whether to auto refresh cache based on creation/modification times instead of a manual process.
     *
     * @return bool
     */
    public function getUseAutoCache();

    /**
     * Cache an item.
     *
     * @param CacheableItemInterface $item
     */
    public function set(CacheableItemInterface $item);

    /**
     * Whether the item is in the cache.
     *
     * @param string $itemType
     * @param string $itemName
     * @return bool
     */
    public function contains($itemType, $itemName);

    /**
     * Delete a specific item from the cache.
     *
     * @param string $type The item type.
     * @param string $name The item name.
     * @param bool
     */
    public function delete($type, $name);

    /**
     * Delete all items from the cache.
     *
     * @return bool
     */
    public function deleteAll();
}

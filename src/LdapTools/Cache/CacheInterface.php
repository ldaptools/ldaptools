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
     * Retrieve an item from the cache.
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
     * Cache an item.
     *
     * @param CacheableItemInterface $item
     */
    public function set(CacheableItemInterface $item);

    /**
     * Clear the cache.
     *
     * @param string $itemType Optionally clear a specific item type only.
     */
    public function clear($itemType = '');
}

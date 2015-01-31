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
 * Any item that can be cached must implement this.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface CacheableItemInterface
{
    /**
     * The cache name that should be referred to for the item. It should be unique.
     *
     * @return string
     */
    public function getCacheName();

    /**
     * The cache type of the item, such as the class name or something else.
     *
     * @return string
     */
    public static function getCacheType();
}

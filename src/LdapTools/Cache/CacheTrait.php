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

use LdapTools\Utilities\MBString;

/**
 * Removes some duplication for some common cache functionality.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait CacheTrait
{
    /**
     * @var string The prefix for the cache.
     */
    protected $cachePrefix = '/ldaptools';

    /**
     * Set the prefix used for the cache item name.
     *
     * @param string $prefix
     */
    public function setCachePrefix($prefix)
    {
        $this->cachePrefix = $prefix;
    }

    /**
     * Get the prefix used for the cache item name.
     *
     * @return string
     */
    public function getCachePrefix()
    {
        return $this->cachePrefix;
    }

    /**
     * Form the "name" string that the cache uses to refer to this item.
     *
     * @param string $key
     * @return string
     */
    protected function getCacheName($key)
    {
        return MBString::strtolower($this->cachePrefix.'/'.$key);
    }
}

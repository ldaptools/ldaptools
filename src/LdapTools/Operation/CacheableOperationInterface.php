<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Operation;

/**
 * The interface needed for a cacheable operation.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface CacheableOperationInterface
{
    /**
     * Set whether the operation should execute on a cache miss.
     *
     * @param bool $executeOnMiss
     * @return $this
     */
    public function setExecuteOnCacheMiss($executeOnMiss);

    /**
     * Get whether the operation should execute on a cache miss.
     *
     * @return bool
     */
    public function getExecuteOnCacheMiss();

    /**
     * Set whether the cache should be used.
     *
     * @param bool $useCache
     * @return $this
     */
    public function setUseCache($useCache);

    /**
     * Get whether the cache should be used.
     *
     * @return bool
     */
    public function getUseCache();

    /**
     * Set the time for when the cache should expire.
     *
     * @param \DateTimeInterface|null $dateTime
     * @return $this
     */
    public function setExpireCacheAt(\DateTimeInterface $dateTime = null);

    /**
     * Get the time for when the cache should expire.
     *
     * @return \DateTimeInterface|null
     */
    public function getExpireCacheAt();

    /**
     * Set whether or not the cache should be invalidated if it exists.
     *
     * @param bool $invalidate
     * @return mixed
     */
    public function setInvalidateCache($invalidate);

    /**
     * Get whether or not the cache should be invalidated if it exists.
     *
     * @return bool
     */
    public function getInvalidateCache();

    /**
     * Get the string key used to identify this operation in the cache.
     *
     * @return string
     */
    public function getCacheKey();
}

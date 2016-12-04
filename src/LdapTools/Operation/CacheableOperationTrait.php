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
 * Implements the methods needed for a cacheable operation.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait CacheableOperationTrait
{
    /**
     * @var bool
     */
    protected $useCache = false;

    /**
     * @var bool
     */
    protected $executeOnCacheMiss = true;

    /**
     * @var \DateTimeInterface|null
     */
    protected $cacheExpiresAt = null;

    /**
     * @var bool
     */
    protected $invalidateCache = false;

    /**
     * @param bool $useCache
     * @return $this
     */
    public function setUseCache($useCache)
    {
        $this->useCache = (bool) $useCache;

        return $this;
    }

    /**
     * @return bool
     */
    public function getUseCache()
    {
        return $this->useCache;
    }

    /**
     * @param bool $executeOnCacheMiss
     * @return $this
     */
    public function setExecuteOnCacheMiss($executeOnCacheMiss)
    {
        $this->executeOnCacheMiss = (bool) $executeOnCacheMiss;

        return $this;
    }

    /**
     * @return bool
     */
    public function getExecuteOnCacheMiss()
    {
        return $this->executeOnCacheMiss;
    }

    /**
     * @param \DateTimeInterface|null $dateTime
     * @return $this
     */
    public function setExpireCacheAt(\DateTimeInterface $dateTime = null)
    {
        $this->cacheExpiresAt = $dateTime;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getExpireCacheAt()
    {
        return $this->cacheExpiresAt;
    }

    /**
     * @param bool $invalidate
     * @return $this
     */
    public function setInvalidateCache($invalidate)
    {
        $this->invalidateCache = (bool) $invalidate;

        return $this;
    }

    /**
     * @return bool
     */
    public function getInvalidateCache()
    {
        return $this->invalidateCache;
    }
}

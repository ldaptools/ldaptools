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
 * Represents a cache item.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class CacheItem
{
    /**
     * Convenience prefix types for certain cache items.
     */
    const TYPE = [
        'OPERATION_RESULT' => 'OperationResult',
        'SCHEMA_OBJECT' => 'SchemaObject',
    ];

    /**
     * @var string
     */
    protected $key;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var \DateTimeInterface|null
     */
    protected $expiry;

    /**
     * @param string $key The key for the cache item.
     * @param mixed $value The value for the cache item.
     * @param \DateTimeInterface|null $expiry When the cache item expires
     */
    public function __construct($key, $value, $expiry = null)
    {
        $this->key = $key;
        $this->value = $value;
        $this->expiry = $expiry;
    }

    /**
     * Get the key name for the cache item.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the value for the cache item.
     *
     * @param mixed $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the value for the cache item.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the time when this cache item expires.
     *
     * @param \DateTimeInterface|null $time
     * @return $this
     */
    public function setExpiresAt(\DateTimeInterface $time = null)
    {
        $this->expiry = $time;

        return $this;
    }

    /**
     * Get the time for when this cache item expires.
     *
     * @return \DateTimeInterface|null
     */
    public function getExpiresAt()
    {
        return $this->expiry;
    }
}

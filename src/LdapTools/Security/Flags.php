<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Security;

/**
 * Represents a base flag structure.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class Flags
{
    /**
     * @var int
     */
    protected $flags;

    /**
     * @param int|string $flags
     */
    public function __construct($flags = 0)
    {
        $this->flags = (int) $flags;
    }

    /**
     * Add a flag to the value.
     *
     * @param int[] ...$flags
     * @return $this
     */
    public function add(...$flags)
    {
        foreach ($flags as $flag) {
            if (!$this->has($flag)) {
                $this->flags = $this->flags | (int)$flag;
            }
        }

        return $this;
    }

    /**
     * Remove a flag from the value.
     *
     * @param int[] ...$flags
     * @return $this
     */
    public function remove(...$flags)
    {
        foreach ($flags as $flag) {
            if ($this->has($flag)) {
                $this->flags = $this->flags ^ (int) $flag;
            }
        }

        return $this;
    }

    /**
     * @param $flag
     * @return bool
     */
    public function has($flag)
    {
        return (bool) ($this->flags & $flag);
    }

    /**
     * Get the flag integer value.
     *
     * @return int
     */
    public function getValue()
    {
        return $this->flags;
    }

    /**
     * @param int $flag
     * @param bool $action
     * @return $this|bool
     */
    protected function hasOrSet($flag, $action)
    {
        if (is_null($action)) {
            $result = $this->has($flag);
        } else {
            $result = ((bool) $action === true) ? $this->add($flag) : $this->remove($flag);
        }

        return $result;
    }
}

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
 * Used in flags that can be translated to SDDL.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait FlagsSddlTrait
{
    abstract public function has($value);

    /**
     * Get the short names used in SDDL.
     *
     * @return array
     */
    public function getShortNames()
    {
        $names = [];

        $used = [];
        foreach (static::SHORT_NAME as $name => $value) {
            if ($this->has($value) && !in_array($value, $used)) {
                $names[] = $name;
                $used[] = $value;
            }
        }

        return $names;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return implode('', $this->getShortNames());
    }
}

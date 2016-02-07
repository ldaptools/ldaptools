<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Factory;

use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Hydrator\ArrayHydrator;
use LdapTools\Hydrator\LdapObjectHydrator;

/**
 * Gets the appropriate LDAP hydrator type.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class HydratorFactory
{
    /**
     * Hydrates to a simple array.
     */
    const TO_ARRAY = 'array';

    /**
     * Hydrates to a LdapObject (single result) or LdapObjectCollection (all results).
     */
    const TO_OBJECT = 'object';

    /**
     * Get the hydrator by its type.
     *
     * @param string $hydratorType
     * @return \LdapTools\Hydrator\HydratorInterface
     */
    public function get($hydratorType)
    {
        if (self::TO_ARRAY == $hydratorType) {
            return new ArrayHydrator();
        } elseif (self::TO_OBJECT == $hydratorType) {
            return new LdapObjectHydrator();
        } else {
            throw new InvalidArgumentException(sprintf('Unknown hydrator type "%s".', $hydratorType));
        }
    }
}

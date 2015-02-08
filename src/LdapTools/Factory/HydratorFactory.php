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

use LdapTools\Hydrator\ArrayHydrator;

/**
 * Gets the appropriate LDAP hydrator type.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class HydratorFactory
{
    const TO_ARRAY = 'array';

    public function get($hydratorType)
    {
        if (self::TO_ARRAY == $hydratorType) {
            return new ArrayHydrator();
        } else {
            throw new \InvalidArgumentException(sprintf('Unknown hydrator type "%s".', $hydratorType));
        }
    }
}

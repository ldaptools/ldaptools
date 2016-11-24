<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Security\Ace;

use LdapTools\Security\Flags;

/**
 * Represents ACE object flags.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class AceObjectFlags extends Flags
{
    /**
     * Valid flags for an objectType
     */
    const FLAG = [
        'OBJECT_TYPES_INVALID' => 0x00000000,
        'OBJECT_TYPE_PRESENT' => 0x00000001,
        'INHERITED_OBJECT_TYPE_PRESENT' => 0x00000002,
    ];

    /**
     * Check or set whether an object type is present. This is done automatically when setting it on the ACE.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function objectTypePresent($action = null)
    {
        return $this->hasOrSet(self::FLAG['OBJECT_TYPE_PRESENT'], $action);
    }

    /**
     * Check or set whether an inherited object type is present. This is done automatically when setting it on the ACE.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function inheritedObjectTypePresent($action = null)
    {
        return $this->hasOrSet(self::FLAG['INHERITED_OBJECT_TYPE_PRESENT'], $action);
    }

    /**
     * Check whether object types are invalid.
     *
     * @return bool
     */
    public function objectTypesInvalid()
    {
        return $this->flags === self::FLAG['OBJECT_TYPES_INVALID'];
    }
}

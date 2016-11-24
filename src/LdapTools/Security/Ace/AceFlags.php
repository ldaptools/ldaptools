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
use LdapTools\Security\FlagsSddlTrait;

/**
 * Represents ACE flags.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class AceFlags extends Flags
{
    use FlagsSddlTrait;

    /**
     * Valid ACE type-specific control flags.
     */
    const FLAG = [
        'OBJECT_INHERIT' => 0x01,
        'CONTAINER_INHERIT' => 0x02,
        'NO_PROPAGATE_INHERIT' => 0x04,
        'INHERIT_ONLY' => 0x08,
        'INHERITED' => 0x10,
        'SUCCESSFUL_ACCESS' => 0x40,
        'FAILED_ACCESS' => 0x80,
    ];

    /**
     * The short name for the flag used in SDDL.
     */
    const SHORT_NAME = [
        'OI' => 0x01,
        'CI' => 0x02,
        'NP' => 0x04,
        'IO' => 0x08,
        'ID' => 0x10,
        'SA' => 0x40,
        'FA' => 0x80,
    ];

    /**
     * Check or set whether the ACE does not control access to the object to which it is attached. When this is true,
     * the ACE only controls access on those objects which inherit it.
     *
     * @param null $action
     * @return $this|bool
     */
    public function inheritOnly($action = null)
    {
        return $this->hasOrSet(self::FLAG['INHERIT_ONLY'], $action);
    }

    /**
     * Check or set whether inheritance of this ACE should be propagated.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function propagateInheritance($action = null)
    {
        // The bit means the opposite, so flip it depending on the context (same for the return).
        $action = is_null($action) ? $action : !((bool) $action);
        $result = $this->hasOrSet(self::FLAG['NO_PROPAGATE_INHERIT'], $action);

        return is_null($action) ? !$result : $result;
    }

    /**
     * Check or set whether containers should inherit this ACE.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function containerInherit($action = null)
    {
        return $this->hasOrSet(self::FLAG['CONTAINER_INHERIT'], $action);
    }

    /**
     * Check or set whether objects should inherit this ACE.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function objectInherit($action = null)
    {
        return $this->hasOrSet(self::FLAG['OBJECT_INHERIT'], $action);
    }

    /**
     * Whether or not the ACE should generate audit messages for failed access attempts (only valid in the SACL).
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function auditFailedAccess($action = null)
    {
        return $this->hasOrSet(self::FLAG['FAILED_ACCESS'], $action);
    }

    /**
     * Whether or not the ACE should generate audit messages for successful access attempts (only valid in the SACL).
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function auditSuccessfulAccess($action = null)
    {
        return $this->hasOrSet(self::FLAG['SUCCESSFUL_ACCESS'], $action);
    }

    /**
     * Check whether or not the ACE is inherited.
     *
     * @return bool
     */
    public function isInherited()
    {
        return $this->has(self::FLAG['INHERITED']);
    }
}

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
 * Represents access rights on an ACE.
 *
 * @see https://msdn.microsoft.com/en-us/library/aa772285(v=vs.85).aspx
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class AceRights extends Flags
{
    use FlagsSddlTrait;

    /**
     * Some of the possible/useful extended rights for a control access right.
     *
     * @see https://msdn.microsoft.com/en-us/library/ms683985(v=vs.85).aspx
     */
    const EXTENDED = [
        'APPLY_GROUP_POLICY' => 'edacfd8f-ffb3-11d1-b41d-00a0c968f939',
        'REANIMATE_TOMBSTONES' => '45ec5156-db7e-47bb-b53f-dbeb2d03c40f',
        'CHANGE_PASSWORD' => 'ab721a53-1e2f-11d0-9819-00aa0040529b',
        'RESET_PASSWORD' => '00299570-246d-11d0-a768-00aa006e0529',
        'EXCHANGE_SEND_AS' => 'ab721a54-1e2f-11d0-9819-00aa0040529b',
        'EXCHANGE_SEND_TO' => 'ab721a55-1e2f-11d0-9819-00aa0040529b',
        'EXCHANGE_RECEIVE_AS' => 'ab721a56-1e2f-11d0-9819-00aa0040529b',
    ];

    /**
     * AceRights for DS
     */
    const FLAG = [
        'DELETE' => 0x00010000,
        'READ_CONTROL' => 0x00020000,
        'WRITE_DACL' => 0x00040000,
        'WRITE_OWNER' => 0x00080000,
        'SYNCHRONIZE' => 0x00100000,
        'ACCESS_SYSTEM_SECURITY' => 0x01000000,
        'MAXIMUM_ALLOWED' => 0x02000000,
        'GENERIC_READ' => 0x80000000,
        'GENERIC_WRITE' => 0x40000000,
        'GENERIC_EXECUTE' => 0x20000000,
        'GENERIC_ALL' => 0x10000000,
        'DS_CREATE_CHILD' => 0x00000001,
        'DS_DELETE_CHILD' => 0x00000002,
        'ACTRL_DS_LIST' => 0x00000004,
        'DS_SELF' => 0x00000008,
        'DS_READ_PROP' => 0x00000010,
        'DS_WRITE_PROP' => 0x00000020,
        'DS_DELETE_TREE' => 0x00000040,
        'DS_LIST_OBJECT' => 0x00000080,
        'DS_CONTROL_ACCESS' => 0x00000100,
    ];

    /**
     * SDDL short names for the ace rights.
     */
    const SHORT_NAME = [
        'DE' => 0x00010000,
        'RC' => 0x00020000,
        'WD' => 0x00040000,
        'WO' => 0x00080000,
        'SY' => 0x00100000,
        'AS' => 0x01000000,
        'MA' => 0x02000000,
        'GR' => 0x80000000,
        'GW' => 0x40000000,
        'GX' => 0x20000000,
        'GA' => 0x10000000,
        'CC' => 0x00000001,
        'DC' => 0x00000002,
        'LC' => 0x00000004,
        'SW' => 0x00000008,
        'RP' => 0x00000010,
        'WP' => 0x00000020,
        'DT' => 0x00000040,
        'LO' => 0x00000080,
        'CR' => 0x00000100,
    ];

    /**
     * Check or set the ability to perform a delete-tree operation on the object.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function deleteTree($action = null)
    {
        return $this->hasOrSet(self::FLAG['DS_DELETE_TREE'], $action);
    }

    /**
     * Check or set the ability to read a specific property.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function readProperty($action = null)
    {
        return $this->hasOrSet(self::FLAG['DS_READ_PROP'], $action);
    }

    /**
     * Check or set the ability to write a specific property.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function writeProperty($action = null)
    {
        return $this->hasOrSet(self::FLAG['DS_WRITE_PROP'], $action);
    }

    /**
     * Check or set the ability to create child objects.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function createChildObject($action = null)
    {
        return $this->hasOrSet(self::FLAG['DS_CREATE_CHILD'], $action);
    }

    /**
     * Check or set the ability to delete child objects.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function deleteChildObject($action = null)
    {
        return $this->hasOrSet(self::FLAG['DS_DELETE_CHILD'], $action);
    }

    /**
     * Check or set the ability to list child objects.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function listChildObject($action = null)
    {
        return $this->hasOrSet(self::FLAG['ACTRL_DS_LIST'], $action);
    }

    /**
     * Check or set the ability to delete the object.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function deleteObject($action = null)
    {
        return $this->hasOrSet(self::FLAG['DELETE'], $action);
    }

    /**
     * Check or set the ability to list objects of a specific type.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function listObject($action = null)
    {
        return $this->hasOrSet(self::FLAG['DS_LIST_OBJECT'], $action);
    }

    /**
     * Check or set the ability to perform a validated write for a property.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function validatedWrite($action = null)
    {
        return $this->hasOrSet(self::FLAG['DS_SELF'], $action);
    }

    /**
     * Check or set control access rights. These control specific actions/operations on an object or attribute.
     *
     * @see https://msdn.microsoft.com/en-us/library/cc223512.aspx
     * @param null|bool $action
     * @return $this|bool
     */
    public function controlAccess($action = null)
    {
        return $this->hasOrSet(self::FLAG['DS_CONTROL_ACCESS'], $action);
    }

    /**
     * Check or set the ability to read data from the security descriptor (minus the SACL).
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function readSecurity($action = null)
    {
        return $this->hasOrSet(self::FLAG['READ_CONTROL'], $action);
    }

    /**
     * Check or set the ability to access the SACL of an object.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function accessSacl($action = null)
    {
        return $this->hasOrSet(self::FLAG['ACCESS_SYSTEM_SECURITY'], $action);
    }

    /**
     * Check or set the ability to write the DACL of an object.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function writeDacl($action = null)
    {
        return $this->hasOrSet(self::FLAG['WRITE_DACL'], $action);
    }

    /**
     * Check or set the ability to assume ownership of the object. The user must be an object trustee. The user cannot
     * transfer the ownership to other users.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function writeOwner($action = null)
    {
        return $this->hasOrSet(self::FLAG['WRITE_OWNER'], $action);
    }

    /**
     * Check or set the ability to read permissions on this object, read all the properties on this object, list this
     * object name when the parent container is listed, and list the contents of this object if it is a container.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function readAll($action = null)
    {
        return $this->hasOrSet(self::FLAG['GENERIC_READ'], $action);
    }

    /**
     * Check or set the ability to read permissions on this object, write all the properties on this object, and perform
     * all validated writes to this object.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function writeAll($action = null)
    {
        return $this->hasOrSet(self::FLAG['GENERIC_WRITE'], $action);
    }

    /**
     * Check or set the ability to read permissions on, and list the contents of, a container object.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function execute($action = null)
    {
        return $this->hasOrSet(self::FLAG['GENERIC_EXECUTE'], $action);
    }

    /**
     * Check or set the ability to create or delete child objects, delete a subtree, read and write properties, examine
     * child objects and the object itself, add and remove the object from the directory, and read or write with an
     * extended right.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function fullControl($action = null)
    {
        return $this->hasOrSet(self::FLAG['GENERIC_ALL'], $action);
    }

    /**
     * Check or set the ability to use the object for synchronization. This enables a thread to wait until the
     * object is in the signaled state.
     *
     * @param null|bool $action
     * @return $this|bool
     */
    public function synchronize($action = null)
    {
        return $this->hasOrSet(self::FLAG['SYNCHRONIZE'], $action);
    }
}

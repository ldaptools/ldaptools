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
 * Represents a LDAP operation to rename an object.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class RenameOperation implements LdapOperationInterface
{
    use LdapOperationTrait;

    /**
     * @var array
     */
    protected $properties = [
        'dn' => null,
        'newLocation' => null,
        'newRdn' => null,
        'deleteOldRdn' => true,
    ];

    /**
     * @param string $dn The DN of the LDAP object to rename.
     * @param null|string $newRdn The new RDN in the form of "CN=NewName"
     * @param null|string $newLocation The new container/OU it should be placed. Leave null if only changing the RDN.
     * @param bool $deleteOldRdn Whether the old RDN should be deleted.
     */
    public function __construct($dn, $newRdn = null, $newLocation = null, $deleteOldRdn = true)
    {
        $this->properties['dn'] = $dn;
        $this->properties['newLocation'] = $newLocation;
        $this->properties['newRdn'] = $newRdn;
        $this->properties['deleteOldRdn'] = $deleteOldRdn;
    }

    /**
     * The distinguished name for an add, delete, or move operation.
     *
     * @return null|string
     */
    public function getDn()
    {
        return $this->properties['dn'];
    }

    /**
     * Set the distinguished name that the operation is working on.
     *
     * @param string $dn
     * @return $this
     */
    public function setDn($dn)
    {
        $this->properties['dn'] = $dn;

        return $this;
    }

    /**
     * Get the DN of the container/ou/parent that the object will be moved to.
     *
     * @return null|string
     */
    public function getNewLocation()
    {
        return $this->properties['newLocation'];
    }

    /**
     * Set the DN of the container/ou/parent that the object will be moved to. If the object is not moving, but rather
     * just being renamed, then this should be set to null.
     *
     * @param null|string $dn
     * @return $this
     */
    public function setNewLocation($dn)
    {
        $this->properties['newLocation'] = $dn;

        return $this;
    }

    /**
     * Get whether or not the old RDN is removed after the operation.
     *
     * @return null|bool
     */
    public function getDeleteOldRdn()
    {
        return $this->properties['deleteOldRdn'];
    }

    /**
     * Set whether or not the old RDN is removed after the operation. If your intent is to move the object, then this
     * should be set to true typically.
     *
     * @param bool $delete
     * @return $this
     */
    public function setDeleteOldRdn($delete)
    {
        $this->properties['deleteOldRdn'] = $delete;

        return $this;
    }

    /**
     * Set the new RDN that the LDAP object will have (ie. "cn=Foo")
     *
     * @param string $rdn
     * @return $this
     */
    public function setNewRdn($rdn)
    {
        $this->properties['newRdn'] = $rdn;

        return $this;
    }

    /**
     * Get the new RDN that the LDAP object will have.
     *
     * @return string
     */
    public function getNewRdn()
    {
        return $this->properties['newRdn'];
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapFunction()
    {
        return 'ldap_rename';
    }

    /**
     * {@inheritdoc}
     */
    public function getArguments()
    {
        return [
            $this->properties['dn'],
            $this->properties['newRdn'],
            $this->properties['newLocation'],
            $this->properties['deleteOldRdn']
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Rename';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogArray()
    {
        return $this->mergeLogDefaults([
            'DN' => $this->properties['dn'],
            'New Location' => $this->properties['newLocation'],
            'New RDN' => $this->properties['newRdn'],
            'Delete Old RDN' => var_export($this->properties['deleteOldRdn'], true),
        ]);
    }
}

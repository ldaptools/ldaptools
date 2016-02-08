<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Ldif\Entry;

use LdapTools\Operation\RenameOperation;
use LdapTools\Utilities\LdapUtilities;

/**
 *  Represents a LDIF entry to modify the DN of an existing LDAP object (ie. move or rename it).
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdifEntryModDn implements LdifEntryInterface
{
    use LdifEntryTrait;

    /**
     * The directive for the new location of the DN.
     */
    const DIRECTIVE_NEWSUPERIOR = 'newsuperior';

    /**
     * The directive for the new DN of the entry.
     */
    const DIRECTIVE_NEWRDN = 'newrdn';

    /**
     * The directive for whether the old RDN should be deleted.
     */
    const DIRECTIVE_DELETEOLDRDN = 'deleteoldrdn';

    /**
     * @var bool Whether the old RDN should be deleted.
     */
    protected $deleteOldRdn;

    /**
     * @var string The new RDN.
     */
    protected $newRdn;

    /**
     * @var string The new location (ou/container) for the LDAP object.
     */
    protected $newSuperior;

    /**
     * @param string $dn
     * @param null|string $newLocation The new container/OU for the LDAP object.
     * @param null|string $newRdn The new RDN for the LDAP object.
     * @param bool $deleteOldRdn Whether the old RDN should be deleted.
     */
    public function __construct($dn, $newLocation = null, $newRdn = null, $deleteOldRdn = true)
    {
        $this->dn = $dn;
        $this->newRdn = $newRdn;
        $this->newSuperior = $newLocation;
        $this->deleteOldRdn = $deleteOldRdn;
        $this->changeType = self::TYPE_MODDN;
    }

    /**
     * Set the new location (OU/container) for the LDAP object.
     *
     * @param string $location
     * @return $this
     */
    public function setNewLocation($location)
    {
        $this->newSuperior = $location;

        return $this;
    }

    /**
     * Get the new location (OU/container) for the LDAP object.
     *
     * @return string
     */
    public function getNewLocation()
    {
        return $this->newSuperior;
    }

    /**
     * Set the new RDN (ie. name) for the LDAP object (in the form of "CN=NewName" or similar).
     *
     * @param string $rdn
     * @return $this
     */
    public function setNewRdn($rdn)
    {
        $this->newRdn = $rdn;

        return $this;
    }

    /**
     * Get the new RDN for the LDAP object.
     *
     * @return string
     */
    public function getNewRdn()
    {
        return $this->newRdn;
    }

    /**
     * Set whether the old RDN should be deleted.
     *
     * @param bool $deleteOldRdn
     * @return $this
     */
    public function setDeleteOldRdn($deleteOldRdn)
    {
        $this->deleteOldRdn = (bool) $deleteOldRdn;

        return $this;
    }

    /**
     * Get whether the old RDN should be deleted.
     *
     * @return bool
     */
    public function getDeleteOldRdn()
    {
        return $this->deleteOldRdn;
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        $ldif = $this->getCommonString();
        if (!is_null($this->newRdn)) {
            $ldif .= $this->getLdifLine(self::DIRECTIVE_NEWRDN, $this->newRdn);
        }
        if (!is_null($this->newSuperior)) {
            $ldif .= $this->getLdifLine(self::DIRECTIVE_NEWSUPERIOR, $this->newSuperior);
        }
        $ldif .= $this->getLdifLine(self::DIRECTIVE_DELETEOLDRDN, $this->deleteOldRdn ? '1' : '0');

        return $ldif;
    }

    /**
     * {@inheritdoc}
     */
    public function toOperation()
    {
        // If this is a move operation to a new OU and we have a DN already, then we can figure out the RDN.
        if (is_null($this->newRdn) && !is_null($this->newSuperior)) {
            $rdn = LdapUtilities::getRdnFromDn($this->dn);
        } else {
            $rdn = $this->newRdn;
        }

        return new RenameOperation(
            $this->dn,
            $rdn,
            $this->newSuperior,
            $this->deleteOldRdn
        );
    }
}

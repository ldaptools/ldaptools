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

/**
 *  Represents a LDIF entry to modify the RDN of an existing LDAP object (ie. move or rename it).
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdifEntryModRdn extends LdifEntryModDn
{
    /**
     * @param string $dn
     * @param null|string $newLocation The new container/OU for the LDAP object.
     * @param null|string $newRdn The new RDN for the LDAP object.
     * @param bool $deleteOldRdn Whether the old RDN should be deleted.
     */
    public function __construct($dn, $newLocation = null, $newRdn = null, $deleteOldRdn = true)
    {
        parent::__construct($dn, $newLocation, $newRdn, $deleteOldRdn);
        $this->changeType = self::TYPE_MODRDN;
    }
}

<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Ldif;

use LdapTools\Ldif\Entry\LdifEntryAdd;
use LdapTools\Ldif\Entry\LdifEntryDelete;
use LdapTools\Ldif\Entry\LdifEntryModDn;
use LdapTools\Ldif\Entry\LdifEntryModify;
use LdapTools\Ldif\Entry\LdifEntryModRdn;

/**
 * Used as a helper for more fluid building of LDIF objects.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdifEntryBuilder
{
    /**
     * Create a LDIF add entry type.
     *
     * @param string $dn
     * @param array $attributes The attribute [key => value] array to be added.
     * @return LdifEntryAdd
     */
    public function add($dn = null, array $attributes = [])
    {
        return new LdifEntryAdd($dn, $attributes);
    }

    /**
     * Create a LDIF delete entry type.
     *
     * @param string $dn
     * @return LdifEntryDelete
     */
    public function delete($dn)
    {
        return new LdifEntryDelete($dn);
    }

    /**
     * Create a LDIF modify entry type.
     *
     * @param $dn
     * @return LdifEntryModify
     */
    public function modify($dn)
    {
        return new LdifEntryModify($dn);
    }

    /**
     * Create a LDIF entry to modify the DN of a LDAP object.
     *
     * @param string $dn The DN to modify.
     * @return LdifEntryModDn
     */
    public function moddn($dn)
    {
        return new LdifEntryModDn($dn, null, false);
    }

    /**
     * Create a LDIF entry to rename a LDAP object (the RDN).
     *
     * @param string $dn
     * @param string $name The new name.
     * @param bool $deleteOldRdn Whether or not the old RDN should be deleted.
     * @return LdifEntryModRdn
     */
    public function rename($dn, $name, $deleteOldRdn = false)
    {
        return new LdifEntryModRdn($dn, null, $name, $deleteOldRdn);
    }

    /**
     * Create a LDIF entry to move a LDAP object to a new OU/container.
     *
     * @param string $dn The DN for the LDAP object to move.
     * @param string $newLocation The DN of the new location.
     * @param bool $deleteOldRdn Whether or not the old RDN should be deleted.
     * @return LdifEntryModDn
     */
    public function move($dn, $newLocation, $deleteOldRdn = true)
    {
        return new LdifEntryModDn($dn, $newLocation, null, $deleteOldRdn);
    }
}

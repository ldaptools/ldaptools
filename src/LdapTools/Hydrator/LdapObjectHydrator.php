<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Hydrator;

use LdapTools\Object\LdapObject;
use LdapTools\Object\LdapObjectCollection;

/**
 * Hydrates a LDAP entry into a LdapObject or LdapObjectCollection form.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapObjectHydrator extends ArrayHydrator
{
    /**
     * {@inheritdoc}
     */
    public function hydrateFromLdap(array $entry)
    {
        $entry = parent::hydrateFromLdap($entry);

        return new LdapObject($entry, $this->schema ? $this->schema->getObjectType() : '');
    }

    /**
     * Hydrates an array of LDAP entries in a LdapObjectCollection.
     *
     * @param array $entries
     * @return LdapObjectCollection
     */
    public function hydrateAllFromLdap(array $entries)
    {
        return new LdapObjectCollection(...parent::hydrateAllFromLdap($entries));
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateToLdap($ldapObject, $dn = null)
    {
        if (!($ldapObject instanceof LdapObject)) {
            throw new \InvalidArgumentException('Expects a LdapObject instance to convert batch modifications to LDAP.');
        }
        if (!$this->schema) {
            return $ldapObject->getBatchCollection()->getBatchArray();
        }

        $batches = $this->convertValuesToLdap($ldapObject->getBatchCollection(), $ldapObject->get('dn'));
        foreach ($batches as $batch) {
            /** @var \LdapTools\BatchModify\Batch $batch */
            $batch->setAttribute(
                $this->schema->getAttributeToLdap($batch->getAttribute())
            );
        }

        return $batches->getBatchArray();
    }
}

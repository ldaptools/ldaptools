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
        $schema = empty($this->schemas) ? null : $this->getSchema();

        $class = $schema ? $this->getSchema()->getObjectClass() : [];
        $category = $schema ? $this->getSchema()->getObjectCategory() : '';
        $type = $schema ? $this->getSchema()->getObjectType() : '';

        return new LdapObject(
            $entry,
            is_array($class) ? $class : [$class],
            $category,
            $type
        );
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateAllFromLdap(array $entries)
    {
        $collection = new LdapObjectCollection();

        for ($i = 0; $i < $entries['count']; $i++) {
            $collection->add($this->hydrateFromLdap($entries[$i]));
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateToLdap($ldapObject)
    {
        if (!($ldapObject instanceof LdapObject)) {
            throw new \InvalidArgumentException('Expects a LdapObject instance to convert data to LDAP.');
        }

        return parent::hydrateToLdap($ldapObject->toArray());
    }
}

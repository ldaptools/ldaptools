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
class LdapObjectHydrator implements HydratorInterface
{
    use HydratorTrait {
        hydrateFromLdap as hydrateFromLdapToArray;
        hydrateAllFromLdap as hydrateAllFromLdapToObjects;
        hydrateToLdap as hydrateToLdapWithArray;
        hydrateBatchToLdap as hydrateBatchToLdapWithArray;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateFromLdap(array $entry)
    {
        $entry = $this->hydrateFromLdapToArray($entry);
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
     * Hydrates an array of LDAP entries in a LdapObjectCollection.
     *
     * @param array $entries
     * @return LdapObjectCollection
     */
    public function hydrateAllFromLdap(array $entries)
    {
        $collection = new LdapObjectCollection();
        $ldapObjects = $this->hydrateAllFromLdapToObjects($entries);
        $collection->add(...$ldapObjects);

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateBatchToLdap($ldapObject, $dn = null)
    {
        if (!($ldapObject instanceof LdapObject)) {
            throw new \InvalidArgumentException('Expects a LdapObject instance to convert batch modifications to LDAP.');
        }

        return $this->hydrateBatchToLdapWithArray($ldapObject->getBatchModifications(), $ldapObject->get('dn'));
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateToLdap($ldapObject)
    {
        if (!($ldapObject instanceof LdapObject)) {
            throw new \InvalidArgumentException('Expects a LdapObject instance to convert data to LDAP.');
        }

        return $this->hydrateToLdapWithArray($ldapObject->toArray());
    }
}

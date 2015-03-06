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
    public function hydrateToLdap($ldapObject, $dn = null)
    {
        if (!($ldapObject instanceof LdapObject)) {
            throw new \InvalidArgumentException('Expects a LdapObject instance to convert batch modifications to LDAP.');
        }

        $batches = $this->convertValuesToLdap($ldapObject->getBatchCollection(), $ldapObject->get('dn'));
        foreach ($batches as $batch) {
            /** @var \LdapTools\BatchModify\Batch $batch */
            $batch->setAttribute(
                $this->getSchema()->getAttributeToLdap($batch->getAttribute())
            );
        }

        return $batches->getBatchArray();
    }
}

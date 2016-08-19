<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Resolver;

use LdapTools\Schema\LdapObjectSchema;
use LdapTools\Utilities\MBString;

/**
 * Resolves names for a LDAP entry going to and from LDAP so they are the correct case/name.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class AttributeNameResolver
{
    /**
     * @var array The attribute names in the exact form they were selected for.
     */
    protected $selectedAttributes = [];

    /**
     * @var LdapObjectSchema|null
     */
    protected $schema;

    /**
     * @param LdapObjectSchema $schema
     */
    public function __construct(LdapObjectSchema $schema = null)
    {
        $this->schema = $schema;
    }

    /**
     * Transform the LDAP attribute names into what is expected given the current context (schema or not).
     *
     * @param array $entry The LDAP entry.
     * @param array $selectedAttributes The attributes that were selected.
     * @return array
     */
    public function fromLdap(array $entry, array $selectedAttributes)
    {
        $this->selectedAttributes = $this->getSelectedAttributes($selectedAttributes, $entry);

        $newEntry = [];
        foreach ($entry as $attribute => $value) {
            if ($this->schema) {
                $newEntry = $this->setMappedNames($newEntry, $attribute, $value);
            }
            // If the LDAP attribute name was also explicitly selected for, and is not already in the array, add it...
            if ($this->selectedButNotPartOfEntry($attribute, $newEntry)) {
                $newEntry[MBString::array_search_get_value($attribute, $this->selectedAttributes)] = $value;
            }
        }
        // The DN attribute must be present as it is used in many critical functions.
        $newEntry = $this->addDnFromLdapIfNotPresent($newEntry, $entry);

        return $newEntry;
    }

    /**
     * Convert values to LDAP.
     *
     * @param array $entry The LDAP entry.
     * @return array
     */
    public function toLdap(array $entry)
    {
        $toLdap = [];

        foreach ($entry as $attribute => $value) {
            $toLdap[$this->schema->getAttributeToLdap($attribute)] = $value;
        }

        return $toLdap;
    }

    /**
     * The DN attribute is returned by PHP on all LDAP search operations, regardless of selected attributes, and is used
     * in many functions. So add it to the results even if it wasn't selected for.
     *
     * @param array $newEntry
     * @param array $entry
     * @return array
     */
    protected function addDnFromLdapIfNotPresent(array $newEntry, array $entry)
    {
        if (!isset($newEntry['dn']) && isset($entry['dn'])) {
            $newEntry['dn'] = $entry['dn'];
        }

        return $newEntry;
    }

    /**
     * Check whether the attribute name was selected to be returned but is not yet part of the entry. Adjusts the check
     * to be case insensitive.
     *
     * @param string $attribute
     * @param array $entry
     * @return bool
     */
    protected function selectedButNotPartOfEntry($attribute, array $entry)
    {
        $lcAttribute = MBString::strtolower($attribute);

        $inSelectedAttributes = in_array($lcAttribute, MBString::array_change_value_case($this->selectedAttributes));
        $existsInEntry = array_key_exists($lcAttribute, MBString::array_change_key_case($entry));

        return ($inSelectedAttributes && !$existsInEntry);
    }

    /**
     * Set all the names mapped to a single attribute from LDAP. This helps account for multiple mappings used for
     * different purposes.
     *
     * @param array $newEntry
     * @param string $attribute
     * @param array|string $value
     * @return mixed
     */
    protected function setMappedNames(array $newEntry, $attribute, $value)
    {
        // Get all names mapped to this LDAP attribute name...
        if (!$this->schema->hasNamesMappedToAttribute($attribute)) {
            return $newEntry;
        }

        $mappedNames = $this->schema->getNamesMappedToAttribute($attribute);
        foreach ($mappedNames as $mappedName) {
            // Any names specifically selected for should be in the result array...
            if ($this->selectedButNotPartOfEntry($mappedName, $newEntry)) {
                $newEntry[MBString::array_search_get_value($mappedName, $this->selectedAttributes)] = $value;
            }
        }

        return $newEntry;
    }

    /**
     * Determine what attributes should be selected. This accounts for a query wanting all attributes.
     *
     * @param array $selected
     * @param array $entry
     * @return array
     */
    protected function getSelectedAttributes(array $selected, array $entry)
    {
        if (count($selected) === 1 && $selected[0] == '*' && !$this->schema) {
            $selected = array_keys($entry);
        } elseif (count($selected) === 1 && $selected[0] == '*' && $this->schema) {
            $selected = array_unique(array_merge(array_keys($this->schema->getAttributeMap()), array_keys($entry)));
        }

        return $selected;
    }
}

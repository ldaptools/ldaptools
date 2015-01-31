<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Query\Hydrator;

use LdapTools\Factory\AttributeConverterFactory;
use LdapTools\Schema\LdapObjectSchema;

/**
 * Common Hydrator functions.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait HydratorTrait
{
    /**
     * @var array An array of LdapObjectSchemas
     */
    protected $schemas = [];

    /**
     * @var array The attributes selected for in the query.
     */
    protected $selectedAttributes = [];

    /**
     * @see HydratorInterface::setLdapObjectSchema()
     */
    public function setLdapObjectSchemas(LdapObjectSchema ...$ldapObjectSchema)
    {
        $this->schemas = $ldapObjectSchema;
    }

    /**
     * @see HydratorInterface::getLdapObjectSchemas()
     */
    public function getLdapObjectSchemas()
    {
        return $this->schemas;
    }

    /**
     * @see HydratorInterface::setSelectedAttributes()
     */
    public function setSelectedAttributes(array $attributes)
    {
        $this->selectedAttributes = $attributes;
    }

    /**
     * @see HydratorInterface::getSelectedAttributes()
     */
    public function getSelectedAttributes()
    {
        return $this->selectedAttributes;
    }

    /**
     * Replace the LDAP attribute names with the schema names if there is a schema present.
     *
     * @param array $entry
     * @return array
     */
    protected function setAttributesFromSchema(array $entry)
    {
        if (empty($this->schemas)) {
            return $entry;
        }

        $newEntry = [];
        foreach ($entry as $attribute => $value) {
            $mappedNames = $this->schemas[0]->getNamesMappedToAttribute($attribute);
            // Get all names mapped to this LDAP attribute name...
            if ($this->schemas[0]->hasNamesMappedToAttribute($attribute)) {
                foreach ($mappedNames as $mappedName) {
                    // Any names specifically selected for should be in the result array...
                    if ($this->selectedButNotPartOfEntry($mappedName, $newEntry)) {
                        $newEntry[$this->getAttributeNameAsRequested($mappedName)] = $value;
                    }
                }
            }
            // If the LDAP attribute name was also explicitly selected for, and is not already in the array, add it...
            if ($this->selectedButNotPartOfEntry($attribute, $newEntry)) {
                $newEntry[$this->getAttributeNameAsRequested($attribute)] = $value;
            }
        }

        return $newEntry;
    }

    /**
     * Replace attribute values with the converted values if the attribute has a converter defined.
     *
     * @param array $entry
     * @return array
     */
    protected function convertValuesFromLdap(array $entry)
    {
        if (empty($this->schemas)) {
            return $entry;
        }

        $schema = $this->schemas[0];
        foreach ($entry as $attribute => $value) {
            if ($schema->hasConverter($attribute)) {
                $converter = $schema->getConverter($attribute);
                $entry[$attribute] = AttributeConverterFactory::get($converter)->fromLdap($value);
            }
        }

        return $entry;
    }

    /**
     * Returns the key for the attribute in the exact way it was selected for, regardless of how LDAP returns it.
     *
     * @param string $attribute
     * @return string
     */
    protected function getAttributeNameAsRequested($attribute)
    {
        // Is there a more efficient way of doing this?
        $lcAttribute = strtolower($attribute);
        foreach ($this->selectedAttributes as $selectedAttribute) {
            if ($lcAttribute == strtolower($selectedAttribute)) {
                return $selectedAttribute;
            }
        }
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
        $lcAttribute = strtolower($attribute);

        $inSelectedAttributes = in_array($lcAttribute, array_map('strtolower', $this->selectedAttributes));
        $existsInEntry = array_key_exists($lcAttribute, array_change_key_case($entry));

        return ($inSelectedAttributes && !$existsInEntry);
    }
}

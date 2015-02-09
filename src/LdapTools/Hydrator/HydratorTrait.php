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

use LdapTools\Factory\AttributeConverterFactory;
use LdapTools\ParameterResolver;
use LdapTools\Schema\LdapObjectSchema;

/**
 * Common Hydrator functions.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait HydratorTrait
{
    /**
     * @var LdapObjectSchema[]
     */
    protected $schemas = [];

    /**
     * @var array The attributes selected for in the query.
     */
    protected $selectedAttributes = [];

    /**
     * @var array Default parameter values that have been set.
     */
    protected $parameters = [];

    /**
     * Sets a parameter that can be used within an attribute value.
     *
     * @param string $name
     * @param string $value
     */
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Get the array of additional possible parameters that have been set for the hydrator.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

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
    protected function convertNamesFromLdap(array $entry)
    {
        if (empty($this->schemas)) {
            return $entry;
        }

        $newEntry = [];
        $schema = $this->getSchema();
        foreach ($entry as $attribute => $value) {
            $mappedNames = $schema->getNamesMappedToAttribute($attribute);
            // Get all names mapped to this LDAP attribute name...
            if ($schema->hasNamesMappedToAttribute($attribute)) {
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

        $schema= $this->getSchema();
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
        $lcAttribute = strtolower($attribute);

        return $this->selectedAttributes[array_change_key_case(array_flip($this->selectedAttributes))[$lcAttribute]];
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

    /**
     * Returns all of the attributes to be sent to LDAP after factoring in possible default schema values.
     *
     * @param array $attributes
     * @return array
     */
    protected function mergeDefaultAttributes(array $attributes)
    {
        $defaults = empty($this->schemas) ? [] : $this->getSchema()->getDefaultValues();

        return array_filter(array_merge($defaults, $attributes));
    }

    /**
     * Checks to make sure all required attributes are present.
     *
     * @param array $attributes
     */
    protected function validateAttributesToLdap(array $attributes)
    {
        if (empty($this->schemas)) {
            return;
        }
        $missing = [];

        foreach ($this->getSchema()->getRequiredAttributes() as $attribute) {
            if (!array_key_exists(strtolower($attribute), array_change_key_case($attributes))) {
                $missing[] = $attribute;
            }
        }

        if (!empty($missing)) {
            throw new \LogicException(
                sprintf('The following required attributes are missing: %s', implode(', ', $missing))
            );
        }
    }

    /**
     * Checks for attributes assigned an attribute converter. It will replace the value with the converted value then
     * send back all the attributes.
     *
     * @param array $attributes
     * @return array
     */
    protected function convertValuesToLdap(array $attributes)
    {
        if (empty($this->schemas)) {
            return $attributes;
        }

        foreach ($attributes as $attribute => $value) {
            if ($this->getSchema()->hasConverter($attribute)) {
                $converter = $this->getSchema()->getConverter($attribute);
                $attributes[$attribute] = AttributeConverterFactory::get($converter)->toLdap($value);
            }
        }

        return $attributes;
    }

    /**
     * Converts attribute names from their schema defined value to the value LDAP needs them in.
     *
     * @param array $attributes
     * @return array
     */
    protected function convertNamesToLdap(array $attributes)
    {
        if (empty($this->schemas)) {
            return $attributes;
        }
        $toLdap = [];

        foreach ($attributes as $attribute => $value) {
            $toLdap[$this->getSchema()->getAttributeToLdap($attribute)] = $value;
        }

        return $toLdap;
    }

    /**
     * @return LdapObjectSchema
     */
    protected function getSchema()
    {
        if (1 == count($this->schemas)) {
            return $this->schemas[0];
        } else {
            throw new \RuntimeException('Using only one LDAP object type is currently supported.');
        }
    }

    /**
     * Resolves all parameters within an array of attributes.
     *
     * @param array $attributes
     * @return array
     */
    protected function resolveParameters(array $attributes)
    {
        return (new ParameterResolver($attributes, $this->parameters))->resolve();
    }
}

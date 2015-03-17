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

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\BatchModify\BatchCollection;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Query\LdapResultSorter;
use LdapTools\Resolver\AttributeValueResolver;
use LdapTools\Resolver\BaseValueResolver;
use LdapTools\Resolver\ParameterResolver;
use LdapTools\Schema\LdapObjectSchema;

/**
 * Common Hydrator functions. The default functionality is to hydrate to/from arrays.
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
     * @var int The operation type that is requesting this hydration process.
     */
    protected $type = AttributeConverterInterface::TYPE_SEARCH_FROM;

    /**
     * @var LdapConnectionInterface|null
     */
    protected $connection;

    /**
     * @var array The attributes to order by.
     */
    protected $orderBy = [];

    /**
     * Set the attributes to sort by.
     *
     * @param array $orderBy
     */
    public function setOrderBy(array $orderBy)
    {
        $this->orderBy = $orderBy;
    }

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
     * @see HydratorInterface::hydrateFromLdap()
     */
    public function hydrateFromLdap(array $entry)
    {
        $attributes = [];

        foreach ($entry as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $attributes = $this->setAttributeFromLdap($attributes, $key, $value);
        }
        $attributes = $this->convertNamesFromLdap($attributes);
        $attributes = $this->convertValuesFromLdap($attributes);

        return $attributes;
    }

    /**
     * @see HydratorInterface::hydrateAllFromLdap()
     */
    public function hydrateAllFromLdap(array $entries)
    {
        $results = [];
        $entryCount = isset($entries['count']) ? $entries['count'] : 0;

        for ($i = 0; $i < $entryCount; $i++) {
            $results[] = $this->hydrateFromLdap($entries[$i]);
        }
        if (!empty($this->orderBy)) {
            $results = $this->sortResults($results);
        }

        return $results;
    }

    /**
     * @see HydratorInterface::hydrateToLdap()
     */
    public function hydrateToLdap($attributes)
    {
        if (!is_array($attributes)) {
            throw new \InvalidArgumentException('Expects an array to convert data to LDAP.');
        }
        $attributes = $this->mergeDefaultAttributes($attributes);
        $this->validateAttributesToLdap($attributes);
        $attributes = $this->resolveParameters($attributes);
        $attributes = $this->convertValuesToLdap($attributes);
        $attributes = $this->convertNamesToLdap($attributes);

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function setOperationType($type)
    {
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function setLdapConnection(LdapConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Takes a LDAP result set and passes it on to the sorter. Makes sure that orderBy attributes are in the correct
     * case first, if possible.
     *
     * @param array $results
     * @return array
     */
    protected function sortResults(array $results)
    {

        $orderBy = [];
        if (!empty($this->schemas)) {
            // Ignore case differences to ease later comparisons.
            foreach ($this->orderBy as $attribute => $direction) {
                $orderBy[$this->getAttributeNameAsRequested($attribute)] = $direction;
            }
        } else {
            $orderBy = $this->orderBy;
        }

        return (new LdapResultSorter($orderBy))->sort($results);
    }

    /**
     * Given a specific attribute and value add it to the newly formed LDAP entry array.
     *
     * @param array $entry
     * @param string $attribute
     * @param string|array $value
     * @return array
     */
    protected function setAttributeFromLdap(array $entry, $attribute, $value)
    {
        if (isset($value['count']) && $value['count'] == 1) {
            $entry[$attribute] = $value[0];
        } elseif (isset($value['count']) && $value['count'] > 0) {
            $entry[$attribute] = [];
            for ($i = 0; $i < $value['count']; $i++) {
                $entry[$attribute][] = $value[$i];
            }
        } elseif ($attribute === 'dn') {
            $entry[$attribute] = $value;
        }

        return $entry;
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
        // The DN attribute must be present as it is used in many critical functions.
        $newEntry = $this->addDnFromLdapIfNotPresent($newEntry, $entry);

        return $newEntry;
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
        $valueResolver = new AttributeValueResolver(
            $this->getSchema(),
            $entry,
            $this->type
        );
        $this->configureValueResolver($valueResolver);

        return $valueResolver->fromLdap();
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
     * @param array|BatchCollection $values
     * @param string|null $dn
     * @return array|BatchCollection
     */
    protected function convertValuesToLdap($values, $dn = null)
    {
        if (empty($this->schemas)) {
            return $values;
        }
        $class =  ($values instanceof BatchCollection) ? '\LdapTools\Resolver\BatchValueResolver' : '\LdapTools\Resolver\AttributeValueResolver';
        $valueResolver = new $class(
            $this->getSchema(),
            $values,
            $this->type
        );
        $this->configureValueResolver($valueResolver, $dn);

        return $valueResolver->toLdap();
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

    /**
     * Retrieve the AttributeValueResolver instance with the connection and other information set if needed.
     *
     * @param BaseValueResolver $valueResolver
     * @param $dn null|string
     */
    protected function configureValueResolver(BaseValueResolver $valueResolver, $dn = null)
    {
        $dn = isset($entry['dn']) ? $entry['dn'] : $dn;
        if ($this->connection) {
            $valueResolver->setLdapConnection($this->connection);
        }
        if (!is_null($dn)) {
            $valueResolver->setDn($dn);
        }
    }
}

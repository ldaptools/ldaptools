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
use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Exception\LogicException;
use LdapTools\Query\OperatorCollection;
use LdapTools\Resolver\AttributeNameResolver;
use LdapTools\Resolver\BaseValueResolver;
use LdapTools\Resolver\ParameterResolver;
use LdapTools\Schema\LdapObjectSchema;
use LdapTools\Utilities\MBString;

/**
 * Hydrates a LDAP entry to/from LDAP in array form.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ArrayHydrator implements HydratorInterface
{
    /**
     * @var LdapObjectSchema
     */
    protected $schema;

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
     * @param LdapConnectionInterface|null $connection
     */
    public function __construct(LdapConnectionInterface $connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setLdapObjectSchema(LdapObjectSchema $schema = null)
    {
        $this->schema = $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function getLdapObjectSchema()
    {
        return $this->schema;
    }

    /**
     * {@inheritdoc}
     */
    public function setSelectedAttributes(array $attributes)
    {
        $this->selectedAttributes = $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getSelectedAttributes()
    {
        return $this->selectedAttributes;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateFromLdap(array $entry)
    {
        $attributes = [];

        foreach ($entry as $key => $value) {
            if (is_string($key)) {
                $attributes = $this->setAttributeFromLdap($attributes, $key, $value);
            }
        }
        $attributes = $this->convertNamesFromLdap($attributes);
        $attributes = $this->convertValuesFromLdap($attributes);

        return $attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateAllFromLdap(array $entries)
    {
        $results = [];
        $entryCount = isset($entries['count']) ? $entries['count'] : 0;

        for ($i = 0; $i < $entryCount; $i++) {
            $results[] = $this->hydrateFromLdap($entries[$i]);
        }

        return $results;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateToLdap($attributes)
    {
        if (!is_array($attributes)) {
            throw new InvalidArgumentException('Expects an array to convert data to LDAP.');
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
    public function setLdapConnection(LdapConnectionInterface $connection = null)
    {
        $this->connection = $connection;
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
        return (new AttributeNameResolver($this->schema))->fromLdap($entry, $this->selectedAttributes);
    }

    /**
     * Replace attribute values with the converted values if the attribute has a converter defined.
     *
     * @param array $entry
     * @return array
     */
    protected function convertValuesFromLdap(array $entry)
    {
        if (!$this->schema) {
            return $entry;
        }
        $valueResolver = BaseValueResolver::getInstance(
            $this->schema,
            $entry,
            $this->type
        );
        $this->configureValueResolver($valueResolver, isset($entry['dn']) ? $entry['dn'] : null);

        return $valueResolver->fromLdap();
    }

    /**
     * Returns all of the attributes to be sent to LDAP after factoring in possible default schema values.
     *
     * @param array $attributes
     * @return array
     */
    protected function mergeDefaultAttributes(array $attributes)
    {
        if ($this->schema && !empty($this->schema->getDefaultValues())) {
            $attributes = array_merge($this->schema->getDefaultValues(), $attributes);
        }

        return $attributes;
    }


    /**
     * Checks to make sure all required attributes are present.
     *
     * @param array $attributes
     */
    protected function validateAttributesToLdap(array $attributes)
    {
        if (!$this->schema) {
            return;
        }
        $missing = [];

        foreach ($this->schema->getRequiredAttributes() as $attribute) {
            if (!array_key_exists(MBString::strtolower($attribute), MBString::array_change_key_case($attributes))) {
                $missing[] = $attribute;
            }
        }

        if (!empty($missing)) {
            throw new LogicException(
                sprintf('The following required attributes are missing: %s', implode(', ', $missing))
            );
        }
    }

    /**
     * Checks for attributes assigned an attribute converter. It will replace the value with the converted value then
     * send back all the attributes.
     *
     * @param array|BatchCollection|OperatorCollection $values
     * @param string|null $dn
     * @return array|BatchCollection|OperatorCollection
     */
    protected function convertValuesToLdap($values, $dn = null)
    {
        if (!($values instanceof OperatorCollection) && !$this->schema) {
            return $values;
        }
        $valueResolver = BaseValueResolver::getInstance(
            $this->schema,
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
        return !$this->schema ? $attributes : (new AttributeNameResolver($this->schema))->toLdap($attributes);
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
     * @param null|string $dn
     */
    protected function configureValueResolver(BaseValueResolver $valueResolver, $dn = null)
    {
        if ($this->connection) {
            $valueResolver->setLdapConnection($this->connection);
        }
        if (!is_null($dn)) {
            $valueResolver->setDn($dn);
        }
    }
}

<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Schema;

use LdapTools\Connection\LdapControl;
use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Query\Operator\BaseOperator;
use LdapTools\Utilities\MBString;

/**
 * Describes the attributes for a LDAP object from a schema definition.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapObjectSchema
{
    /**
     * @var string The name of the schema this object is from.
     */
    protected $schemaName = '';

    /**
     * @var string The LdapTools specific object type.
     */
    protected $objectType = '';

    /**
     * @var array The actual object class name in LDAP.
     */
    protected $objectClass = [];

    /**
     * @var string The actual object category name in LDAP.
     */
    protected $objectCategory = '';

    /**
     * @var array The map between LdapTools specific attribute names to what LDAP actually calls them.
     */
    protected $attributeMap = [];

    /**
     * @var array A lower-case equivalent of the attributes map keys.
     */
    protected $lcAttributeNameMap = [];

    /**
     * @var array A lower-case equivalent of the attributes map values.
     */
    protected $lcAttributeValueMap = [];

    /**
     * @var array An array of attribute keys and the converter names tied to them.
     */
    protected $converterMap = [];

    /**
     * @var array A lower-case equivalent of the converter map keys.
     */
    protected $lcConverterMap = [];

    /**
     * @var array An array of attribute names to select by default when using LdapQueryBuilder or a Repository.
     */
    protected $attributesToSelect = [];

    /**
     * @var array These attributes are required when creating this object.
     */
    protected $requiredAttributes = [];

    /**
     * @var array Default values for attributes upon creation.
     */
    protected $defaultValues = [];

    /**
     * @var string The repository to use for this object.
     */
    protected $repository = '\LdapTools\Object\LdapObjectRepository';

    /**
     * @var string The default ou/container where the object should reside in LDAP when created.
     */
    protected $defaultContainer = '';

    /**
     * @var array Any attribute converter options defined.
     */
    protected $converterOptions = [];

    /**
     * @var array Attributes that are defined as being multivalued.
     */
    protected $multivaluedAttributes = [];

    /**
     * @var string The BaseDN used for queries against this schema type.
     */
    protected $baseDn;

    /**
     * @var BaseOperator The operator representation of a filter to be used to select objects of this schema type.
     */
    protected $filter;

    /**
     * @var LdapControl[]
     */
    protected $controls = [];

    /**
     * @var bool|null
     */
    protected $usePaging;

    /**
     * @var string|null
     */
    protected $scope;

    /**
     * @var array
     */
    protected $rdn = ['name'];

    /**
     * @param string $schemaName
     * @param string $objectType
     */
    public function __construct($schemaName, $objectType)
    {
        $this->schemaName = $schemaName;
        $this->objectType = $objectType;
    }

    /**
     * The name of the schema this object definition came from.
     *
     * @param string $schemaName
     */
    public function setSchemaName($schemaName)
    {
        $this->schemaName = $schemaName;
    }

    /**
     * The name of the schema this object definition came from.
     *
     * @return string
     */
    public function getSchemaName()
    {
        return $this->schemaName;
    }

    /**
     * Set the map of names to actual LDAP attribute names.
     *
     * @param array $attributeMap
     */
    public function setAttributeMap(array $attributeMap)
    {
        $this->attributeMap = $attributeMap;
        $this->lcAttributeNameMap = MBString::array_change_key_case($attributeMap);
        $this->lcAttributeValueMap = MBString::array_change_value_case($attributeMap);
    }

    /**
     * Get the map of names to actual LDAP attribute names.
     *
     * @return array
     */
    public function getAttributeMap()
    {
        return $this->attributeMap;
    }

    /**
     * Set the attribute name to attribute converter map.
     *
     * @param array $converterMap
     */
    public function setConverterMap(array $converterMap)
    {
        $this->converterMap = $converterMap;
        $this->lcConverterMap = MBString::array_change_key_case($converterMap);
    }

    /**
     * Get the attribute name to attribute converter map.
     *
     * @return array
     */
    public function getConverterMap()
    {
        return $this->converterMap;
    }

    /**
     * Check if an attribute has a converter defined.
     *
     * @param string $attributeName
     * @return bool
     */
    public function hasConverter($attributeName)
    {
        return isset($this->lcConverterMap[MBString::strtolower($attributeName)]);
    }

    /**
     * Get the name of the converter for an attribute.
     *
     * @param string $attributeName
     * @return string
     */
    public function getConverter($attributeName)
    {
        if (!$this->hasConverter($attributeName)) {
            throw new InvalidArgumentException(sprintf('No converter exists for attribute "%s".', $attributeName));
        }

        return $this->lcConverterMap[MBString::strtolower($attributeName)];
    }

    /**
     * Set the LdapTools object type that this object refers to.
     *
     * @param string $objectType
     */
    public function setObjectType($objectType)
    {
        $this->objectType = $objectType;
    }

    /**
     * Get the LdapTools object type that this object refers to.
     *
     * @return string
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * Set the LDAP object class(es) for this object type.
     *
     * @param string|array $objectClass
     */
    public function setObjectClass($objectClass)
    {
        $this->objectClass = is_array($objectClass) ? $objectClass : [$objectClass];
    }

    /**
     * Get the LDAP object class(es) for this object type.
     *
     * @return array
     */
    public function getObjectClass()
    {
        return $this->objectClass;
    }

    /**
     * Set the objectCategory that this schema object refers to.
     *
     * @param $objectCategory
     */
    public function setObjectCategory($objectCategory)
    {
        $this->objectCategory = $objectCategory;
    }

    /**
     * Get the objectCategory that this schema object refers to.
     *
     * @return string
     */
    public function getObjectCategory()
    {
        return $this->objectCategory;
    }

    /**
     * Check for an attribute defined in the schema that maps to an LDAP attribute.
     *
     * @param string $attribute
     * @return bool
     */
    public function hasAttribute($attribute)
    {
        return isset($this->lcAttributeNameMap[MBString::strtolower($attribute)]);
    }

    /**
     * Check if a LDAP attribute has a schema name mapped to it.
     *
     * @param string $attribute
     * @return bool
     */
    public function hasNamesMappedToAttribute($attribute)
    {
        return (bool) array_search(MBString::strtolower($attribute), $this->lcAttributeValueMap);
    }

    /**
     * Get all the possible schema names/alias' mapped to a LDAP attribute name. It's possible to have multiple
     * different names mapped to one attribute in the case of different converters applied to the same value.
     *
     * @param string $attribute
     * @return array
     */
    public function getNamesMappedToAttribute($attribute)
    {
        return array_keys($this->lcAttributeValueMap, MBString::strtolower($attribute));
    }

    /**
     * Given an attribute name, this will get the attribute that LDAP is expecting for that name.
     *
     * @param string $attribute
     * @return string
     */
    public function getAttributeToLdap($attribute)
    {
        return $this->hasAttribute($attribute) ? $this->lcAttributeNameMap[MBString::strtolower($attribute)] : $attribute;
    }

    /**
     * Set the attributes that will be retrieved by default when using LdapQueryBuilder or a Repository.
     *
     * @param array $attributes
     */
    public function setAttributesToSelect(array $attributes)
    {
        $this->attributesToSelect = $attributes;
    }

    /**
     * Get the attributes that should be retrieved by default when using LdapQueryBuilder or a Repository.
     *
     * @return array
     */
    public function getAttributesToSelect()
    {
        return $this->attributesToSelect;
    }

    /**
     * Set the fully qualified name of the repository class to use for this object.
     *
     * @param string $repository
     */
    public function setRepository($repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get the fully qualified name of the repository class to use for this object.
     *
     * @return string
     */
    public function getRepository()
    {
        return $this->repository;
    }

    /**
     * Set the required attributes when creating this object.
     *
     * @param array $attributes
     */
    public function setRequiredAttributes(array $attributes)
    {
        $this->requiredAttributes = $attributes;
    }

    /**
     * Get the required attributes when creating this object.
     *
     * @return array
     */
    public function getRequiredAttributes()
    {
        return $this->requiredAttributes;
    }

    /**
     * Set the default values when creating this object.
     *
     * @param array $defaultValues
     */
    public function setDefaultValues(array $defaultValues)
    {
        $this->defaultValues = $defaultValues;
    }

    /**
     * Get the default values when creating this object.
     *
     * @return array
     */
    public function getDefaultValues()
    {
        return $this->defaultValues;
    }

    /**
     * Set the default ou/container when creating this object.
     *
     * @param string $container
     */
    public function setDefaultContainer($container)
    {
        $this->defaultContainer = $container;
    }

    /**
     * Get the default ou/container used when creating this object.
     *
     * @return string
     */
    public function getDefaultContainer()
    {
        return $this->defaultContainer;
    }

    /**
     * Set the BaseDN used for queries against this schema type.
     *
     * @param string $baseDn
     */
    public function setBaseDn($baseDn)
    {
        $this->baseDn = $baseDn;
    }

    /**
     * Get the BaseDN used for queries against this schema type.
     *
     * @return string
     */
    public function getBaseDn()
    {
        return $this->baseDn;
    }

    /**
     * Set any options to be passed to specific converters.
     *
     * @param array $converterOptions
     */
    public function setConverterOptions(array $converterOptions)
    {
        $this->converterOptions = $converterOptions;
    }

    /**
     * Get the array of converter names and the options that will be passed to them.
     *
     * @return array
     */
    public function getConverterOptions()
    {
        return $this->converterOptions;
    }

    /**
     * Set the attributes that are expected to be multivalued.
     *
     * @param array $multivaluedAttributes
     */
    public function setMultivaluedAttributes(array $multivaluedAttributes)
    {
        $this->multivaluedAttributes = $multivaluedAttributes;
    }

    /**
     * Get the attributes that are expected to be multivalued.
     *
     * @return array
     */
    public function getMultivaluedAttributes()
    {
        return $this->multivaluedAttributes;
    }

    /**
     * Whether a specific attribute is defined as multivalued or not.
     *
     * @param string $attribute
     * @return bool
     */
    public function isMultivaluedAttribute($attribute)
    {
        return in_array(MBString::strtolower($attribute), MBString::array_change_value_case($this->multivaluedAttributes));
    }

    /**
     * Set the operator that will be used as a filter for querying LDAP for this object type.
     *
     * @param BaseOperator $filter
     */
    public function setFilter(BaseOperator $filter)
    {
        $this->filter = $filter;
    }

    /**
     * Get the operator that will be used as a filter for querying LDAP for this object type.
     *
     * @return BaseOperator
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Set the LDAP controls needed when querying for this operation.
     *
     * @param \LdapTools\Connection\LdapControl[] ...$controls
     */
    public function setControls(LdapControl ...$controls)
    {
        $this->controls = $controls;
    }

    /**
     * Get the LDAP controls needed when querying for this operation.
     *
     * @return \LdapTools\Connection\LdapControl[]
     */
    public function getControls()
    {
        return $this->controls;
    }

    /**
     * Set whether paging should be used when querying LDAP for this type.
     *
     * @param bool $usePaging
     */
    public function setUsePaging($usePaging)
    {
        $this->usePaging = (bool) $usePaging;
    }

    /**
     * Get whether paging should be used when querying LDAP for this type.
     *
     * @return bool|null
     */
    public function getUsePaging()
    {
        return $this->usePaging;
    }

    /**
     * Set the scope of the search for queries using this type.
     *
     * @param string|null $scope
     * @return $this
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
        
        return $this;
    }

    /**
     * Get the scope of the search for queries using this type.
     *
     * @return null|string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set the RDN attribute(s).
     *
     * @param array $rdn
     * @return $this
     */
    public function setRdn(array $rdn)
    {
        $this->rdn = $rdn;

        return $this;
    }

    /**
     * Get the RDN attribute(s)
     *
     * @return array
     */
    public function getRdn()
    {
        return $this->rdn;
    }
}

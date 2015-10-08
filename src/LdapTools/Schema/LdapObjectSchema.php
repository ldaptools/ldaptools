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

use LdapTools\Cache\CacheableItemInterface;

/**
 * Describes the attributes for a LDAP object from a schema definition.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapObjectSchema implements CacheableItemInterface
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
     * @var array An array of attribute keys and the converter names tied to them.
     */
    protected $converterMap = [];

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
     * @param string $schemaName
     * @param string $objectType
     */
    public function __construct($schemaName, $objectType)
    {
        $this->schemaName = $schemaName;
        $this->objectType = $objectType;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheName()
    {
        return $this->schemaName.'.'.$this->objectType;
    }

    /**
     * {@inheritdoc}
     */
    public static function getCacheType()
    {
        return 'LdapObjectSchema';
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
        return array_key_exists(strtolower($attributeName), array_change_key_case($this->converterMap));
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
            throw new \InvalidArgumentException(sprintf('No converter exists for attribute "%s".', $attributeName));
        }

        return array_change_key_case($this->converterMap)[strtolower($attributeName)];
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
        return array_key_exists(strtolower($attribute), array_change_key_case($this->attributeMap));
    }

    /**
     * Check if a LDAP attribute has a schema name mapped to it.
     *
     * @param string $attribute
     * @return bool
     */
    public function hasNamesMappedToAttribute($attribute)
    {
        return (bool) array_search(strtolower($attribute), array_map('strtolower', $this->attributeMap));
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
        return array_keys(array_map('strtolower', $this->attributeMap), strtolower($attribute));
    }

    /**
     * Given an attribute name, this will get the attribute that LDAP is expecting for that name.
     *
     * @param string $attribute
     * @return string
     */
    public function getAttributeToLdap($attribute)
    {
        return $this->hasAttribute($attribute) ?
            array_change_key_case($this->attributeMap)[strtolower($attribute)] : $attribute;
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
        return in_array(strtolower($attribute), array_map('strtolower', $this->multivaluedAttributes));
    }
}

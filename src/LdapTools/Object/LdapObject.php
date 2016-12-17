<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Object;

use LdapTools\BatchModify\Batch;
use LdapTools\BatchModify\BatchCollection;
use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Utilities\MBString;

/**
 * Represents a LDAP Object.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapObject
{
    /**
     * @var array These are the expected "magic" function calls for the attributes.
     */
    protected $functions = [
        'has',
        'get',
        'set',
        'remove',
        'add',
        'reset',
    ];

    /**
     * @var string The type as defined in the schema, if any.
     */
    protected $type = '';

    /**
     * @var array The attributes and values that represent this LDAP object.
     */
    protected $attributes = [];

    /**
     * @var BatchCollection A collection of Batch objects for changes to be sent to LDAP.
     */
    protected $batches;

    /**
     * @param array $attributes
     * @param string $type
     */
    public function __construct(array $attributes = [], $type = '')
    {
        $this->attributes = $attributes;
        $this->type = $type;
        $this->batches = new BatchCollection(isset($attributes['dn']) ? $attributes['dn'] : null);
    }

    /**
     * Get the LDAP type for this object.
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Check if this LDAP Object is a specific type (Schema type).
     *
     * @param string $type
     * @return bool
     */
    public function isType($type)
    {
        return ($this->type == $type);
    }

    /**
     * Check to see if a specific attribute exists. Optionally check if it exists with a specific value.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function has($attribute, $value = null)
    {
        if (!array_key_exists(MBString::strtolower($attribute), MBString::array_change_key_case($this->attributes))) {
            return false;
        }

        return is_null($value) ?: $this->attributeHasValue($attribute, $value);
    }

    /**
     * Get the value of an attribute. An attribute with multiple values will return an array of values.
     *
     * @param string $attribute
     * @return mixed
     */
    public function get($attribute)
    {
        if ($this->has($attribute)) {
            return MBString::array_change_key_case($this->attributes)[MBString::strtolower($attribute)];
        } else {
            throw new InvalidArgumentException(
                sprintf('Attribute "%s" is not defined for this LDAP object.', $attribute)
            );
        }
    }

    /**
     * Set a value for an attribute.
     * If a value already exists it will be replaced.
     * If the value is empty the attribute will be cleared/reset.
     *
     * @param string $attribute
     * @param mixed $value
     * @return $this
     */
    public function set($attribute, $value)
    {
        if ($value === [] || $value === '' || $value === null) {
            return $this->reset($attribute);
        }

        if ($this->has($attribute)) {
            $attribute = $this->resolveAttributeName($attribute);
            $this->attributes[$attribute] = $value;
        } else {
            $this->attributes[$attribute] = $value;
        }
        $this->batches->add(new Batch(Batch::TYPE['REPLACE'], $attribute, $value));

        return $this;
    }

    /**
     * Remove a specific value, or multiple values, from an attribute.
     *
     * @param string $attribute
     * @param mixed[] ...$values
     * @return $this
     */
    public function remove($attribute, ...$values)
    {
        foreach ($values as $value) {
            if ($this->has($attribute)) {
                $attribute = $this->resolveAttributeName($attribute);
                $this->attributes[$attribute] = $this->removeAttributeValue($this->attributes[$attribute], $value);
            }
            $this->batches->add(new Batch(Batch::TYPE['REMOVE'], $attribute, $value));
        }

        return $this;
    }

    /**
     * Resets the attribute, which effectively removes any values it may have.
     *
     * @param string[] ...$attributes
     * @return $this
     */
    public function reset(...$attributes)
    {
        foreach ($attributes as $attribute) {
            if ($this->has($attribute)) {
                $attribute = $this->resolveAttributeName($attribute);
                unset($this->attributes[$attribute]);
            }
            $this->batches->add(new Batch(Batch::TYPE['REMOVE_ALL'], $attribute));
        }

        return $this;
    }

    /**
     * Add an additional value, or values, to an attribute.
     *
     * @param string $attribute
     * @param mixed[] ...$values
     * @return $this
     */
    public function add($attribute, ...$values)
    {
        foreach ($values as $value) {
            if ($this->has($attribute)) {
                $attribute = $this->resolveAttributeName($attribute);
                $this->attributes[$attribute] = $this->addAttributeValue($this->attributes[$attribute], $value);
            } else {
                $this->attributes[$attribute] = $value;
            }
            $this->batches->add(new Batch(Batch::TYPE['ADD'], $attribute, $value));
        }

        return $this;
    }

    /**
     * The array representation of the LDAP object.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Get the BatchCollection object.
     *
     * @return BatchCollection
     */
    public function getBatchCollection()
    {
        return $this->batches;
    }

    /**
     * Sets the BatchCollection.
     *
     * @param BatchCollection $batches
     * @return $this
     */
    public function setBatchCollection(BatchCollection $batches)
    {
        $this->batches = $batches;

        return $this;
    }

    /**
     * Updates a set of attributes/values on the object without incurring a tracked modification.
     *
     * @param array $attributes
     * @return $this
     */
    public function refresh(array $attributes)
    {
        foreach ($attributes as $attribute => $value) {
            if ($this->has($attribute)) {
                $this->attributes[$this->resolveAttributeName($attribute)] = $value;
            } else {
                $this->attributes[$attribute] = $value;
            }
        }

        return $this;
    }

    /**
     * Determines which function, if any, should be called.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (!preg_match('/^('.implode('|', $this->functions).')(.*)$/', $method, $matches)) {
            throw new \RuntimeException(sprintf('The method "%s" is unknown.', $method));
        }
        $method = $matches[1];
        $attribute = lcfirst($matches[2]);

        if ('get' == $method || 'reset' == $method) {
            return $this->$method($attribute);
        } else {
            return $this->$method($attribute, ...$arguments);
        }
    }

    /**
     * Magic property setter.
     *
     * @param string $attribute
     * @param mixed $value
     * @return $this
     */
    public function __set($attribute, $value)
    {
        return $this->set($attribute, $value);
    }

    /**
     * Magic property getter.
     *
     * @param string $attribute
     * @return mixed
     */
    public function __get($attribute)
    {
        return $this->get($attribute);
    }

    /**
     * Magically check for an attributes existence on an isset call.
     *
     * @param string $attribute
     * @return bool
     */
    public function __isset($attribute)
    {
        return $this->has($attribute);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->has('dn') ? $this->get('dn') : '';
    }

    /**
     * Check if an attribute has a specific value. Called only when the attribute is known to exist already.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    protected function attributeHasValue($attribute, $value)
    {
        $attribute = $this->resolveAttributeName($attribute);

        if (is_array($this->attributes[$attribute])) {
            return in_array($value, $this->attributes[$attribute]);
        } else {
            return ($this->attributes[$attribute] == $value);
        }
    }

    /**
     * Retrieve the attribute in the case it exists in the array.
     *
     * @param string $attribute
     * @return string
     */
    protected function resolveAttributeName($attribute)
    {
        $result = preg_grep("/^$attribute$/i", array_keys($this->attributes));
        if (empty($result)) {
            throw new InvalidArgumentException(sprintf('Unable to resolve attribute "%s".', $attribute));
        }

        return reset($result);
    }

    /**
     * Given the original value, remove if it's present.
     *
     * @param mixed $value
     * @param mixed $valueToRemove
     * @return mixed
     */
    protected function removeAttributeValue($value, $valueToRemove)
    {
        $valueToRemove = is_array($valueToRemove) ? $valueToRemove : [ $valueToRemove ];

        foreach ($valueToRemove as $remove) {
            if (is_array($value) && (($key = array_search($remove, $value)) !== false)) {
                unset($value[$key]);
            } elseif (!is_array($value) && ($value == $remove)) {
                $value = '';
            }
        }

        return $value;
    }

    /**
     * Adds an additional value to an existing LDAP attribute value.
     *
     * @param mixed $value
     * @param mixed $valueToAdd
     * @return array
     */
    protected function addAttributeValue($value, $valueToAdd)
    {
        $valueToAdd = is_array($valueToAdd) ? $valueToAdd : [ $valueToAdd ];
        $value = is_array($value) ? $value : [ $value ];

        return array_merge($value, $valueToAdd);
    }
}

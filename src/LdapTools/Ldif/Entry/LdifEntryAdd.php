<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Ldif\Entry;

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\Connection\LdapAwareInterface;
use LdapTools\Connection\LdapAwareTrait;
use LdapTools\Hydrator\OperationHydrator;
use LdapTools\Operation\AddOperation;
use LdapTools\Schema\SchemaAwareInterface;
use LdapTools\Schema\SchemaAwareTrait;

/**
 * Represents a LDIF entry to add an object to LDAP.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdifEntryAdd implements LdifEntryInterface, SchemaAwareInterface, LdapAwareInterface
{
    use LdifEntryTrait,
        SchemaAwareTrait,
        LdapAwareTrait;

    /**
     * @var array The attributes to be sent to LDAP.
     */
    protected $attributes = [];

    /**
     * @var string|null The OU/container where the LDAP object should be created.
     */
    protected $location;

    /**
     * @param string $dn
     * @param array $attributes
     */
    public function __construct($dn = null, $attributes = [])
    {
        $this->dn = $dn;
        if (!empty($attributes)) {
            $this->setAttributes($attributes);
        }
        $this->changeType = self::TYPE_ADD;
    }

    /**
     * Add an attribute that will be added to the entry going to LDAP.
     *
     * @param string $attribute
     * @param mixed $value
     * @return $this
     */
    public function addAttribute($attribute, $value)
    {
        $value = is_array($value) ? $value : [$value];

        if (!isset($this->attributes[$attribute])) {
            $this->attributes[$attribute] = [];
        }
        foreach ($value as $attrValue) {
            $this->attributes[$attribute][] = $attrValue;
        }

        return $this;
    }

    /**
     * Set the attributes that will be added to the entry going to LDAP.
     *
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = [];
        foreach ($attributes as $attribute => $value) {
            $this->addAttribute($attribute, $value);
        }

        return $this;
    }

    /**
     * Get the attributes to be sent to LDAP on an add changetype.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the location of where the LDAP object should be created in LDAP. Only set this when you are using a schema
     * type via the 'setType()' method. Otherwise you must set the full DN via 'setDn()'.
     *
     * @param string $location
     * @return $this
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get the location where the LDAP object should be created in LDAP.
     *
     * @return null|string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * {@inheritdoc}
     */
    public function toOperation()
    {
        $hydrator = new OperationHydrator();
        $hydrator->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $operation =  new AddOperation($this->dn, $this->attributes);
        $operation->setLocation($this->location);

        return $this->hydrateOperation($hydrator, $operation);
    }

    /**
     * {@inheritdoc}
     */
    public function toString()
    {
        /** @var AddOperation $operation */
        $operation = $this->toOperation();
        $ldif = $this->getCommonString($operation->getDn());

        $this->unicodePwdHack($operation);
        foreach ($operation->getAttributes() as $key => $values) {
            $values = is_array($values) ? $values : [$values];
            foreach ($values as $value) {
                $ldif .= $this->getLdifLine($key, $value);
            }
        }

        return $ldif;
    }

    /**
     * Workaround AD special cases with the unicodePwd attribute...
     *
     * @link https://support.microsoft.com/en-us/kb/263991
     * @param AddOperation $operation
     */
    protected function unicodePwdHack(AddOperation $operation)
    {
        if (!$this->isUnicodePwdHackNeeded()) {
            return;
        }
        $attributes = $operation->getAttributes();

        foreach ($attributes as $attribute => $value) {
            if (strtolower($attribute) !== 'unicodepwd') {
                continue;
            }
            $value = is_array($value) ? reset($value) : $value;
            $attributes[$attribute] = base64_encode($value);
        }

        $operation->setAttributes($attributes);
    }
}

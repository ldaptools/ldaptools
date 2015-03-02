<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\AttributeConverter;

use LdapTools\Connection\LdapConnectionInterface;

/**
 * Any attribute conversion to/from LDAP should implement this interface.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface AttributeConverterInterface
{
    /**
     * The process requesting the attribute conversion is searching LDAP and sending the value.
     */
    const TYPE_SEARCH_FROM = 1;

    /**
     * The process requesting the attribute conversion is searching LDAP and retrieving the value.
     */
    const TYPE_SEARCH_TO = 2;

    /**
     * The process requesting the attribute conversion is modifying a LDAP entry.
     */
    const TYPE_MODIFY = 3;

    /**
     * The process request the attribute conversion is creating a LDAP entry.
     */
    const TYPE_CREATE = 4;

    /**
     * Modify the value so it can be understood by LDAP when it gets sent back.
     *
     * @param $value
     * @return mixed
     */
    public function toLdap($value);

    /**
     * Modify the value coming from LDAP so it's easier to work with.
     *
     * @param $value
     * @return mixed
     */
    public function fromLdap($value);

    /**
     * Sets the current LDAP Connection for use by the converter.
     *
     * @param LdapConnectionInterface $connection
     */
    public function setLdapConnection(LdapConnectionInterface $connection);

    /**
     * Sets options that may be recognized by the converter.
     *
     * @param array $options
     */
    public function setOptions(array $options);

    /**
     * Gets the options that may be recognized by the converter.
     *
     * @return array
     */
    public function getOptions();

    /**
     * Set the LDAP operation type.
     *
     * @param $type
     */
    public function setOperationType($type);

    /**
     * Get the LDAP operation type.
     *
     * @return int
     */
    public function getOperationType();

    /**
     * Set the DN of the object with the specific value being converted.
     *
     * @param string $dn
     */
    public function setDn($dn);

    /**
     * Get the DN of the object with the specific value being converted.
     *
     * @return string
     */
    public function getDn();

    /**
     * Set the name of the attribute being converted.
     *
     * @param string $attribute
     */
    public function setAttribute($attribute);

    /**
     * Get the name of the attribute being converted.
     *
     * @return string
     */
    public function getAttribute();

    /**
     * Get whether or not this converter should aggregate multiple attributes into one value.
     *
     * @return bool
     */
    public function getShouldAggregateValues();

    /**
     * Set whether or not this converter should aggregate multiple attributes into one value.
     *
     * @param bool $aggregateValues
     */
    public function setShouldAggregateValues($aggregateValues);

    /**
     * Set the last value in the case of an aggregate value.
     *
     * @param mixed $value
     */
    public function setLastValue($value);

    /**
     * Get the last value as the result of the value conversion.
     *
     * @return mixed
     */
    public function getLastValue();

    /**
     * Get the type of batch operation that is being converted.
     *
     * @return int
     */
    public function getBatchOperationType();

    /**
     * Set the batch operation type being converted.
     *
     * @param int $batchOpType
     */
    public function setBatchOperationType($batchOpType);
}

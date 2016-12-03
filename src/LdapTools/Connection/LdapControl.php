<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Connection;

/**
 * Represents an LDAP control.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapControl
{
    /**
     * @var string The OID for the control.
     */
    protected $oid;

    /**
     * @var bool The criticality of the control.
     */
    protected $criticality = false;

    /**
     * @var mixed The value for the control.
     */
    protected $value;

    /**
     * @var mixed The value to send to reset the control at the end of an operation.
     */
    protected $resetValue = false;

    /**
     * @param string $oid
     * @param bool $criticality
     * @param mixed|null $value
     */
    public function __construct($oid, $criticality = false, $value = null)
    {
        $this->oid = $oid;
        $this->criticality = (bool) $criticality;
        $this->value = $value;
    }

    /**
     * Set the OID for the control.
     *
     * @param string $oid
     * @return $this
     */
    public function setOid($oid)
    {
        $this->oid = $oid;

        return $this;
    }

    /**
     * Get the OID for the control.
     *
     * @return string
     */
    public function getOid()
    {
        return $this->oid;
    }

    /**
     * Set the criticality for the control.
     *
     * @param bool $criticality
     * @return $this
     */
    public function setCriticality($criticality)
    {
        $this->criticality = (bool) $criticality;

        return $this;
    }

    /**
     * Get the criticality for the control.
     *
     * @return bool
     */
    public function getCriticality()
    {
        return $this->criticality;
    }

    /**
     * Set the value for the control.
     *
     * @param mixed $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get the value for the control.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value for the control upon reset at the end of an operation. Defaults to bool false.
     *
     * @param mixed $value
     * @return $this
     */
    public function setResetValue($value)
    {
        $this->resetValue = $value;

        return $this;
    }

    /**
     * Get the value for the control to use upon reset at the end of an operation. Defaults to bool false.
     *
     * @return mixed
     */
    public function getResetValue()
    {
        return $this->resetValue;
    }

    /**
     * Get the control array structure that ldap_set_option expects.
     *
     * @return array
     */
    public function toArray()
    {
        $control = [
            'oid' => $this->oid,
            'iscritical' => $this->criticality
        ];
        if (!is_null($this->value)) {
            $control['value'] = $this->value;
        }

        return $control;
    }

    /**
     * A simple helper to BER encode an int for an ASN.1 structure for a basic LDAP control value.
     *
     * @param int $int
     * @return string The BER encoded ASN.1 structure to use for the LDAP control value.
     */
    public static function berEncodeInt($int)
    {
        return sprintf("%c%c%c%c%c", 48, 3, 2, 1, $int);
    }
}

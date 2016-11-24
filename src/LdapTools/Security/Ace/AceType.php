<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Security\Ace;

use LdapTools\Exception\InvalidArgumentException;

/**
 * Represents an ACE type.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class AceType
{
    /**
     * The different ACE types in numeric value order.
     */
    const TYPE = [
        'ACCESS_ALLOWED' => 0x00,
        'ACCESS_DENIED' => 0x01,
        'SYSTEM_AUDIT' => 0x02,
        'SYSTEM_ALARM' => 0x03,
        'ACCESS_ALLOWED_COMPOUND' => 0x04,
        'ACCESS_ALLOWED_OBJECT' => 0x05,
        'ACCESS_DENIED_OBJECT' => 0x06,
        'SYSTEM_AUDIT_OBJECT' => 0x07,
        'SYSTEM_ALARM_OBJECT' => 0x08,
        'ACCESS_ALLOWED_CALLBACK' => 0x09,
        'ACCESS_DENIED_CALLBACK' => 0x0A,
        'ACCESS_ALLOWED_CALLBACK_OBJECT' => 0x0B,
        'ACCESS_DENIED_CALLBACK_OBJECT' => 0x0C,
        'SYSTEM_AUDIT_CALLBACK' => 0x0D,
        'SYSTEM_ALARM_CALLBACK' => 0x0E,
        'SYSTEM_AUDIT_CALLBACK_OBJECT' => 0x0F,
        'SYSTEM_ALARM_CALLBACK_OBJECT' => 0x10,
        'SYSTEM_MANDATORY_LABEL' => 0x11,
        'SYSTEM_RESOURCE_ATTRIBUTE' => 0x12,
        'SYSTEM_SCOPED_POLICY_ID' => 0x13,
    ];

    /**
     * The short name used for the ACE Type in SDDL.
     */
    const SHORT_NAME = [
        'A' => 0x00,
        'D' => 0x01,
        'AU' => 0x02,
        'AL' => 0x03,
        'CA' => 0x04,
        'OA' => 0x05,
        'OD' => 0x06,
        'OU' => 0x07,
        'OL' => 0x08,
        'XA' => 0x09,
        'XD' => 0x0A,
        'ZA' => 0x0B,
        'ZD' => 0x0C,
        'XU' => 0x0D,
        'XL' => 0x0E,
        'ZU' => 0x0F,
        'ZL' => 0x10,
        'ML' => 0x11,
        'RA' => 0x12,
        'SP' => 0x13,
    ];

    /**
     * @var int
     */
    protected $type;

    /**
     * @param string|int $type
     */
    public function __construct($type)
    {
        $this->setValue($type);
    }

    /**
     * Get the type value of the Ace.
     *
     * @return int
     */
    public function getValue()
    {
        return $this->type;
    }

    /**
     * Set the type of the Ace.
     *
     * @param string|int $type
     * @return $this
     */
    public function setValue($type)
    {
        if (is_numeric($type) && in_array($type, self::TYPE)) {
            $this->type = (int) $type;
        } elseif (is_string($type) && array_key_exists(strtoupper($type), self::TYPE)) {
            $this->type = self::TYPE[strtoupper($type)];
        } elseif (is_string($type) && array_key_exists(strtoupper($type), self::SHORT_NAME)) {
            $this->type = self::SHORT_NAME[strtoupper($type)];
        } else {
            throw new InvalidArgumentException(sprintf('The value "%s" is not a valid AceType.', $type));
        }

        return $this;
    }

    /**
     * Get the short name of the Ace type used in SDDL.
     *
     * @return string
     */
    public function getShortName()
    {
        return (string) array_search($this->type, self::SHORT_NAME);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getShortName();
    }
}

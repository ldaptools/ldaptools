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

use LdapTools\Query\LdapQueryBuilder;
use LdapTools\Query\UserAccountControlFlags;

/**
 * Uses a User Account Control Mapping from the schema and the current attribute/last value context to properly convert
 * the boolean value to what LDAP or PHP expects it to be.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertUserAccountControl implements AttributeConverterInterface
{
    use ConverterUtilitiesTrait;
    use AttributeConverterTrait {
        getShouldAggregateValues as parentGetShouldAggregateValues;
    }

    public function __construct()
    {
        $this->options = [
            'uacMap' => [],
            'defaultValue' => UserAccountControlFlags::NORMAL_ACCOUNT,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        $this->validateCurrentAttribute($this->options['uacMap']);
        if (empty($this->getLastValue()) && $this->getOperationType() == self::TYPE_MODIFY) {
            $this->setLastValue($this->getCurrentUacValue());
        } elseif (empty($this->getLastValue()) && $this->getOperationType() == self::TYPE_CREATE) {
            $this->setLastValue($this->options['defaultValue']);
        }

        return $this->modifyUacValue((bool) $value);
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        $this->validateCurrentAttribute($this->options['uacMap']);

        return (bool) ((int) $value & (int) $this->getArrayValue($this->options['uacMap'], $this->attribute));
    }

    /**
     * {@inheritdoc}
     */
    public function getShouldAggregateValues()
    {
        return ($this->getOperationType() == self::TYPE_MODIFY || $this->getOperationType() == self::TYPE_CREATE);
    }

    /**
     * If the context is a modification, then the current User Account Control value is needed to do the proper bitwise
     * value calculations so as to not squash other values within this attribute.
     *
     * @return string
     */
    protected function getCurrentUacValue()
    {
        if (!$this->getDn() || !$this->getLdapConnection()) {
            throw new \RuntimeException('Unable to query for the current userAccountControl attribute.');
        }

        $query = new LdapQueryBuilder($this->getLdapConnection());
        $result = $query->select('userAccountControl')
            ->where(['distinguishedName' => $this->getDn()])
            ->getLdapQuery()
            ->execute();

        if ($result->count() == 0) {
            throw new \RuntimeException(sprintf('Unable to find LDAP object: %s', $this->getDn()));
        }
        $object = $result->toArray()[0];

        return $object->getUserAccountControl();
    }

    /**
     * Given a bool value, do the needed bitwise comparison against the User Account Control value to either remove or
     * add the bit from the overall value.
     *
     * @param bool $value
     * @return int
     */
    protected function modifyUacValue($value)
    {
        $lastValue = is_array($this->getLastValue()) ? reset($this->getLastValue()) : $this->getLastValue();

        // If the bit we are expecting is already set how we want it, then do not attempt to modify it.
        if ($this->fromLdap($lastValue) === $value) {
            return $lastValue;
        }

        $mappedValue = $this->getArrayValue($this->options['uacMap'], $this->attribute);
        if ($value) {
            $uac = (int) $lastValue | (int) $mappedValue;
        } else {
            $uac = (int) $lastValue ^ (int) $mappedValue;
        }

        return (string) $uac;
    }
}

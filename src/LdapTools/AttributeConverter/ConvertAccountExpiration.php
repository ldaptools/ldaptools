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

use LdapTools\Exception\AttributeConverterException;
use LdapTools\Query\Builder\FilterBuilder;

/**
 * Used to convert an accountExpires value to a DateTime object, or detect if the value indicates it never expires and
 * either set it as false. To set the account to never expire always pass a bool false as the value. Otherwise to set a
 * date and time for the account to expire then set a \DateTime object.
 *
 * @see https://msdn.microsoft.com/en-us/library/ms675098%28v=vs.85%29.aspx
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertAccountExpiration implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * This value (and 0) indicates that the account never expires.
     */
    const NEVER_EXPIRES = '9223372036854775807';

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        // A simple bool for a LDAP search requires some additional filter logic, other values can fall through...
        if ($this->getOperationType() == AttributeConverterInterface::TYPE_SEARCH_TO && is_bool($value)) {
            return $this->getQueryOperator($value);
        }
        if (!($value === false || ($value instanceof \DateTime))) {
            throw new AttributeConverterException(sprintf(
                'Expecting a bool false or DateTime object when converting to LDAP for "%s".',
                $this->getAttribute()
            ));
        }

        return ($value === false) ? '0' : (new ConvertWindowsTime())->toLdap($value);
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        return ($value == 0  || $value == self::NEVER_EXPIRES) ? false : (new ConvertWindowsTime())->fromLdap($value);
    }

    /**
     * @param bool $value
     * @return \LdapTools\Query\Operator\BaseOperator
     */
    protected function getQueryOperator($value)
    {
        $fb = new FilterBuilder();

        if ($value) {
            $operator = $fb->bAnd(
                $fb->gte('accountExpires', '1'),
                $fb->lte('accountExpires', '9223372036854775806')
            );
        } else {
            $operator = $fb->bOr(
                $fb->eq('accountExpires', '0'),
                $fb->eq('accountExpires', self::NEVER_EXPIRES)
            );
        }

        return $operator;
    }
}

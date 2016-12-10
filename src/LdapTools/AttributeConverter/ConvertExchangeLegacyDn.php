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
use LdapTools\Exception\EmptyResultException;
use LdapTools\Query\LdapQueryBuilder;
use LdapTools\Utilities\LdapUtilities;

/**
 * Helps to convert a legacy exchange DN to the proper format on creation/modification
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertExchangeLegacyDn implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    const AUTO = 'auto:';

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        if (substr($value, 0, strlen(self::AUTO)) !== self::AUTO) {
            return $value;
        }
        try {
            $adminLegacyDn = (new LdapQueryBuilder($this->getLdapConnection()))
                ->where(['objectClass' => 'msExchAdminGroup'])
                ->select('legacyExchangeDn')
                ->setBaseDn('%_configurationNamingContext_%')
                ->getLdapQuery()
                ->getSingleScalarResult();
        } catch (EmptyResultException $e) {
            throw new AttributeConverterException('Unable to determine the legacyExchangeDn value. Verify your LDAP account has the correct permissions.');
        }

        return $adminLegacyDn."/cn=Recipients/cn=".str_replace('-', '', LdapUtilities::uuid4())."-".substr($value, strlen(self::AUTO));
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        // Nothing to do coming from LDAP. Just pass along the legacyExchangeDn value...
        return $value;
    }
}

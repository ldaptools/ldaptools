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
use LdapTools\Query\Operator\bOr;
use LdapTools\Utilities\ConverterUtilitiesTrait;
use LdapTools\Utilities\LdapUtilities;

/**
 * Takes a common string value and converts it into a full distinguished name.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertValueToDn implements  AttributeConverterInterface
{
    use AttributeConverterTrait, ConverterUtilitiesTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        if (is_null($this->getLdapConnection())) {
            return $value;
        }
        $this->validateCurrentAttribute($this->options);

        return $this->getDnFromValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($dn)
    {
        $options = $this->getOptionsArray();

        return (isset($options['display_dn']) && $options['display_dn']) ? $dn : reset(LdapUtilities::explodeDn($dn));
    }

    /**
     * Given a value try to determine its full distinguished name.
     *
     * @param string $value
     * @return string $dn
     */
    protected function getDnFromValue($value)
    {
        $options = $this->getOptionsArray();
        $query = $this->buildLdapQuery($options['filter']);

        $bOr = $this->getQueryOrStatement($query, $value);
        $eq = $query->filter()->eq($options['attribute'], $value);

        // If the value is in DN form this will still do a query. Is this really what we want? However, this will verify
        // whether the DN is actually valid or not and still look something that matched a DN but really is not.
        if (!empty($bOr->getChildren())) {
            $bOr->add($eq);
            $query->where($bOr);
        } else {
            $query->where($eq);
        }

        return $query->getLdapQuery()->getSingleScalarResult();
    }

    /**
     * Builds the part the of the query with the specific object class/value to search on.
     *
     * @param array $filter
     * @return LdapQueryBuilder
     */
    protected function buildLdapQuery(array $filter)
    {
        $query = new LdapQueryBuilder($this->connection);
        $query->select('distinguishedName');

        foreach ($filter as $attribute => $value) {
            $query->where([$attribute => $value]);
        }

        return $query;
    }

    /**
     * @param LdapQueryBuilder $query
     * @param string $value
     * @return bOr
     */
    protected function getQueryOrStatement(LdapQueryBuilder $query, $value)
    {
        $bOr = $query->filter()->bOr();

        if (preg_match(LdapUtilities::MATCH_GUID, $value)) {
            $bOr->add($query->filter()->eq('objectGuid', (new ConvertWindowsGuid())->toLdap($value)));
        } elseif (preg_match(LdapUtilities::MATCH_SID, $value)) {
            $bOr->add($query->filter()->eq('objectSid', (new ConvertWindowsSid())->toLdap($value)));
        } elseif (($pieces = ldap_explode_dn($value, 1)) && isset($pieces['count']) && $pieces['count'] > 2) {
            $bOr->add($query->filter()->eq('distinguishedName', $value));
        }

        return $bOr;
    }

    /**
     * Validates and retrieves the options array for the current attribute.
     *
     * @return array
     */
    protected function getOptionsArray()
    {
        $this->validateCurrentAttribute();
        $options = $this->getArrayValue($this->options, $this->getAttribute());

        if (!isset($options['filter']) || !is_array($options['filter'])) {
            throw new \RuntimeException(sprintf('Filter not valid for "%s".', $this->getAttribute()));
        }
        if (!isset($options['attribute'])) {
            throw new \RuntimeException(sprintf('Attribute to search on not defined for "%s"', $this->getAttribute()));
        }

        return $options;
    }

    /**
     * Make sure that the current attribute has actually been defined.
     */
    protected function validateCurrentAttribute()
    {
        if (!array_key_exists(strtolower($this->getAttribute()), array_change_key_case($this->getOptions()))) {
            throw new \RuntimeException(
                sprintf('Attribute "%s" must be defined in the converter options.', $this->getAttribute())
            );
        }
    }
}

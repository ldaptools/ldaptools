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

use LdapTools\Object\LdapObject;
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
        $this->validateCurrentAttribute($this->options);

        return $this->getDnFromValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($dn)
    {
        $options = $this->getOptionsArray();

        if (!(isset($options['display_dn']) && $options['display_dn'])) {
            $dn = LdapUtilities::explodeDn($dn);
            $dn = reset($dn);
        }

        return $dn;
    }

    /**
     * Given a value try to determine how to get its full distinguished name.
     *
     * @param string $value
     * @return string $dn
     */
    protected function getDnFromValue($value)
    {
        if ($value instanceof LdapObject && !$value->has('dn')) {
            throw new \RuntimeException('The LdapObject must have a DN defined.');
        } elseif ($value instanceof LdapObject) {
            $value = $value->get('dn');
        } elseif (!LdapUtilities::isValidLdapObjectDn($value) && !is_null($this->getLdapConnection())) {
            $value = $this->getDnFromLdapQuery($value);
        }

        return $value;
    }

    /**
     * Attempt to look-up the DN from a LDAP query based on the value.
     *
     * @param string $value
     * @return string The distinguished name.
     */
    protected function getDnFromLdapQuery($value)
    {
        $options = $this->getOptionsArray();
        $query = $this->buildLdapQuery($options['filter'], (isset($options['or_filter']) && $options['or_filter']));

        $bOr = $this->getQueryOrStatement($query, $value);
        $eq = $query->filter()->eq($options['attribute'], $value);

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
     * @param bool $isOrFilter
     * @return LdapQueryBuilder
     */
    protected function buildLdapQuery(array $filter, $isOrFilter)
    {
        $query = new LdapQueryBuilder($this->connection);
        $query->select('distinguishedName');

        $statement = $isOrFilter ? $query->filter()->bOr() : $query->filter()->bAnd();
        foreach ($filter as $attribute => $values) {
            $values = is_array($values) ? $values : [ $values ];
            foreach ($values as $value) {
                $statement->add($query->filter()->eq($attribute, $value));
            }
        }

        return $query->andWhere($statement);
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

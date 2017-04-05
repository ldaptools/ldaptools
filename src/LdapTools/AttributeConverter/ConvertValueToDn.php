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
use LdapTools\Object\LdapObject;
use LdapTools\Query\LdapQueryBuilder;
use LdapTools\Query\Operator\bOr;
use LdapTools\Utilities\ConverterUtilitiesTrait;
use LdapTools\Utilities\LdapUtilities;
use LdapTools\Utilities\MBString;

/**
 * Takes a common string value and converts it into a full distinguished name.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertValueToDn implements AttributeConverterInterface
{
    use AttributeConverterTrait, ConverterUtilitiesTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        $this->validateCurrentAttribute();

        return $this->getDnFromValue($value);
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        $options = $this->getOptionsArray();

        if (!(isset($options['display_dn']) && $options['display_dn'])) {
            $value = $this->explodeDnValue($value, $options);
        }

        return $value;
    }

    /**
     * @param string $value
     * @param array $options
     * @return array
     */
    protected function explodeDnValue($value, array $options)
    {
        if (isset($options['legacy_dn']) && $options['legacy_dn']) {
            $value = LdapUtilities::explodeExchangeLegacyDn($value);
            $value = end($value);
        }  else {
            $value = LdapUtilities::explodeDn($value);
            $value = reset($value);
        }

        return $value;
    }

    /**
     * Given a value try to determine how to get its full distinguished name.
     *
     * @param string $value
     * @return string $dn
     * @throws AttributeConverterException
     */
    protected function getDnFromValue($value)
    {
        $options = $this->getOptionsArray();
        $toSelect = (isset($options['select']) ? $options['select'] : 'dn');

        if ($value instanceof LdapObject && !$value->has($toSelect)) {
            throw new AttributeConverterException(sprintf(
                'The LdapObject must have a "%s" defined when used in "%s".',
                $toSelect,
                $this->getAttribute()
            ));
        } elseif ($value instanceof LdapObject) {
            $value = $value->get($toSelect);
        } elseif (!LdapUtilities::isValidLdapObjectDn($value) && !is_null($this->getLdapConnection())) {
            $value = $this->getAttributeFromLdapQuery($value, $toSelect);
        }

        return $value;
    }

    /**
     * Attempt to look-up the attribute from a LDAP query based on the value.
     *
     * @param string $value
     * @param string $toSelect
     * @return string The distinguished name.
     * @throws AttributeConverterException
     */
    protected function getAttributeFromLdapQuery($value, $toSelect)
    {
        $options = $this->getOptionsArray();
        $query = $this->buildLdapQuery($options['filter'], (isset($options['or_filter']) && $options['or_filter']), $toSelect);

        $bOr = $this->getQueryOrStatement($query, $value);
        $eq = $query->filter()->eq($options['attribute'], $value);

        if (!empty($bOr->getChildren())) {
            $bOr->add($eq);
            $query->where($bOr);
        } else {
            $query->where($eq);
        }
        if (isset($options['base_dn'])) {
            $query->setBaseDn($options['base_dn']);
        }

        try {
            return $query->getLdapQuery()->getSingleScalarResult();
        } catch (EmptyResultException $e) {
            throw new AttributeConverterException(sprintf(
                'Unable to convert value "%s" to a %s for attribute "%s"',
                $value,
                $toSelect,
                $this->getAttribute()
            ));
        }
    }

    /**
     * Builds the part the of the query with the specific object class/value to search on.
     *
     * @param array $filter
     * @param bool $isOrFilter
     * @param string $toSelect
     * @return LdapQueryBuilder
     */
    protected function buildLdapQuery(array $filter, $isOrFilter, $toSelect)
    {
        $query = new LdapQueryBuilder($this->connection);
        $query->select($toSelect);

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

        $opType = AttributeConverterInterface::TYPE_SEARCH_TO;
        if (LdapUtilities::isValidGuid($value)) {
            $bOr->add($query->filter()->eq('objectGuid', (new ConvertWindowsGuid())->setOperationType($opType)->toLdap($value)));
        } elseif (LdapUtilities::isValidSid($value)) {
            $bOr->add($query->filter()->eq('objectSid', (new ConvertWindowsSid())->setOperationType($opType)->toLdap($value)));
        }

        return $bOr;
    }

    /**
     * Validates and retrieves the options array for the current attribute.
     *
     * @return array
     * @throws AttributeConverterException
     */
    protected function getOptionsArray()
    {
        $this->validateCurrentAttribute();
        $options = $this->getArrayValue($this->options, $this->getAttribute());

        if (!isset($options['filter']) || !is_array($options['filter'])) {
            throw new AttributeConverterException(sprintf('Filter not valid for "%s".', $this->getAttribute()));
        }
        if (!isset($options['attribute'])) {
            throw new AttributeConverterException(sprintf('Attribute to search on not defined for "%s"', $this->getAttribute()));
        }

        return $options;
    }

    /**
     * Make sure that the current attribute has actually been defined.
     *
     * @throws AttributeConverterException
     */
    protected function validateCurrentAttribute()
    {
        if (!array_key_exists(MBString::strtolower($this->getAttribute()), MBString::array_change_key_case($this->getOptions()))) {
            throw new AttributeConverterException(
                sprintf('Attribute "%s" must be defined in the converter options.', $this->getAttribute())
            );
        }
    }
}

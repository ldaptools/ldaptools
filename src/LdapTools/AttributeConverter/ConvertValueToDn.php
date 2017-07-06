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

/**
 * Takes a common string value and converts it into a full distinguished name.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertValueToDn implements AttributeConverterInterface
{
    use AttributeConverterTrait, ConverterUtilitiesTrait;

    /**
     * @var array
     */
    protected $options = [
        # The attribute containing the friendly/common name of the value
        'attribute' => 'cn',
        # Whether the DN should be displayed instead of the friendly/common name
        'display_dn' => false,
        # Whether the value is expected to be a legacy (exchange) DN
        'legacy_dn' => false,
        # The value to select/use when going back to LDAP
        'select' => 'dn',
        # The filter to use when querying LDAP
        'filter' => [],
        # Whether the query should be a logical OR filter
        'or_filter' => false,
        # The base DN to use when querying LDAP
        'base_dn' => null,
        # Whether or not a wildcard should be allowed in the friendly name
        'allow_wildcard' => false,
    ];

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        if ($value instanceof LdapObject && !$value->has($this->options['select'])) {
            throw new AttributeConverterException(sprintf(
                'The LdapObject must have a "%s" defined when used in "%s".',
                $this->options['select'],
                $this->getAttribute()
            ));
        } elseif ($value instanceof LdapObject) {
            $value = $value->get($this->options['select']);
        } elseif (!LdapUtilities::isValidLdapObjectDn($value) && !is_null($this->getLdapConnection())) {
            $value = $this->getAttributeFromLdapQuery($value, $this->options['select']);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        if (!($this->options['display_dn'])) {
            $value = $this->explodeDnValue($value);
        }

        return $value;
    }

    /**
     * @param string $value
     * @return array
     */
    protected function explodeDnValue($value)
    {
        if ($this->options['legacy_dn']) {
            $value = LdapUtilities::explodeExchangeLegacyDn($value);
            $value = end($value);
        }  else {
            $value = LdapUtilities::explodeDn($value);
            $value = reset($value);
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
        $query = $this->buildLdapQuery($this->options['filter'], $this->options['or_filter'], $toSelect);

        $bOr = $this->getQueryOrStatement($query, $value);
        $eq = $this->getQueryComparisonStatement($value, $query);

        if (!empty($bOr->getChildren())) {
            $bOr->add($eq);
            $query->where($bOr);
        } else {
            $query->where($eq);
        }
        $query->setBaseDn($this->options['base_dn']);

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
     * @param string $value
     * @param LdapQueryBuilder $query
     * @return \LdapTools\Query\Operator\BaseOperator
     */
    protected function getQueryComparisonStatement($value, LdapQueryBuilder $query)
    {
        if ($this->options['allow_wildcard']) {
            $eq = $query->filter()->like($this->options['attribute'], $value);
        } else {
            $eq = $query->filter()->eq($this->options['attribute'], $value);
        }

        return $eq;
    }
}

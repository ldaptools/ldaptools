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
use LdapTools\Utilities\LdapUtilities;

/**
 * Takes a common string value and converts it into a full distinguished name.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertNameToDn implements  AttributeConverterInterface
{
    use AttributeConverterTrait, ConverterUtilitiesTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($name)
    {
        if (is_null($this->getLdapConnection())) {
            return $name;
        }
        $this->validateCurrentAttribute($this->options);

        return $this->getDnFromName($name);
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($dn)
    {
        return reset(LdapUtilities::explodeDn($dn));
    }

    /**
     * Given a common string name, do the LDAP query needed to retrieve the full distinguished name.
     *
     * @param string $name
     * @return string $dn
     */
    protected function getDnFromName($name)
    {
        $options = $this->getOptionsArray();
        $query = $this->buildLdapQuery($options['filter'])->where([$options['attribute'] => $name]);

        $results = $query->getLdapQuery()->execute();
        if ($results->count() != 1) {
            throw new \InvalidArgumentException(sprintf('Unable to resolve "%s" to a valid LDAP object.', $name));
        }

        return $results->toArray()[0]->getDn();
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
        $query->select(['distinguishedName']);

        foreach ($filter as $attribute => $value) {
            $query->where([$attribute => $value]);
        }

        return $query;
    }

    /**
     * Validates and retrieves the options array for the current attribute.
     *
     * @return array
     */
    protected function getOptionsArray()
    {
        $options = $this->getArrayValue($this->options, $this->getAttribute());

        if (!isset($options['filter']) || !is_array($options['filter'])) {
            throw new \RuntimeException(sprintf('Filter not valid for "%s".', $this->getAttribute()));
        }
        if (!isset($options['attribute'])) {
            throw new \RuntimeException(sprintf('Attribute to search on not defined for "%s"', $this->getAttribute()));
        }

        return $options;
    }
}

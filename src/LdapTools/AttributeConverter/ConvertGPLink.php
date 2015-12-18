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

use LdapTools\BatchModify\Batch;
use LdapTools\Exception\AttributeConverterException;
use LdapTools\Factory\HydratorFactory;
use LdapTools\Utilities\ConverterUtilitiesTrait;
use LdapTools\Query\LdapQueryBuilder;

/**
 * Converts a gPLink attribute to an array of GPO human-readable names, and back again for LDAP.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertGPLink implements AttributeConverterInterface
{
    use AttributeConverterTrait, ConverterUtilitiesTrait;

    /**
     * @var null|array The GPO names to go to LDAP are stored here, as the last value must be a conversion of this.
     */
    protected $gpoNames = null;

    public function __construct()
    {
        $this->setIsMultiValuedConverter(true);
    }

    /**
     * {@inheritdoc}
     */
    public function toLdap($gpoLinks)
    {
        $this->setDefaultLastValue('gPLink', '');
        $this->modifyGPOLinks($gpoLinks);

        if ($this->getOperationType() == self::TYPE_MODIFY) {
            $this->getBatch()->setModType(Batch::TYPE['REPLACE']);
        }

        return $this->getLastValue();
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($gpLink)
    {
        $gpLinks = $this->explodeGPOLinkString(is_array($gpLink) ? reset($gpLink) : $gpLink);

        return $this->getValuesForAttribute($gpLinks, 'distinguishedName', 'displayname');
    }

    /**
     * {@inheritdoc}
     */
    public function getShouldAggregateValues()
    {
        return ($this->getOperationType() == self::TYPE_MODIFY || $this->getOperationType() == self::TYPE_CREATE);
    }

    /**
     * Given an array of values and the attribute to query, get the values as represent by the attribute to select.
     *
     * @param array $values
     * @param string $toQuery
     * @param string $toSelect
     * @return array
     */
    protected function getValuesForAttribute(array $values, $toQuery, $toSelect)
    {
        $query = new LdapQueryBuilder($this->getLdapConnection());

        $or = $query->filter()->bOr();
        foreach ($values as $value) {
            $or->add($query->filter()->eq($toQuery, $value));
        }
        $query->select($toSelect)->where($or);

        return array_column($query->getLdapQuery()->execute(HydratorFactory::TO_ARRAY), $toSelect);
    }

    /**
     * Modify the current GPO links based on value modifications requested.
     *
     * @param array $GPOs
     */
    protected function modifyGPOLinks(array $GPOs)
    {
        if (is_null($this->gpoNames) && $this->getOperationType() != self::TYPE_CREATE) {
            $this->gpoNames = $this->fromLdap($this->getLastValue());
        } elseif (is_null($this->gpoNames)) {
            $this->gpoNames = [];
        }
        $this->gpoNames = $this->modifyMultivaluedAttribute($this->gpoNames, $GPOs);

        // Unfortunately this will make round-trips to LDAP on each aggregate. How to avoid this?
        $this->setLastValue($this->implodeGPOLinks($this->gpoNames));
    }

    /**
     * Given a gPLink value, pick out all the GPO DNs and return them as an array.
     *
     * @param string $gpLink
     * @return string[]
     * @throws AttributeConverterException
     */
    protected function explodeGPOLinkString($gpLink)
    {
        preg_match_all('/(?:\[LDAP\:\/\/(.*?);\d\])/', $gpLink, $matches);
        if (!isset($matches[1])) {
            throw new AttributeConverterException(sprintf('Unable to parse gPLink value: %s', $gpLink));
        }

        return $matches[1];
    }

    /**
     * Given an array of GPO names, transform them back into a single GPO link string.
     *
     * @param array $gpoLinks
     * @return string
     */
    protected function implodeGPOLinks(array $gpoLinks)
    {
        $distinguishedNames = $this->getValuesForAttribute($gpoLinks, 'displayName', 'distinguishedname');

        $gpoLink = '';
        foreach ($distinguishedNames as $dn) {
            $gpoLink .= '[LDAP://'.$dn.';0]';
        }

        return $gpoLink;
    }
}

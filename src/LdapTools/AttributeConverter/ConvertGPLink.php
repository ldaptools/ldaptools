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
use LdapTools\Object\LdapObject;
use LdapTools\Utilities\ConverterUtilitiesTrait;
use LdapTools\Query\LdapQueryBuilder;
use LdapTools\Utilities\GPOLink;
use LdapTools\Utilities\MBString;

/**
 * Converts a gPLink attribute to an array of GPOLink objects, and back again for LDAP.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertGPLink implements AttributeConverterInterface
{
    use AttributeConverterTrait, ConverterUtilitiesTrait;

    /**
     * @var null|GPOLink[] The GPOLinks to go to LDAP are stored here, as the last value must be a conversion of this.
     */
    protected $gpoLinks = null;

    public function __construct()
    {
        $this->setIsMultiValuedConverter(true);
    }

    /**
     * {@inheritdoc}
     */
    public function toLdap($gpoLinks)
    {
        // Let a 'reset' type pass through untouched...
        if ($this->getOperationType() == self::TYPE_MODIFY && $this->getBatch()->isTypeRemoveAll()) {
            $gpoString =  '';
        // On a search we just translate the objects to a string link...
        } elseif ($this->getOperationType() == self::TYPE_SEARCH_TO) {
            $this->validateGPOLinks($gpoLinks);
            $gpoString = $this->implodeGPOLinks($gpoLinks);
        } else {
            $gpoString = $this->createOrModifyGPOLinks($gpoLinks);
        }

        return $gpoString;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($gpLink)
    {
        $gpoInfo = $this->explodeGPOLinkString(is_array($gpLink) ? reset($gpLink) : $gpLink);

        return empty($gpoInfo) ? [] : $this->getGPOLinkArray($gpoInfo);
    }

    /**
     * {@inheritdoc}
     */
    public function getShouldAggregateValues()
    {
        return ($this->getOperationType() == self::TYPE_MODIFY || $this->getOperationType() == self::TYPE_CREATE);
    }

    /**
     * @param array $gpoLinks
     * @return string
     */
    protected function createOrModifyGPOLinks(array $gpoLinks)
    {
        $this->setDefaultLastValue('gPLink', '');
        $this->modifyGPOLinks($gpoLinks);
        if ($this->getOperationType() == self::TYPE_MODIFY) {
            $this->getBatch()->setModType(Batch::TYPE['REPLACE']);
        }

        /**
         * If all GPO links are removed on modification, the value should be a single space. This is what AD actually
         * does anyway, not sure why it doesn't just unset the attribute value.
         */
        return $this->getLastValue() === '' && $this->getBatch() ? ' ' : $this->getLastValue();
    }

    /**
     * Modify the current GPO links based on value modifications requested.
     *
     * @param array $GPOs
     */
    protected function modifyGPOLinks(array $GPOs)
    {
        if (is_null($this->gpoLinks) && $this->getOperationType() != self::TYPE_CREATE) {
            $this->gpoLinks = $this->fromLdap($this->getLastValue());
        } elseif (is_null($this->gpoLinks)) {
            $this->gpoLinks = [];
        }
        $this->validateGPOLinks($GPOs);
        $this->gpoLinks = $this->modifyMultivaluedAttribute($this->gpoLinks, $GPOs);
        $this->setLastValue($this->implodeGPOLinks($this->gpoLinks));
    }

    /**
     * @param array $gpoInfo
     * @return GPOLink[]
     */
    protected function getGPOLinkArray(array $gpoInfo)
    {
        $GPOs = $this->getValuesForAttribute(array_keys($gpoInfo), 'distinguishedName', ['displayname', 'objectGuid']);
        $gpoLinks = [];

        // Doing one at a time to keep the order of the GPOs...
        foreach ($gpoInfo as $dn => $options) {
            foreach ($GPOs as $GPO) {
                if (MBString::strtolower($GPO->get('dn')) == MBString::strtolower($dn)) {
                    $attributes = [
                        'dn' => $GPO->get('dn'),
                        'guid' => (new ConvertWindowsGuid())->fromLdap($GPO->get('objectGuid')),
                        'name' => $GPO->get('displayname'),
                    ];
                    $gpoLinks[] = new GPOLink(new LdapObject($attributes), $options, $dn);
                    break;
                }
            }
        }

        return $gpoLinks;
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

        return $query->getLdapQuery()->getResult();
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
        /**
         * It's possible for the gpLink attribute to be a single space under some conditions, though it doesn't seem to
         * be documented anywhere in MSDN. In this case we will return an empty array below.
         */
        if (!preg_match_all('/(?:\[LDAP\:\/\/(.*?);(\d)\])/', $gpLink, $matches) || !isset($matches[1])) {
            return [];
        }

        // GPO link data is stored in reverse order in the string, hence the array_reverse
        return array_combine(array_reverse($matches[1]), array_reverse($matches[2]));
    }

    /**
     * Given an array of GPOLink objects, transform them back into a single gpLink string.
     *
     * @param GPOLink[] $gpoLinks
     * @return string
     */
    protected function implodeGPOLinks(array $gpoLinks)
    {
        // The GPO link string stores the order in reverse, so we need to reverse it when going back to LDAP...
        $gpoLinks = array_reverse($gpoLinks);

        $gpoLink = '';
        /** @var GPOLink $gpo */
        foreach ($gpoLinks as $gpo) {
            $dn = $this->getGPOLinkDN($gpo);
            $gpoLink .= '[LDAP://'.$dn.';'.$gpo->getOptionsFlag().']';
        }

        return $gpoLink;
    }

    /**
     * @param array $GPOs
     * @throws AttributeConverterException
     */
    protected function validateGPOLinks(array $GPOs)
    {
        foreach ($GPOs as $GPO) {
            if (!$GPO instanceof GPOLink) {
                throw new AttributeConverterException('GPO links going to LDAP must be an instance of \LdapTools\Utilities\GPOLink');
            }
        }
    }

    /**
     * Modifies a multivalued attribute array based off the original values, the new values, and the modification type.
     *
     * @param array $values
     * @param array $newValues
     * @return array
     */
    protected function modifyMultivaluedAttribute(array $values, array $newValues)
    {
        if ($this->getOperationType() == AttributeConverterInterface::TYPE_CREATE || ($this->getBatch() && $this->getBatch()->isTypeAdd())) {
            $values = array_merge($values, $newValues);
        } elseif ($this->getBatch() && $this->getBatch()->isTypeReplace()) {
            $values = $newValues;
        } elseif ($this->getBatch() && $this->getBatch()->isTypeRemove()) {
            $values = $this->removeGPOLinksFromArray($values, $newValues);
        }

        return $values;
    }

    /**
     * @param GPOLink[] $values
     * @param GPOLink[] $toRemove
     * @return GPOLink[]
     */
    protected function removeGPOLinksFromArray(array $values, array $toRemove)
    {
        foreach ($toRemove as $value) {
            $dn = $this->getGPOLinkDN($value);
            foreach ($values as $index => $originalValue) {
                if (MBString::strtolower($originalValue->getGpo()->get('dn')) == MBString::strtolower($dn)) {
                    unset($values[$index]);
                    break;
                }
            }
        }

        return $values;
    }

    /**
     * @param GPOLink $gpoLink
     * @return string
     */
    protected function getGPOLinkDN(GPOLink $gpoLink)
    {
        $toDn = new ConvertValueToDn();
        $toDn->setAttribute('gpoLink');
        $toDn->setLdapConnection($this->connection);
        $toDn->setOptions([
            'gpoLink' => [
                'attribute' => 'displayName',
                'filter' => [
                    'objectClass' => 'groupPolicyContainer'
                ]
            ]
        ]);

        return $toDn->toLdap($gpoLink->getGpo());
    }
}

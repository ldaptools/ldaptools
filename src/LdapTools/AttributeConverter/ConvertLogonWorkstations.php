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
use LdapTools\Utilities\ConverterUtilitiesTrait;

/**
 * Modifies the userWorkstations attribute (The "Log on To..." entries) to correctly format it between a comma separated
 * string and an array. Why this value isn't just a multi-valued LDAP attribute to begin with to avoid this whole mess
 * the world may never know.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertLogonWorkstations implements AttributeConverterInterface
{
    use ConverterUtilitiesTrait, AttributeConverterTrait;

    public function __construct()
    {
        $this->setIsMultiValuedConverter(true);
    }

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        if ($this->getOperationType() == self::TYPE_MODIFY && $this->getBatch()->isTypeRemoveAll()) {
            return '';
        } elseif ($this->getOperationType() == self::TYPE_SEARCH_TO) {
            return $this->implodeLoginList($value);
        }
        $this->setDefaultLastValue('userWorkstations', '');
        $this->modifyWorkstations($value);

        if ($this->getOperationType() == self::TYPE_MODIFY) {
            $this->getBatch()->setModType(Batch::TYPE['REPLACE']);
        }

        return $this->getLastValue();
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        return explode(',', reset($value)) ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function getShouldAggregateValues()
    {
        return ($this->getOperationType() == self::TYPE_MODIFY || $this->getOperationType() == self::TYPE_CREATE);
    }

    /**
     * Modifies an array of generic address types.
     *
     * @param array $workstations
     */
    protected function modifyWorkstations(array $workstations)
    {
        $values = array_filter(explode(',', $this->getLastValue())) ?: [];
        $values = $this->modifyMultivaluedAttribute($values, $workstations);

        $this->setLastValue($this->implodeLoginList($values));
    }

    /**
     * @param array $values
     * @return string
     */
    protected function implodeLoginList(array $values)
    {
        return implode(',', array_filter($values));
    }
}

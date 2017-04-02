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

use LdapTools\Security\GUID;

/**
 * Converts Exchange Recipient Policies assigned to a user in GUID format to readable names.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertExchangeRecipientPolicy extends ConvertValueToDn
{
    /**
     * This GUID represents the auto-update recipient setting.
     */
    const AUTO_UPDATE = '{26491cfc-9e50-4857-861b-0cb8df22b5d7}';

    /**
     * {@inheritdoc}
     */
    public function toLdap($values)
    {
        $policies = [];

        foreach ($values as $value) {
            $policies[] = (new GUID(parent::toLdap($value)))->toString();
        }
        /**
         * @todo This does not allow removing the auto update of email addresses...
         */
        if ($this->getOperationType() == self::TYPE_CREATE && !in_array(self::AUTO_UPDATE, $policies)) {
            array_unshift($policies, self::AUTO_UPDATE);
        }

        return $policies;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($values)
    {
        $policies = [];

        foreach ($values as $value) {
            if ($value !== self::AUTO_UPDATE) {
                $policies[] = $this->getAttributeFromLdapQuery($value, 'cn');
            }
        }

        return  $policies;
    }

    /**
     * {@inheritdoc}
     */
    public function getIsMultiValuedConverter()
    {
        return true;
    }
}

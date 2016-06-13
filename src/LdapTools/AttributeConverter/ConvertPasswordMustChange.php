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

use LdapTools\Query\Builder\FilterBuilder;

/**
 * Based off a boolean value this will correctly set the pwdLastSet attribute in AD.
 *
 * @see https://technet.microsoft.com/en-us/library/ee198797.aspx
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class ConvertPasswordMustChange implements AttributeConverterInterface
{
    use AttributeConverterTrait;

    /**
     * {@inheritdoc}
     */
    public function toLdap($value)
    {
        /**
         * @todo There's a lot more potential logic that needs to happen for this to be accurate...
         */
        if ($this->getOperationType() == AttributeConverterInterface::TYPE_SEARCH_TO && !$value) {
            $fb = new FilterBuilder();
            $value = $fb->bNot($fb->eq('pwdLastSet', '0'));
        } else {
            $value = ((bool) $value) ? '0' : '-1';
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function fromLdap($value)
    {
        return (bool) ($value == '0');
    }
}

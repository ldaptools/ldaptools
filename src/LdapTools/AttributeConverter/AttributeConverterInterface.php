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

/**
 * Any attribute conversion to/from LDAP should implement this interface.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface AttributeConverterInterface
{
    /**
     * Modify the value so it can be understood by LDAP when it gets sent back.
     *
     * @param $value
     * @return mixed
     */
    public function toLdap($value);

    /**
     * Modify the value coming from LDAP so it's easier to work with.
     *
     * @param $value
     * @return mixed
     */
    public function fromLdap($value);
}

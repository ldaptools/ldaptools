<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Schema;

/**
 * The LDAP schema aware interface.
 */
interface SchemaAwareInterface
{
    /**
     * @param LdapObjectSchema|null $schema
     */
    public function setLdapObjectSchema(LdapObjectSchema $schema = null);

    /**
     * @return LdapObjectSchema|null
     */
    public function getLdapObjectSchema();
}

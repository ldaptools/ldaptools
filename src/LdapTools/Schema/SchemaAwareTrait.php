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
 * A trait used to implement the SchemaAwareInterface.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
trait SchemaAwareTrait
{
    /**
     * @var LdapObjectSchema|null
     */
    protected $schema;

    /**
     * @param LdapObjectSchema|null $schema
     * @return $this
     */
    public function setLdapObjectSchema(LdapObjectSchema $schema = null)
    {
        $this->schema = $schema;

        return $this;
    }

    /**
     * @return LdapObjectSchema|null
     */
    public function getLdapObjectSchema()
    {
        return $this->schema;
    }
}

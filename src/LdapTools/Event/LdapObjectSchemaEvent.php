<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Event;

use LdapTools\Schema\LdapObjectSchema;

/**
 * Represents a LdapObjectSchema event.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapObjectSchemaEvent extends Event
{
    /**
     * @var LdapObjectSchema
     */
    protected $schema;

    /**
     * @param string $eventName
     * @param LdapObjectSchema $schema
     */
    public function __construct($eventName, LdapObjectSchema $schema)
    {
        $this->schema = $schema;
        parent::__construct($eventName);
    }

    /**
     * @return LdapObjectSchema
     */
    public function getLdapObjectSchema()
    {
        return $this->schema;
    }
}

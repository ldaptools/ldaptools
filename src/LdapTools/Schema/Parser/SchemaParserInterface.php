<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Schema\Parser;

use LdapTools\Schema\LdapObjectSchema;

/**
 * The interface for a SchemaParser.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface SchemaParserInterface
{
    /**
     * Given the schema name and object type, parse through the schema and return the LdapObjectSchema.
     *
     * @param string $schemaName
     * @param string $objectType
     * @return LdapObjectSchema
     */
    public function parse($schemaName, $objectType);

    /**
     * Given the schema name, parse through and return every LdapObjectSchema.
     *
     * @param string $schemaName
     * @return LdapObjectSchema[]
     */
    public function parseAll($schemaName);

    /**
     * Given the schema name, return the last time the schema was modified in DateTime format.
     *
     * @param string $schemaName
     * @return \DateTime
     */
    public function getSchemaModificationTime($schemaName);
}

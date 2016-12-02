<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Factory;

use LdapTools\Configuration;
use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Schema\Parser\SchemaYamlParser;

/**
 * A factory for retrieving the schema parsing mechanism by its type.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class SchemaParserFactory
{
    /**
     * YAML format for schema definition.
     */
    const TYPE_YML = 'yml';

    public static function get($type, $schemaFolder)
    {
        if (self::TYPE_YML == $type) {
            return new SchemaYamlParser($schemaFolder);
        } else {
            throw new InvalidArgumentException(sprintf('Unknown schema parser type "%s".', $type));
        }
    }
}

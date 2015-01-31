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

use LdapTools\Cache\CacheInterface;
use LdapTools\Schema\Parser\SchemaParserInterface;
use LdapTools\Schema\LdapObjectSchema;

/**
 * Responsible for returning a LdapObjectSchema for a given object class in the given domain context.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapObjectSchemaFactory
{
    /**
     * @var CacheInterface The caching mechanism.
     */
    protected $cache;

    /**
     * @var \LdapTools\Schema\Parser\SchemaParserInterface The parser for the schema files.
     */
    protected $parser;

    /**
     * @param CacheInterface $cache
     * @param SchemaParserInterface $parser
     */
    public function __construct(CacheInterface $cache, SchemaParserInterface $parser)
    {
        $this->cache = $cache;
        $this->parser = $parser;
    }

    /**
     * Get the LdapObjectSchema for a specific schema name and object type.
     *
     * @param string $schemaName
     * @param string $objectType
     * @return LdapObjectSchema
     */
    public function get($schemaName, $objectType)
    {
        $cacheItem = $schemaName.'.'.$objectType;
        $lastModTime = $this->parser->getSchemaModificationTime($schemaName);
        $cacheCreationTime = $this->cache->getCacheCreationTime(LdapObjectSchema::getCacheType(), $cacheItem);

        if (!$lastModTime || ($lastModTime > $cacheCreationTime)) {
            $ldapObjectSchema = $this->parser->parse($schemaName, $objectType);
            $this->cache->set($ldapObjectSchema);
        } else {
            $ldapObjectSchema = $this->cache->get(LdapObjectSchema::getCacheType(), $schemaName.'.'.$objectType);
        }

        return $ldapObjectSchema;
    }
}

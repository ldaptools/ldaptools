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
use LdapTools\Event\Event;
use LdapTools\Event\EventDispatcherInterface;
use LdapTools\Event\LdapObjectSchemaEvent;
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
     * @var SchemaParserInterface The parser for the schema files.
     */
    protected $parser;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param CacheInterface $cache
     * @param SchemaParserInterface $parser
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(CacheInterface $cache, SchemaParserInterface $parser, EventDispatcherInterface $dispatcher)
    {
        $this->cache = $cache;
        $this->parser = $parser;
        $this->dispatcher = $dispatcher;
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

        if ($this->shouldBuildCacheItem($schemaName, $cacheItem)) {
            $ldapObjectSchema = $this->parser->parse($schemaName, $objectType);
            $this->dispatcher->dispatch(new LdapObjectSchemaEvent(Event::LDAP_SCHEMA_LOAD, $ldapObjectSchema));
            $this->cache->set($ldapObjectSchema);
        } else {
            $ldapObjectSchema = $this->cache->get(LdapObjectSchema::getCacheType(), $cacheItem);
        }

        return $ldapObjectSchema;
    }

    /**
     * Whether or not the item needs to be parsed and cached.
     *
     * @param string $schemaName
     * @param string $cacheItem
     * @return bool
     */
    protected function shouldBuildCacheItem($schemaName, $cacheItem)
    {
        $cacheOutOfDate = false;
        if ($this->cache->getUseAutoCache()) {
            $lastModTime = $this->parser->getSchemaModificationTime($schemaName);
            $cacheCreationTime = $this->cache->getCacheCreationTime(LdapObjectSchema::getCacheType(), $cacheItem);
            $cacheOutOfDate = (!$lastModTime || ($lastModTime > $cacheCreationTime));
        }

        return ($cacheOutOfDate || !$this->cache->contains(LdapObjectSchema::getCacheType(), $cacheItem));
    }
}

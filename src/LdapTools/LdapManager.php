<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools;

use LdapTools\Connection\LdapConnection;
use LdapTools\Event\EventDispatcherInterface;
use LdapTools\Event\SymfonyEventDispatcher;
use LdapTools\Factory\AttributeConverterFactory;
use LdapTools\Factory\CacheFactory;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Factory\SchemaParserFactory;
use LdapTools\Object\LdapObject;
use LdapTools\Object\LdapObjectCreator;
use LdapTools\Object\LdapObjectManager;
use LdapTools\Object\LdapObjectRepository;
use LdapTools\Query\LdapQueryBuilder;

/**
 * The LDAP Manager provides easy access to the various tools in the library.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapManager
{
    /**
     * @var Configuration The main configuration for the library.
     */
    protected $config;

    /**
     * @var LdapConnection[] An array of "domain name" => "LdapConnection" pairs.
     */
    protected $connections;

    /**
     * @var string The current domain in focus for calls to this class.
     */
    protected $context;

    /**
     * @var DomainConfiguration[] An array of "domain name" => "DomainConfiguration" pairs.
     */
    protected $domains;

    /**
     * @var Schema\Parser\SchemaParserInterface
     */
    protected $schemaParser;

    /**
     * @var Cache\CacheInterface
     */
    protected $cache;

    /**
     * @var Factory\LdapObjectSchemaFactory
     */
    protected $schemaFactory;

    /**
     * @var LdapObjectManager[]
     */
    protected $ldapObjectManager = [];

    /**
     * @var SymfonyEventDispatcher
     */
    protected $dispatcher;

    /**
     * @param Configuration $config
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(Configuration $config, EventDispatcherInterface $dispatcher = null)
    {
        $this->config = $config;
        $this->domains = $config->getDomainConfiguration();

        if (empty($this->domains)) {
            throw new \RuntimeException("Your configuration must have at least one domain.");
        }
        $this->dispatcher = $dispatcher ?: new SymfonyEventDispatcher();
        $this->context = $this->config->getDefaultDomain() ?: array_keys($this->domains)[0];
        $this->registerAttributeConverters($config->getAttributeConverters());

        // Pre-populate the connections array. They will be instantiated as needed.
        foreach (array_keys($this->domains) as $domain) {
            $this->connections[$domain] = null;
        }
    }

    /**
     * Get the domain name currently being used.
     *
     * @return string
     */
    public function getDomainContext()
    {
        return $this->context;
    }

    /**
     * Get all of the domain names that are loaded.
     *
     * @return string[]
     */
    public function getDomains()
    {
        return array_keys($this->domains);
    }

    /**
     * Switch the context of the LdapManager by passing a domain name (ie. 'example.local').
     *
     * @param string $domain
     * @return $this
     * @throws \InvalidArgumentException If the domain name is not recognized.
     */
    public function switchDomain($domain)
    {
        if (!array_key_exists($domain, $this->domains)) {
            throw new \InvalidArgumentException(sprintf('Domain "%s" is not valid.', $domain));
        }
        $this->context = $domain;

        return $this;
    }

    /**
     * Get the Ldap Connection object.
     *
     * @return \LdapTools\Connection\LdapConnectionInterface
     */
    public function getConnection()
    {
        if (!$this->connections[$this->context]) {
            $this->connections[$this->context] = new LdapConnection($this->domains[$this->context]);
        }

        return $this->connections[$this->context];
    }

    /**
     * Get a LdapQueryBuilder object.
     *
     * @return \LdapTools\Query\LdapQueryBuilder
     * @throws \InvalidArgumentException When the query type is not recognized.
     */
    public function buildLdapQuery()
    {
        return new LdapQueryBuilder($this->getConnection(), $this->getSchemaFactory());
    }

    /**
     * Get a LdapObjectCreator object.
     *
     * @return LdapObjectCreator
     */
    public function createLdapObject()
    {
        return new LdapObjectCreator($this->getConnection(), $this->getSchemaFactory(), $this->getEventDispatcher());
    }

    /**
     * Get a repository for a specific LDAP object type.
     *
     * @param string $type
     * @return LdapObjectRepository
     */
    public function getRepository($type)
    {
        try {
            $ldapObjectSchema = $this->getLdapObjectSchema($type);
            $repositoryClass = $ldapObjectSchema->getRepository();

            $repository = new $repositoryClass($ldapObjectSchema, $this->getConnection());
        } catch (\ErrorException $e) {
            throw new \RuntimeException(sprintf('Unable to load Repository for type "%s": %s', $type, $e->getMessage()));
        }
        if (!($repository instanceof LdapObjectRepository)) {
            throw new \RuntimeException('Your repository class must extend \LdapTools\LdapObjectRepository.');
        }

        return $repository;
    }

    /**
     * Sends a LdapObject back to LDAP so the changes can be written to the directory.
     *
     * @param LdapObject $ldapObject
     * @return $this
     */
    public function persist(LdapObject $ldapObject)
    {
        $this->getObjectManager()->persist($ldapObject);

        return $this;
    }

    /**
     * Delete an object from LDAP.
     *
     * @param LdapObject $ldapObject
     * @return $this
     */
    public function delete(LdapObject $ldapObject)
    {
        $this->getObjectManager()->delete($ldapObject);

        return $this;
    }

    /**
     * Move an object in LDAP from one container/OU to another.
     *
     * @param LdapObject $ldapObject
     * @param string $container The container/OU in DN format.
     * @return $this
     */
    public function move(LdapObject $ldapObject, $container)
    {
        $this->getObjectManager()->move($ldapObject, $container);

        return $this;
    }

    /**
     * A shorthand method for verifying a username/password combination against LDAP. Optionally you can pass a variable
     * to store the error message or error number returned from LDAP for more detailed information on authentication
     * failures.
     *
     * @param string $user
     * @param string $password
     * @param bool|string $errorMessage Optionally, this will store the LDAP error message on failure.
     * @param bool|string $errorNumber Optionally, this will store the LDAP error number on failure.
     * @return bool
     */
    public function authenticate($user, $password, &$errorMessage = false, &$errorNumber = false)
    {
        return $this->getConnection()->authenticate($user, $password, $errorMessage, $errorNumber);
    }

    /**
     * Retrieve the schema factory instance.
     *
     * @return LdapObjectSchemaFactory
     */
    public function getSchemaFactory()
    {
        if (!$this->schemaFactory) {
            $this->schemaFactory = new LdapObjectSchemaFactory(
                $this->getCache(), $this->getSchemaParser(), $this->getEventDispatcher()
            );
        }

        return $this->schemaFactory;
    }

    /**
     * Retrieve the cache instance.
     *
     * @return Cache\CacheInterface
     */
    public function getCache()
    {
        if (!$this->cache) {
            $this->cache = CacheFactory::get($this->config->getCacheType(), $this->config->getCacheOptions());
        }

        return $this->cache;
    }

    /**
     * Retrieve the schema parser instance.
     *
     * @return Schema\Parser\SchemaParserInterface
     */
    public function getSchemaParser()
    {
        if (!$this->schemaParser) {
            $this->schemaParser = SchemaParserFactory::get(
                $this->config->getSchemaFormat(),
                $this->config->getSchemaFolder()
            );
        }

        return $this->schemaParser;
    }

    /**
     * Get the Event Dispatcher instance.
     *
     * @return Event\EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Retrieve the LdapObjectManager for the current domain context.
     *
     * @return LdapObjectManager
     */
    protected function getObjectManager()
    {
        if (!isset($this->ldapObjectManager[$this->context])) {
            $this->ldapObjectManager[$this->context] = new LdapObjectManager(
                $this->getConnection(),
                $this->getSchemaFactory(),
                $this->getEventDispatcher()
            );
        }

        return $this->ldapObjectManager[$this->context];
    }

    /**
     * Get the LDAP object schema from the factory by its type.
     *
     * @param string $type
     * @return Schema\LdapObjectSchema
     */
    protected function getLdapObjectSchema($type)
    {
        return $this->getSchemaFactory()->get($this->getConnection()->getSchemaName(), $type);
    }

    /**
     * Register any explicitly defined converters.
     *
     * @param array $attributeConverters
     */
    protected function registerAttributeConverters(array $attributeConverters)
    {
        foreach ($attributeConverters as $name => $class) {
            AttributeConverterFactory::register($name, $class);
        }
    }
}

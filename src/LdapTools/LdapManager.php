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
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Factory\AttributeConverterFactory;
use LdapTools\Factory\CacheFactory;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Factory\SchemaParserFactory;
use LdapTools\Ldif\Ldif;
use LdapTools\Object\LdapObject;
use LdapTools\Object\LdapObjectCreator;
use LdapTools\Object\LdapObjectManager;
use LdapTools\Object\LdapObjectRepository;
use LdapTools\Operation\AuthenticationOperation;
use LdapTools\Operation\AuthenticationResponse;
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
     * @param Configuration $config
     * @param Connection\LdapConnectionInterface[] $connections
     */
    public function __construct(Configuration $config, LdapConnectionInterface ...$connections)
    {
        $this->config = $config;
        $this->domains = $config->getDomainConfiguration();

        if (empty($this->domains) && empty($connections)) {
            throw new \RuntimeException("Your configuration must have at least one domain.");
        }
        $this->registerAttributeConverters($config->getAttributeConverters());

        // Pre-populate the connections array. They will be instantiated as needed.
        foreach (array_keys($this->domains) as $domain) {
            $this->connections[$domain] = null;
        }
        $this->addConnection(...$connections);

        $this->context = array_keys($this->domains)[0];
        if (!empty($this->config->getDefaultDomain())) {
            $this->validateDomainName($this->config->getDefaultDomain());
            $this->context = $this->config->getDefaultDomain();
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
     * @throws InvalidArgumentException If the domain name is not recognized.
     */
    public function switchDomain($domain)
    {
        $this->validateDomainName($domain);
        $this->context = $domain;

        return $this;
    }

    /**
     * Explicitly add connections using already constructed connection objects.
     *
     * @param Connection\LdapConnectionInterface[] $connections
     * @return $this
     */
    public function addConnection(LdapConnectionInterface ...$connections)
    {
        foreach ($connections as $connection) {
            $this->domains[$connection->getConfig()->getDomainName()] = $connection->getConfig();
            $this->connections[$connection->getConfig()->getDomainName()] = $connection;
        }

        return $this;
    }

    /**
     * Get the Ldap Connection object. By default it will get the connection of the domain currently in context. To get
     * a different domain connection pass the domain name as a parameter.
     *
     * @param null|string $domain
     * @return Connection\LdapConnectionInterface
     */
    public function getConnection($domain = null)
    {
        $domain = $domain ?: $this->context;
        $this->validateDomainName($domain);

        if (!$this->connections[$domain]) {
            $this->connections[$domain] = new LdapConnection(
                $this->domains[$domain],
                $this->config->getEventDispatcher(),
                $this->config->getLogger(),
                $this->getCache()
            );
        }

        return $this->connections[$domain];
    }

    /**
     * Get a LdapQueryBuilder object.
     *
     * @return \LdapTools\Query\LdapQueryBuilder
     */
    public function buildLdapQuery()
    {
        return new LdapQueryBuilder($this->getConnection(), $this->getSchemaFactory());
    }

    /**
     * Get a LdapObjectCreator object.
     *
     * @param string|null $type
     * @return LdapObjectCreator
     */
    public function createLdapObject($type = null)
    {
        $creator = new LdapObjectCreator(
            $this->getConnection(),
            $this->getSchemaFactory(),
            $this->config->getEventDispatcher()
        );
        if ($type) {
            $creator->create($type);
        }

        return $creator;
    }

    /**
     * Get a LDIF object to help build a LDIF file.
     *
     * @return Ldif
     */
    public function createLdif()
    {
        return new Ldif($this->getConnection(), $this->getSchemaFactory());
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
            if (!class_exists($repositoryClass)) {
                throw new \RuntimeException(sprintf('Repository class "%s" not found.', $repositoryClass));
            }
            $repository = new $repositoryClass($ldapObjectSchema, $this->getConnection());
            if (!($repository instanceof LdapObjectRepository)) {
                throw new \RuntimeException('Your repository class must extend \LdapTools\Object\LdapObjectRepository.');
            }
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf('Unable to load Repository for type "%s": %s', $type, $e->getMessage()));
        } catch (\Throwable $e) {
            throw new \RuntimeException(sprintf('Unable to load Repository for type "%s": %s', $type, $e->getMessage()));
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
     * Delete an object from LDAP. Optionally you can set the second argument to true which sends a control to LDAP to
     * perform a recursive deletion. This is helpful in the case of deleting an OU with with objects underneath it. By
     * setting the second parameter to true the OU and all objects below it would be deleted. Use with care!
     *
     * If recursive deletion does not work, first check that 'accidental deletion' is not enabled on the object (AD).
     *
     * @param LdapObject $ldapObject
     * @param bool $recursively
     * @return $this
     */
    public function delete(LdapObject $ldapObject, $recursively = false)
    {
        $this->getObjectManager()->delete($ldapObject, $recursively);

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
     * Restore a deleted LDAP object. Optionally specify where to restore it to (full DN of a container/OU).
     *
     * @param LdapObject $ldapObject
     * @param string|null $container The container/OU in DN format of where it should be restored to.
     * @return $this
     */
    public function restore(LdapObject $ldapObject, $container = null)
    {
        $this->getObjectManager()->restore($ldapObject, $container);

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
        $operation = new AuthenticationOperation($user, $password);
        /** @var AuthenticationResponse $response */
        $response = $this->getConnection()->execute($operation);

        if ($errorMessage !== false) {
            $errorMessage = $response->getErrorMessage();
        }
        if ($errorNumber !== false) {
            $errorNumber = $response->getErrorCode();
        }

        return $response->isAuthenticated();
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
                $this->getCache(), $this->getSchemaParser(), $this->config->getEventDispatcher()
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
        if (!$this->config->getCache()) {
            // This will be removed eventually. The default cache will be instantiated directly in the config class.
            $this->config->setCache(CacheFactory::get($this->config->getCacheType(), $this->config->getCacheOptions()));
        }

        return $this->config->getCache();
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
     * Get the event dispatcher instance.
     *
     * @return Event\EventDispatcherInterface|null
     */
    public function getEventDispatcher()
    {
        return $this->config->getEventDispatcher();
    }

    /**
     * Validates that the domain name actually exists.
     *
     * @param string $domain
     */
    protected function validateDomainName($domain)
    {
        if (!array_key_exists($domain, $this->domains)) {
            throw new InvalidArgumentException(sprintf(
                'Domain "%s" is not valid. Valid domains are: %s',
                $domain,
                implode(', ', array_keys($this->domains))
            ));
        }
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
                $this->config->getEventDispatcher()
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
        return $this->getSchemaFactory()->get($this->getConnection()->getConfig()->getSchemaName(), $type);
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

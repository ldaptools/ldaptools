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

use LdapTools\Cache\CacheInterface;
use LdapTools\Event\EventDispatcherInterface;
use LdapTools\Event\SymfonyEventDispatcher;
use LdapTools\Exception\ConfigurationException;
use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Factory\SchemaParserFactory;
use LdapTools\Factory\CacheFactory;
use LdapTools\Log\LdapLoggerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Represents the configuration for LdapTools.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class Configuration
{
    use ConfigurationParseTrait;

    /**
     * @var array Maps the config values to their array key values in this class.
     */
    protected $yamlConfigMap = [
        'schema_folder' => 'schemaFolder',
        'schema_format' => 'schemaFormat',
        'cache_options' => 'cacheOptions',
        'cache_type' => 'cacheType',
        'default_domain' => 'defaultDomain',
        'attribute_converters' => 'attributeConverters',
    ];

    /**
     * @var array
     */
    protected $config = [
        'defaultDomain' => '',
        'schemaFolder' => '/../../resources/schema',
        'schemaFormat' => SchemaParserFactory::TYPE_YML,
        'cacheOptions' => [],
        'cacheType' => CacheFactory::TYPE_NONE,
        'attributeConverters' => [],
    ];

    /**
     * @var DomainConfiguration[] DomainConfiguration objects in the form of 'domainName' => object.
     */
    protected $domains = [];

    /**
     * @var EventDispatcherInterface The event dispatcher that should be used.
     */
    protected $eventDispatcher;

    /**
     * @var null|LdapLoggerInterface The logger that should be used.
     */
    protected $logger;

    /**
     * @var CacheInterface|null The cache that should be used.
     */
    protected $cache;

    /**
     * @param DomainConfiguration ...$domain
     */
    public function __construct(DomainConfiguration ...$domains)
    {
        if (!empty($domains)) {
            $this->addDomain(...$domains);
        }

        $this->config['cacheOptions']['cache_folder'] = sys_get_temp_dir();
        $this->config['schemaFolder'] = __DIR__ . $this->config['schemaFolder'];
        $this->eventDispatcher = new SymfonyEventDispatcher();
    }

    /**
     * Get the DomainConfiguration for a specific domain, or an array of all DomainConfiguration objects if none is
     * specified.
     *
     * @param null|string $domain
     * @return DomainConfiguration[]|DomainConfiguration
     */
    public function getDomainConfiguration($domain = null)
    {
        if ($domain && isset($this->domains[$domain])) {
            return $this->domains[$domain];
        } elseif ($domain) {
            throw new InvalidArgumentException(sprintf('Domain "%s" not found.', $domain));
        } else {
            return $this->domains;
        }
    }

    /**
     * Add domain configurations. Accepts an arbitrary amount of domain configurations.
     *
     * @param DomainConfiguration $domains,...
     * @return $this
     */
    public function addDomain(DomainConfiguration ...$domains)
    {
        foreach ($domains as $domain) {
            $this->domains[$domain->getDomainName()] = $domain;
        }

        return $this;
    }

    /**
     * Set the caching options for the cache type.
     *
     * @param array
     * @return $this
     * @deprecated This function will be removed in a later version. Use setCache() instead.
     */
    public function setCacheOptions($options)
    {
        trigger_error('The '.__METHOD__.' method is deprecated and will be removed in a later version. Use setCache() instead.', E_USER_DEPRECATED);

        $this->config['cacheOptions'] = $options;

        return $this;
    }

    /**
     * Get the cache options for the cache type.
     *
     * @return array
     * @deprecated This function will be removed in a later version.
     */
    public function getCacheOptions()
    {
        return $this->config['cacheOptions'];
    }

    /**
     * Set the location where the schema definitions exist.
     *
     * @param $folder string The full path to the folder.
     * @return $this
     */
    public function setSchemaFolder($folder)
    {
        $this->config['schemaFolder'] = $folder;

        return $this;
    }

    /**
     * Get the location where the schema definitions exist.
     *
     * @return string
     */
    public function getSchemaFolder()
    {
        return $this->config['schemaFolder'];
    }

    /**
     * Set the schema definition format.
     *
     * @param $type string The schema type (ie. yml).
     * @return $this
     * @throws ConfigurationException
     */
    public function setSchemaFormat($type)
    {
        if (!defined('\LdapTools\Factory\SchemaParserFactory::TYPE_'.strtoupper($type))) {
            throw new ConfigurationException(sprintf('Unknown schema format "%s".', $type));
        }
        $this->config['schemaFormat'] = $type;

        return $this;
    }

    /**
     * Get the schema definition format.
     *
     * @return string
     */
    public function getSchemaFormat()
    {
        return $this->config['schemaFormat'];
    }

    /**
     * Set the cache to use.
     *
     * @param CacheInterface|null $cache
     * @return $this
     */
    public function setCache(CacheInterface $cache = null)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Get the Cache to use.
     *
     * @return CacheInterface|null
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Set the cache type to use.
     *
     * @param $type
     * @return $this
     * @throws ConfigurationException
     * @deprecated This function will be removed in a later version. Use setCache() instead.
     */
    public function setCacheType($type)
    {
        trigger_error('The '.__METHOD__.' method is deprecated and will be removed in a later version. Use setCache() instead.', E_USER_DEPRECATED);

        if (!defined('\LdapTools\Factory\CacheFactory::TYPE_'.strtoupper($type))) {
            throw new ConfigurationException(sprintf('Unknown cache type "%s".', $type));
        }
        $this->config['cacheType'] = $type;

        return $this;
    }

    /**
     * Get the configured cache type.
     *
     * @deprecated This function will be removed in a later version.
     * @return string
     */
    public function getCacheType()
    {
        return $this->config['cacheType'];
    }

    /**
     * Set the default domain that should be used by the LdapManager. In the absence of multiple domains,
     * this does not have to be set.
     *
     * @param $domain
     * @return $this
     * @throws ConfigurationException
     */
    public function setDefaultDomain($domain)
    {
        $this->config['defaultDomain'] = $domain;

        return $this;
    }

    /**
     * Get the name of the default domain to be used when there are multiple domains.
     *
     * @return string
     */
    public function getDefaultDomain()
    {
        return $this->config['defaultDomain'];
    }

    /**
     * Set attribute converters that should be registered. In the form:
     *
     *      [ 'converter_name' => '\Full\Class\Name' ]
     *
     * @param array $attributeConverters
     * @return $this
     */
    public function setAttributeConverters(array $attributeConverters)
    {
        $this->config['attributeConverters'] = $attributeConverters;

        return $this;
    }

    /**
     * Get the attribute converters that will be registered.
     *
     * @return array
     */
    public function getAttributeConverters()
    {
        return $this->config['attributeConverters'];
    }

    /**
     * Set an event dispatcher, other than the default, to be used.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @return $this
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * Get the explicitly set event dispatcher to be used.
     *
     * @return EventDispatcherInterface|null
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * Set a logger to be used.
     *
     * @param LdapLoggerInterface $logger
     * @return $this
     */
    public function setLogger(LdapLoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get the logger to be used.
     *
     * @return LdapLoggerInterface|null
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Load the LDAP configuration from a YAML file. See the 'resources/config' folder for an example.
     *
     * @param string $file The path to the file.
     * @return $this
     * @throws ConfigurationException
     */
    public function load($file)
    {
        if (!is_readable($file)) {
            throw new ConfigurationException(sprintf("Cannot read configuration file: %s", $file));
        }

        try {
            $config = Yaml::parse(file_get_contents($file));
        } catch (ParseException $e) {
            throw new ConfigurationException('Error in configuration file: %s', $e->getMessage());
        }

        return $this->loadFromArray($config);
    }

    /**
     * Load the configuration from an array of values. They should be in the same format/name as the YAML format.
     * ie. [ 'general' => [ ... ], 'domains' => [ ... ] ]
     *
     * @param array $config
     * @return $this
     * @throws ConfigurationException
     */
    public function loadFromArray(array $config)
    {
        if (isset($config['domains'])) {
            $this->loadDomainConfiguration($config);
        }

        if (isset($config['general'])) {
            $general = $config['general'];
            $generalConfig = $this->getParsedConfig(
                $general,
                $this->config,
                $this->yamlConfigMap,
                []
            );
            $this->setCache(CacheFactory::get($generalConfig['cacheType'], $generalConfig['cacheOptions']));
            unset($generalConfig['cacheType']);
            unset($generalConfig['cacheOptions']);
            $this->setParsedConfig($generalConfig);
        }

        return $this;
    }

    /**
     * Iterates through and loads the domain section of the configuration.
     *
     * @param $config
     * @throws ConfigurationException
     */
    protected function loadDomainConfiguration(array $config)
    {
        try {
            foreach ($config['domains'] as $domain => $options) {
                $domain = new DomainConfiguration();
                $domain->load($options);
                $this->addDomain($domain);
            }
        } catch (ConfigurationException $e) {
            throw new ConfigurationException(sprintf("Error in domain config section: %s", $e->getMessage()));
        }
    }
}

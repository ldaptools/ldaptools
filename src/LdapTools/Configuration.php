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

use LdapTools\Exception\ConfigurationException;
use LdapTools\Factory\SchemaParserFactory;
use LdapTools\Factory\CacheFactory;
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
    ];

    /**
     * @var DomainConfiguration[] DomainConfiguration objects in the form of 'domainName' => object.
     */
    protected $domains = [];

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
    }

    /**
     * Get the DomainConfiguration for a specific domain, or an array of all DomainConfiguration objects if none is
     * specified.
     *
     * @param null|string $domain
     * @return array|DomainConfiguration
     */
    public function getDomainConfiguration($domain = null)
    {
        if ($domain && isset($this->domains[$domain])) {
            return $this->domains[$domain];
        } elseif ($domain) {
            throw new \InvalidArgumentException(sprintf('Domain "%s" not found.', $domain));
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
     */
    public function setCacheOptions($options)
    {
        $this->config['cacheOptions'] = $options;

        return $this;
    }

    /**
     * Get the cache options for the cache type.
     *
     * @return array
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
     * Set the cache type to use.
     *
     * @param $type
     * @return $this
     * @throws ConfigurationException
     */
    public function setCacheType($type)
    {
        if (!defined('\LdapTools\Factory\CacheFactory::TYPE_'.strtoupper($type))) {
            throw new ConfigurationException(sprintf('Unknown cache type "%s".', $type));
        }
        $this->config['cacheType'] = $type;

        return $this;
    }

    /**
     * Get the configured cache type.
     *
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
        if (!isset($this->domains[$domain])) {
            throw new ConfigurationException(sprintf('The domain "%s" is not valid.', $domain));
        }
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

        $this->loadDomainConfiguration($config);

        if (isset($config['general'])) {
            $generalConfig = $this->getParsedConfig(
                $config['general'],
                $this->config,
                $this->yamlConfigMap,
                []
            );
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
        if (!isset($config['domains'])) {
            throw new ConfigurationException("Your configuration file must have at least one domain.");
        }

        try {
            foreach ($config['domains'] as $domain => $options) {
                $domain = new DomainConfiguration();
                $domain->load($options);
                $this->addDomain($domain);
            }
        } catch (ConfigurationException $e) {
            throw new ConfigurationException(sprintf("Error in domain config section: ", $e->getMessage()));
        }
    }
}

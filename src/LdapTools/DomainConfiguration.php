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
use LdapTools\Connection\LdapServerPool;
use LdapTools\Exception\ConfigurationException;

/**
 * Represents a domain configuration.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class DomainConfiguration
{
    use ConfigurationParseTrait;

    /**
     * The standard LDAP port number.
     */
    const PORT = 389;

    /**
     * The standard SSL LDAP port number.
     */
    const PORT_SSL = 636;

    /**
     * @var array Maps YAML domain config values to their array key values.
     */
    protected $yamlConfigMap = [
        'domain_name' => 'domainName',
        'servers' => 'servers',
        'use_tls' => 'useTls',
        'use_ssl' => 'useSsl',
        'port' => 'port',
        'base_dn' => 'baseDn',
        'page_size' => 'pageSize',
        'username' => 'username',
        'password' => 'password',
        'ldap_type' => 'ldapType',
        'lazy_bind' => 'lazyBind',
        'schema_name' => 'schemaName',
        'server_selection' => 'serverSelection',
        'encoding' => 'encoding',
        'bind_format' => 'bindFormat',
    ];

    /**
     * @var array These values must be set for the configuration to be valid.
     */
    protected $required = [
        'domainName',
        'username',
        'password',
    ];

    /**
     * @var array The configuration values.
     */
    protected $config = [
        'username' => '',
        'password' => '',
        'domainName' => '',
        'baseDn' => '',
        'useSsl' => false,
        'useTls' => false,
        'port' => self::PORT,
        'pageSize' => 1000,
        'servers' => [],
        'ldapType' => LdapConnection::TYPE_AD,
        'lazyBind' => false,
        'schemaName' => '',
        'serverSelection' => LdapServerPool::SELECT_ORDER,
        'encoding' => 'UTF-8',
        'bindFormat' => '',
    ];

    /**
     * @param string $domainName
     */
    public function __construct($domainName = '')
    {
        $this->setDomainName($domainName);
    }

    /**
     * Get the username to use when binding.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->config['username'];
    }

    /**
     * Set the username to use when binding.
     *
     * @param string $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->config['username'] = $username;

        return $this;
    }

    /**
     * Set the password to use when binding.
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->config['password'];
    }

    /**
     * Set the password to use when binding.
     *
     * @param string $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->config['password'] = $password;

        return $this;
    }

    /**
     * Get the fully qualified domain name.
     *
     * @return string
     */
    public function getDomainName()
    {
        return $this->config['domainName'];
    }

    /**
     * Set the fully qualified domain name (ie. "example.com").
     *
     * @param string $domainName
     * @return $this
     */
    public function setDomainName($domainName)
    {
        $this->config['domainName'] = $domainName;

        return $this;
    }

    /**
     * The base distinguished name in use.
     *
     * @return string
     */
    public function getBaseDn()
    {
        return $this->config['baseDn'];
    }

    /**
     * The base distinguished name to use (ie. "dc=example,dc=com").
     *
     * @param string $baseDn
     * @return $this
     */
    public function setBaseDn($baseDn)
    {
        $this->config['baseDn'] = $baseDn;

        return $this;
    }

    /**
     * Whether SSL will be used for the connection or not.
     *
     * @return bool
     */
    public function getUseSsl()
    {
        return $this->config['useSsl'];
    }

    /**
     * Set whether SSL will be used for the connection or not.
     *
     * @param bool $useSsl
     * @return $this
     */
    public function setUseSsl($useSsl)
    {
        $this->config['useSsl'] = (bool) $useSsl;

        return $this;
    }

    /**
     * Get whether TLS will be used for the connection or not.
     *
     * @return bool
     */
    public function getUseTls()
    {
        return $this->config['useTls'];
    }

    /**
     * Set whether TLS will be used for the connection or not.
     *
     * @param bool $useTls
     * @return $this
     */
    public function setUseTls($useTls)
    {
        $this->config['useTls'] = $useTls;

        return $this;
    }

    /**
     * Get the port number to connect to.
     *
     * @return int
     */
    public function getPort()
    {
        return $this->config['port'];
    }


    /**
     * The port number to connect to.
     *
     * @param int $port
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setPort($port)
    {
        $this->validateInteger($port, "port number");
        $this->config['port'] = $port;

        return $this;
    }

    /**
     * Get the page size for paging operations.
     *
     * @return int
     */
    public function getPageSize()
    {
        return $this->config['pageSize'];
    }

    /**
     * Set the page size for paging operations.
     *
     * @param int $pageSize
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setPageSize($pageSize)
    {
        $this->validateInteger($pageSize, "page size");
        $this->config['pageSize'] = $pageSize;

        return $this;
    }

    /**
     * Get the LDAP server names or IP addresses.
     *
     * @return array
     */
    public function getServers()
    {
        return $this->config['servers'];
    }

    /**
     * Set the LDAP servers as an array of names or IP addresses.
     *
     * @param array $servers
     * @return $this
     */
    public function setServers(array $servers)
    {
        $this->config['servers'] = $servers;

        return $this;
    }

    /**
     * Get the server selection method to be used.
     *
     * @return mixed
     */
    public function getServerSelection()
    {
        return $this->config['serverSelection'];
    }

    /**
     * Set the server selection method that should be used. Let the LdapServerPool take care of validation.
     *
     * @param $type
     * @return $this
     * @throws ConfigurationException
     */
    public function setServerSelection($type)
    {
        $this->config['serverSelection'] = $type;

        return $this;
    }

    /**
     * Get the LDAP type set for this domain (ie. Active Directory, OpenLDAP, etc).
     *
     * @return string
     */
    public function getLdapType()
    {
        return $this->config['ldapType'];
    }

    /**
     * Set the LDAP type for this domain.
     *
     * @param string $ldapType The LDAP type.
     * @return $this
     */
    public function setLdapType($ldapType)
    {
        if (!defined('\LdapTools\Connection\LdapConnection::TYPE_'.strtoupper($ldapType))) {
            throw new \InvalidArgumentException(sprintf('Invalid LDAP type "%s".', $ldapType));
        }
        $this->config['ldapType'] = strtolower($ldapType);

        return $this;
    }

    /**
     * Get whether or not the connection should bind on construction, or what until necessary.
     *
     * @return bool
     */
    public function getLazyBind()
    {
        return $this->config['lazyBind'];
    }

    /**
     * Set whether or not the connection should bind on construction, or what until necessary.
     *
     * @param bool $lazyBind
     * @return $this
     */
    public function setLazyBind($lazyBind)
    {
        $this->config['lazyBind'] = (bool) $lazyBind;

        return $this;
    }

    /**
     * Set the schema name to use for this domain.
     *
     * @param string $schemaName
     * @return $this
     */
    public function setSchemaName($schemaName)
    {
        $this->config['schemaName'] = $schemaName;

        return $this;
    }

    /**
     * Get the schema name set for this domain.
     *
     * @return string
     */
    public function getSchemaName()
    {
        return $this->config['schemaName'];
    }

    /**
     * Set the encoding type to use.
     *
     * @param string $encoding
     * @return $this
     */
    public function setEncoding($encoding)
    {
        $this->config['encoding'] = $encoding;

        return $this;
    }

    /**
     * Get the encoding type to use.
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->config['encoding'];
    }

    /**
     * Set the format a username should be bound as.
     *
     * @param string $bindFormat
     * @return $this
     */
    public function setBindFormat($bindFormat)
    {
        $this->config['bindFormat'] = $bindFormat;

        return $this;
    }

    /**
     * Get the format a username should be bound as.
     *
     * @return string
     */
    public function getBindFormat()
    {
        return $this->config['bindFormat'];
    }

    /**
     * Load a configuration from an array of values. The keys must be the same name as their YAML config
     * names.
     *
     * @param array $config
     * @return $this
     * @throws ConfigurationException
     */
    public function load(array $config)
    {
        $config = $this->getParsedConfig(
            $config,
            $this->config,
            $this->yamlConfigMap,
            $this->required
        );
        $this->setParsedConfig($config);

        return $this;
    }

    /**
     * Checks whether all required values for the configuration have been set.
     *
     * @param array $config
     * @return bool
     */
    protected function isParsedConfigValid(array $config)
    {
        $inConfig = count(array_intersect_key(array_flip($this->required), $config));

        return $inConfig === count($this->required);
    }

    /**
     * This is a helper since an integer could simply be passed as a string, which is still valid.
     *
     * @param mixed $value
     * @param string $name
     */
    protected function validateInteger($value, $name)
    {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            throw new \InvalidArgumentException(sprintf("The %s should be an integer.", $name));
        }
    }
}

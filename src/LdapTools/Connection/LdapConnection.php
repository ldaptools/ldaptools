<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Connection;

use LdapTools\Cache\CacheInterface;
use LdapTools\Event\EventDispatcherInterface;
use LdapTools\Event\SymfonyEventDispatcher;
use LdapTools\Exception\LdapBindException;
use LdapTools\Exception\LdapConnectionException;
use LdapTools\DomainConfiguration;
use LdapTools\Log\LdapLoggerInterface;
use LdapTools\Operation\LdapOperationInterface;
use LdapTools\Utilities\LdapUtilities;

/**
 * A LDAP connection class that provides an OOP style wrapper around the ldap_* functions.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapConnection implements LdapConnectionInterface
{
    /**
     * Active Directory connection.
     */
    const TYPE_AD = 'ad';

    /**
     * OpenLDAP connection.
     */
    const TYPE_OPENLDAP = 'openldap';

    /**
     * @var bool Whether the connection is bound using a username/password
     */
    protected $isBound = false;

    /**
     * @var resource
     */
    protected $connection;

    /**
     * @var DomainConfiguration
     */
    protected $config;

    /**
     * @var string|null The LDAP server that we are currently connected to.
     */
    protected $server;

    /**
     * @var LdapServerPool
     */
    protected $serverPool;

    /**
     * @var AD\ADBindUserStrategy|BindUserStrategy
     */
    protected $usernameFormatter;

    /**
     * @var \LdapTools\Object\LdapObject|null
     */
    protected $rootDse;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var LdapLoggerInterface|null
     */
    protected $logger;

    /**
     * @var CacheInterface|null
     */
    protected $cache;

    /**
     * @var \DateTime|null
     */
    protected $lastActivity;

    /**
     * @param DomainConfiguration $config
     * @param EventDispatcherInterface|null $dispatcher
     * @param LdapLoggerInterface|null $logger
     * @param CacheInterface|null $cache
     */
    public function __construct(DomainConfiguration $config, EventDispatcherInterface $dispatcher = null, LdapLoggerInterface $logger = null, CacheInterface $cache = null)
    {
        $this->usernameFormatter = BindUserStrategy::getInstance($config);
        $this->serverPool = new LdapServerPool($config);
        $this->config = $config;
        $this->dispatcher = $dispatcher ?: new SymfonyEventDispatcher();
        $this->logger = $logger;
        $this->cache = $cache;
        $this->setupOperationInvoker();

        $this->serverPool->setSelectionMethod($config->getServerSelection());
        if (!$config->getLazyBind()) {
            $this->connect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function getResource()
    {
        return $this->connection;
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDse()
    {
        if (!$this->rootDse) {
            $this->rootDse = (new RootDse($this, $this->dispatcher))->get();
        }

        return $this->rootDse;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->isBound) {
            @ldap_close($this->connection);
            $this->isBound = false;
            $this->server = null;
            $this->lastActivity = null;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function connect($username = null, $password = null, $anonymous = false, $server = null)
    {
        $this->initiateLdapConnection($server);

        $username = $this->usernameFormatter->getUsername($username ?: $this->config->getUsername());
        $password = $password ?: $this->config->getPassword();

        $this->bind($username, $password, $anonymous);
        $this->lastActivity = new \DateTime();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isBound()
    {
        return $this->isBound;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastError()
    {
        return LastErrorStrategy::getInstance($this->config->getLdapType(), $this->connection)->getLastErrorMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedErrorNumber()
    {
        return LastErrorStrategy::getInstance($this->config->getLdapType(), $this->connection)->getExtendedErrorNumber();
    }

    /**
     * {@inheritdoc}
     */
    public function getDiagnosticMessage()
    {
        return LastErrorStrategy::getInstance($this->config->getLdapType(), $this->connection)->getDiagnosticMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LdapOperationInterface $operation)
    {
        $result = $this->config->getOperationInvoker()->execute($operation);
        $this->lastActivity = new \DateTime();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdleTime()
    {
        if (is_null($this->lastActivity)) {
            $idleTime = 0;
        } else {
            $idleTime = (new \DateTime())->getTimestamp() - $this->lastActivity->getTimestamp();
        }
        
        return $idleTime;
    }

    /**
     * {@inheritdoc}
     */
    public function setControl(LdapControl $control)
    {
        // To set a a server control we must first be bound...
        if (!$this->isBound()) {
            $this->connect();
        }

        if (!@ldap_set_option($this->connection, LDAP_OPT_SERVER_CONTROLS, [$control->toArray()]) && $control->getCriticality()) {
            throw new LdapConnectionException(sprintf('Unable to set control for OID "%s".', $control->getOid()));
        }
    }

    /**
     * Makes the initial connection to LDAP, sets connection options, and starts TLS if specified.
     *
     * @param null|string $server
     * @throws LdapConnectionException
     */
    protected function initiateLdapConnection($server = null)
    {
        list($ldapUrl, $server) = $this->getLdapUrl($server);

        $this->connection = @ldap_connect($ldapUrl);
        if (!$this->connection) {
            throw new LdapConnectionException(
                sprintf("Failed to initiate LDAP connection with URI: %s", $ldapUrl)
            );
        }

        foreach ($this->config->getLdapOptions() as $option => $value) {
            if (!ldap_set_option($this->connection, $option, $value)) {
                throw new LdapConnectionException("Failed to set LDAP connection option.");
            }
        }

        if ($this->config->getUseTls() && !@ldap_start_tls($this->connection)) {
            throw new LdapConnectionException(
                sprintf("Failed to start TLS: %s", $this->getLastError()),
                $this->getExtendedErrorNumber()
            );
        }

        $this->server = $server;
    }

    /**
     * Binds to LDAP with the supplied credentials or anonymously if specified.
     *
     * @param string $username The username to bind with.
     * @param string $password The password to bind with.
     * @param bool $anonymous Whether this is an anonymous bind attempt.
     * @throws LdapBindException
     */
    protected function bind($username, $password, $anonymous = false)
    {
        if ($anonymous) {
            $this->isBound = @ldap_bind($this->connection);
        } else {
            $this->isBound = @ldap_bind(
                $this->connection,
                LdapUtilities::encode($username, $this->config->getEncoding()),
                LdapUtilities::encode($password, $this->config->getEncoding())
            );
        }

        if (!$this->isBound) {
            throw new LdapBindException(
                sprintf('Unable to bind to LDAP: %s', $this->getLastError()),
                $this->getExtendedErrorNumber()
            );
        }
    }

    /**
     * Get the LDAP URL to connect to.
     *
     * @param null|string $server
     * @return string[]
     * @throws LdapConnectionException
     */
    protected function getLdapUrl($server = null)
    {
        $server = $server ?: $this->serverPool->getServer();
        $ldapUrl = ($this->config->getUseSsl() ? 'ldaps' : 'ldap').'://'.$server.':'.$this->config->getPort();

        return [$ldapUrl, $server];
    }

    /**
     * Sets the needed objects on the operation invoker.
     */
    protected function setupOperationInvoker()
    {
        $this->config->getOperationInvoker()->setEventDispatcher($this->dispatcher);
        $this->config->getOperationInvoker()->setConnection($this);
        if ($this->logger) {
            $this->config->getOperationInvoker()->setLogger($this->logger);
        }
        if ($this->cache) {
            $this->config->getOperationInvoker()->setCache($this->cache);
        }
    }
}

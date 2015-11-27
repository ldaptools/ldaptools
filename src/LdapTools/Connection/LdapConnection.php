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

use LdapTools\Event\EventDispatcherInterface;
use LdapTools\Event\SymfonyEventDispatcher;
use LdapTools\Exception\LdapBindException;
use LdapTools\Exception\LdapConnectionException;
use LdapTools\DomainConfiguration;
use LdapTools\Log\LdapLoggerInterface;
use LdapTools\Log\LogOperation;
use LdapTools\Operation\LdapOperationInterface;
use LdapTools\Operation\QueryOperation;
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
     * @var string The full LDAP URL to connect to.
     */
    protected $ldapUrl;

    /**
     * @var string|null The LDAP server that we are currently connected to.
     */
    protected $server;

    /**
     * @var LdapServerPool
     */
    protected $serverPool;

    /**
     * @var ADBindUserStrategy|BindUserStrategy
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
     * @param DomainConfiguration $config
     * @param EventDispatcherInterface $dispatcher
     * @param LdapLoggerInterface $logger
     */
    public function __construct(DomainConfiguration $config, EventDispatcherInterface $dispatcher = null, LdapLoggerInterface $logger = null)
    {
        $this->usernameFormatter = BindUserStrategy::getInstance($config);
        $this->serverPool = new LdapServerPool($config);
        $this->config = $config;
        $this->dispatcher = $dispatcher ?: new SymfonyEventDispatcher();
        $this->logger = $logger;

        $this->serverPool->setSelectionMethod($config->getServerSelection());
        if (!$config->getLazyBind()) {
            $this->connect();
        }
    }

    /**
     * {@ineritdoc}
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get a LdapObject that represents the RootDSE information for the domain.
     *
     * @return \LdapTools\Object\LdapObject
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
    public function authenticate($username, $password, &$errorMessage = false, &$errorCode = false)
    {
        if (!$username || !$password) {
            throw new \InvalidArgumentException("You must specify a username and password.");
        }
        $wasBound = $this->isBound;

        // Only catch a bind failure. Let the others through, as it's probably a sign of other issues.
        try {
            $authenticated = (bool) $this->close()->connect($username, $password);
        } catch (LdapBindException $e) {
            $authenticated = false;
            $errorMessage = ($errorMessage === false) ?: $this->getLastError();
            $errorCode = ($errorCode === false) ?: $this->getExtendedErrorNumber();
        }
        $this->close();

        // Only reconnect afterwards if the connection was bound to begin with.
        !$wasBound ?: $this->connect();

        return $authenticated;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->isBound) {
            ldap_close($this->connection);
            $this->isBound = false;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function connect($username = null, $password = null, $anonymous = false)
    {
        $this->initiateLdapConnection();

        $username = $this->usernameFormatter->getUsername($username ?: $this->config->getUsername());
        $password = $password ?: $this->config->getPassword();

        $this->bind($username, $password, $anonymous);

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
    public function getBaseDn()
    {
        if (!empty($this->config->getBaseDn())) {
            $baseDn = $this->config->getBaseDn();
        } elseif ($this->getRootDse()->has('defaultNamingContext')) {
            $baseDn = $this->getRootDse()->get('defaultNamingContext');
        } elseif ($this->getRootDse()->has('namingContexts')) {
            $baseDn =  $this->getRootDse()->get('namingContexts')[0];
        } else {
            throw new LdapConnectionException('The base DN is not defined and could not be found in the RootDSE.');
        }

        return $baseDn;
    }

    /**
     * @param LdapOperationInterface $operation
     * @return array|mixed
     * @throws LdapConnectionException
     */
    public function execute(LdapOperationInterface $operation)
    {
        $log = $this->logger ? (new LogOperation($operation))->setDomain($this->config->getDomainName()) : null;

        try {
            return $this->getLdapResponse($operation, $log);
        } catch (\Throwable $e) {
            $this->logExceptionAndThrow($e, $log);
        } catch (\Exception $e) {
            $this->logExceptionAndThrow($e, $log);
        } finally {
            $this->log($log, false);
        }
    }

    /**
     * Get the server the connection is using. If it is not yet connected this will return null.
     *
     * @return string|null
     */
    public function getServer()
    {
        return $this->server;
    }

    protected function query(QueryOperation $operation)
    {
        $allEntries = [];

        // If this is not a paged search then set this to null so it ends the loop on the first pass.
        $cookie = $operation->getUsePaging() ? '' : null;
        do {
            $this->setPagedResultsControl($operation, $cookie);

            $result = @call_user_func($operation->getLdapFunction(), $this->connection, ...$operation->getArguments());
            $allEntries = $this->processSearchResult($result, $allEntries);

            $this->setPagedResultsResponse($operation, $result, $cookie);
        } while ($cookie !== null && $cookie != '');

        return $allEntries;
    }

    /**
     * Looks for some needed query parameters and sets the defaults for this connection if they are not provided.
     *
     * @param QueryOperation $operation
     */
    protected function setQueryOperationDefaults(QueryOperation $operation)
    {
        if (is_null($operation->getPageSize())) {
            $operation->setPageSize($this->config->getPageSize());
        }
        if (is_null($operation->getBaseDn())) {
            $operation->setBaseDn($this->getBaseDn());
        }
        if (!is_null($operation->getUsePaging())) {
            $operation->setUsePaging($this->config->getUsePaging());
        }
    }

    /**
     * Makes the initial connection to LDAP, sets connection options, and starts TLS if specified.
     *
     * @throws LdapConnectionException
     */
    protected function initiateLdapConnection()
    {
        $this->connection = @ldap_connect($this->getLdapUrl(), $this->config->getPort());
        if (!$this->connection) {
            throw new LdapConnectionException(
                sprintf("Failed to initiate LDAP connection with URI: %s", $this->getLdapUrl())
            );
        }

        foreach ($this->config->getLdapOptions() as $option => $value) {
            if (!ldap_set_option($this->connection, $option, $value)) {
                throw new LdapConnectionException("Failed to set LDAP connection option.");
            }
        }

        if ($this->config->getUseTls() && !@ldap_start_tls($this->connection)) {
            throw new LdapConnectionException(sprintf("Failed to start TLS: %s", $this->getLastError()));
        }
    }

    /**
     * Binds to LDAP with the supplied credentials or anonymously if specified. You should NOT have to use this directly.
     * Instead you should call either 'connect()' or 'authenticate()'.
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
            throw new LdapBindException(sprintf('Unable to bind to LDAP: %s', $this->getLastError()));
        }
    }

    /**
     * Get the LDAP URL to connect to.
     *
     * @return string
     * @throws LdapConnectionException
     */
    protected function getLdapUrl()
    {
        if (!$this->ldapUrl) {
            $this->server = $this->serverPool->getServer();
            $this->ldapUrl = ($this->config->getUseSsl() ? 'ldaps' : 'ldap').'://'.$this->server;
        }

        return $this->ldapUrl;
    }

    /**
     * Send the LDAP pagination control if specified and check for errors.
     *
     * @param QueryOperation $operation
     * @param string $cookie
     * @throws LdapConnectionException
     */
    protected function setPagedResultsControl(QueryOperation $operation, &$cookie)
    {
        $scope = $operation->getScope();
        $usePaging = $operation->getUsePaging();
        $pageSize = $operation->getPageSize();

        if ($scope !== QueryOperation::SCOPE['BASE'] && $usePaging && !@ldap_control_paged_result($this->connection, $pageSize, false, $cookie)) {
            throw new LdapConnectionException(sprintf('Unable to enable paged results: %s', $this->getLastError()));
        } elseif ($scope == QueryOperation::SCOPE['BASE'] && $usePaging && !@ldap_control_paged_result($this->connection, 0)) {
            throw new LdapConnectionException(sprintf(
                'Unable to reset paged results for read operation: %s',
                $this->getLastError()
            ));
        }
    }

    /**
     * Retrieves the LDAP pagination cookie based on the result if specified and check for errors.
     *
     * @param QueryOperation $operation
     * @param resource $result
     * @param string $cookie
     * @throws LdapConnectionException
     */
    protected function setPagedResultsResponse(QueryOperation $operation, $result, &$cookie)
    {
        $scope = $operation->getScope();
        $usePaging = $operation->getUsePaging();

        if ($scope !== QueryOperation::SCOPE['BASE'] && $usePaging && !@ldap_control_paged_result_response($this->connection, $result, $cookie)) {
            throw new LdapConnectionException(
                sprintf('Unable to set paged results response: %s', $this->getLastError())
            );
        }
    }

    /**
     * Process a LDAP search result and merge it with the existing entries if possible.
     *
     * @param resource $result
     * @param array $allEntries
     * @return array
     * @throws LdapConnectionException
     */
    protected function processSearchResult($result, array $allEntries)
    {
        if (!$result) {
            throw new LdapConnectionException(sprintf('LDAP search failed: %s', $this->getLastError()));
        }
        $entries = @ldap_get_entries($this->connection, $result);

        if ($entries) {
            $allEntries = array_merge($allEntries, $entries);
        }

        return $allEntries;
    }

    /**
     * Send the operation to the logger if it exists.
     *
     * @param LogOperation $log
     * @param bool $start If true, this is the start of the logging. If false it is the end.
     */
    protected function log(LogOperation $log = null, $start = true)
    {
        if ($this->logger && $log && $start) {
            $this->logger->start($log);
        } elseif ($this->logger && $log && !$start) {
            $this->logger->end($log->stop());
        }
    }

    /**
     * Handles exception error message logging if logging is enabled then re-throws the exception.
     *
     * @param LogOperation|null $log
     * @param \Throwable|\Exception $exception
     * @throws LdapConnectionException
     * @throws null
     */
    protected function logExceptionAndThrow($exception, LogOperation $log = null)
    {
        if (!is_null($log)) {
            $log->setError($exception->getMessage());
        }

        // It's possible for a query operation to fail before it even begins. Trigger the log start if so.
        if ($log && $log->getStartTime() === null) {
            $this->log($log);
        }

        throw $exception;
    }

    /**
     * @param LdapOperationInterface $operation
     * @param LogOperation|null $log
     * @return array|mixed
     * @throws LdapConnectionException
     */
    protected function getLdapResponse(LdapOperationInterface $operation, LogOperation $log = null)
    {
        if ($operation instanceof QueryOperation) {
            $this->setQueryOperationDefaults($operation);
            $this->log($log);
            $result = $this->query($operation);
        } else {
            $this->log($log);
            $result = @call_user_func($operation->getLdapFunction(), $this->connection, ...$operation->getArguments());
        }

        if ($result === false) {
            throw new LdapConnectionException(sprintf(
                'LDAP %s Operation Error: %s',
                $operation->getName(),
                $this->getLastError()
            ));
        }

        return $result;
    }
}

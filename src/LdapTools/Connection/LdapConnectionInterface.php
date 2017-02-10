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

use LdapTools\DomainConfiguration;
use LdapTools\Exception\LdapConnectionException;
use LdapTools\Operation\LdapOperationInterface;

/**
 * An interface for LDAP Connection classes.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface LdapConnectionInterface
{
    /**
     * Get the DomainConfiguration instance in use by the connection.
     *
     * @return DomainConfiguration
     */
    public function getConfig();

    /**
     * Get the LDAP connection resource.
     *
     * @return resource
     */
    public function getResource();

    /**
     * Connect and bind to LDAP.
     *
     * @param string|null $username The username to connect with. If not specified, the one in the config is used.
     * @param string|null $password The password for the username.
     * @param bool $anonymous Whether this is an attempt to bind anonymously, ignoring the username and password.
     * @param string|null $server The server to connect to. If not specified, the server(s) in the config are used.
     * @return $this
     */
    public function connect($username = null, $password = null, $anonymous = false, $server = null);

    /**
     * Execute an operation against LDAP (Add, Modify, Delete, Move, Query, etc).
     *
     * @param LdapOperationInterface $operation
     * @return mixed
     */
    public function execute(LdapOperationInterface $operation);

    /**
     * If the connection is bound, this closes the LDAP connection.
     *
     * @return $this
     */
    public function close();

    /**
     * Get the LDAP server that the connection is currently connected to.
     *
     * @return string|null
     */
    public function getServer();

    /**
     * Get the time, in seconds, that the connection has been idle. If not connected this will always return 0.
     *
     * @return int
     */
    public function getIdleTime();

    /**
     * Return a RootDse LDAP object for this connection.
     *
     * @return \LdapTools\Object\LdapObject
     * @throws \LdapTools\Exception\LdapBindException When not bound yet and an anonymous bind fails.
     */
    public function getRootDse();

    /**
     * Determine whether the connection is currently bound to LDAP with a username/password.
     *
     * @return bool
     */
    public function isBound();

    /**
     * Get the message from the LDAP server for the last operation.
     *
     * @return string
     */
    public function getLastError();

    /**
     * Get the extended error number from LDAP for the last operation.
     *
     * @return int
     */
    public function getExtendedErrorNumber();

    /**
     * Get the full diagnostic message from the LDAP server for the last operation.
     *
     * @return string
     */
    public function getDiagnosticMessage();

    /**
     * Set the LDAP control for the connection.
     *
     * @param LdapControl $control
     * @throws LdapConnectionException
     */
    public function setControl(LdapControl $control);
}

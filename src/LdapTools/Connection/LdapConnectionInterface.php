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
     * Add a LDAP_OPT_* constant to be used when connecting to LDAP.
     *
     * @param $option
     * @param $value
     * @return $this
     */
    public function setOptionOnConnect($option, $value);

    /**
     * Get the LDAP_OPT_* constants to when connecting.
     *
     * @return array
     */
    public function getOptionsOnConnect();

    /**
     * Connect and bind to LDAP.
     *
     * @param string|null $username The username to connect with. If not specified, the one in the config is used.
     * @param string|null $password The password for the username.
     * @param bool $anonymous Whether this is an attempt to bind anonymously, ignoring the username and password.
     * @return $this
     */
    public function connect($username = null, $password = null, $anonymous = false);

    /**
     * Try to connect and bind to LDAP as a user account.
     *
     * @param string $username
     * @param string $password
     * @param bool|string $errorMessage Optionally, the last LDAP error message will be set here if any occurs.
     * @param bool|string $errorNumber Optionally, the last LDAP error number will be set here if any occurs.
     * @return bool
     * @throws \LdapTools\Exception\LdapBindException If re-binding fails after authentication.
     * @throws \LdapTools\Exception\LdapConnectionException If re-connecting fails after authentication.
     */
    public function authenticate($username, $password, &$errorMessage = false, &$errorNumber = false);

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
     * Get the Base DN configured for this connection.
     *
     * @return string
     */
    public function getBaseDn();

    /**
     * Get the LDAP server that the connection is currently connected to.
     *
     * @return string|null
     */
    public function getServer();

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
     * Outputs the domain name for the connection.
     *
     * @return string
     */
    public function __toString();
}

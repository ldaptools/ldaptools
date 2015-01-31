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

use LdapTools\Query\LdapQuery;

/**
 * An interface for LDAP Connection classes.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface LdapConnectionInterface
{
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
     * @param null $username
     * @param null $password
     * @return $this
     */
    public function connect($username = null, $password = null);

    /**
     * Try to connect and bind to LDAP as a user account.
     *
     * @param $username
     * @param $password
     * @return bool
     * @throws \LdapTools\Exception\LdapBindException
     * @throws \LdapTools\Exception\LdapConnectionException
     * @throws \LdapTools\Exception\LdapReconnectException
     */
    public function authenticate($username, $password);

    /**
     * Perform a query against LDAP.
     *
     * @param string $ldapFilter The LDAP filter string.
     * @param array $attributes The attributes to retrieve.
     * @param null|string $baseDn The Base DN for the search.
     * @param string $scope The scope of the query.
     * @param null|int $pageSize The page size to use.
     * @return array The entries from LDAP.
     */
    public function search($ldapFilter, array $attributes = [], $baseDn = null, $scope = LdapQuery::SCOPE_SUBTREE, $pageSize = null);

    /**
     * Adds a LDAP entry to the directory.
     *
     * @param string $dn The full distinguished name of the entry.
     * @param array $entry An array of attributes for the entry.
     * @return bool
     */
    public function add($dn, array $entry);

    /**
     * Delete a LDAP entry from the directory.
     *
     * @param string $dn The full distinguished name of the entry.
     * @return bool
     */
    public function delete($dn);

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
     * Get the page size configured for this connection.
     *
     * @return int
     */
    public function getPageSize();

    /**
     * Retrieve the RootDSE entries for the connection. Some directories may require a bind for this to work correctly.
     *
     * @return array
     * @throws \LdapTools\Exception\LdapBindException When an anonymous bind fails.
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
     * Check if a LDAP Control is supported by its OID. This will query the RootDSE "supportedcontrol" attribute.
     * Use the constants supplied in LdapControls for convenience.
     *
     * @param $oid
     * @return bool
     * @throws \LdapTools\Exception\LdapBindException
     */
    public function isControlSupported($oid);

    /**
     * Get the schema name used by this connection.
     *
     * @return string
     */
    public function getSchemaName();

    /**
     * Get the LDAP type that the connection was set as (ie. ad, openldap).
     *
     * @return string
     */
    public function getLdapType();
}

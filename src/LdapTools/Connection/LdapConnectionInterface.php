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
     * @return bool
     * @throws \LdapTools\Exception\LdapBindException If re-binding fails after authentication.
     * @throws \LdapTools\Exception\LdapConnectionException If re-connecting fails after authentication.
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
     * Modify a LDAP entry from the directory using the ldap_modify_batch specification.
     *
     * @param string $dn The full distinguished name of the entry.
     * @param array $entries The ldap_modify_batch array specification of changes to perform.
     * @return bool
     */
    public function modifyBatch($dn, array $entries);

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
     * Return a RootDse object for this connection.
     *
     * @return RootDse
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

    /**
     * Get whether or not the search is set to use the paging control.
     *
     * @return bool
     */
    public function getPagedResults();

    /**
     * Set whether or not the search should use paging control.
     *
     * @param bool $pagedResults
     */
    public function setPagedResults($pagedResults);

    /**
     * Outputs the domain name for the connection.
     *
     * @return string
     */
    public function __toString();
}

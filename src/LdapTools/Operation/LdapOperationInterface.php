<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Operation;

use LdapTools\Connection\LdapControl;

/**
 * The interface to represent a LDAP operation to be executed.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface LdapOperationInterface
{
    /**
     * Set the LDAP server that should be used for the operation.
     *
     * @param string|null $server
     * @return $this
     */
    public function setServer($server);

    /**
     * Get the server that should be used for the operation.
     *
     * @return null|string
     */
    public function getServer();

    /**
     * Add LDAP controls to the operation.
     *
     * @param LdapControl[] ...$controls
     * @return $this
     */
    public function addControl(LdapControl ...$controls);

    /**
     * Get the controls set for the operation.
     *
     * @return LdapControl[]
     */
    public function getControls();

    /**
     * Gets an array of arguments that will be passed to the LDAP function for executing this operation.
     *
     * @return array
     */
    public function getArguments();

    /**
     * Gets the name of the LDAP function needed to execute this operation.
     *
     * @return string
     */
    public function getLdapFunction();

    /**
     * Get the readable name that this operation represents. This is to be used in messages/exceptions.
     *
     * @return string
     */
    public function getName();

    /**
     * Get an array of keys/values related to the operation. This allows for ease of use within a logger.
     *
     * @return array
     */
    public function getLogArray();

    /**
     * Add an operation that should be executed after this operation.
     *
     * @param LdapOperationInterface[] ...$operation
     * @return $this
     */
    public function addPostOperation(LdapOperationInterface ...$operation);

    /**
     * Add an operation that should be executed before this operation.
     *
     * @param LdapOperationInterface[] ...$operation
     * @return $this
     */
    public function addPreOperation(LdapOperationInterface ...$operation);

    /**
     * Get operations that should be executed after this operation.
     *
     * @return LdapOperationInterface[]
     */
    public function getPostOperations();

    /**
     * Get operations that should be executed before this operation.
     *
     * @return LdapOperationInterface[]
     */
    public function getPreOperations();
}

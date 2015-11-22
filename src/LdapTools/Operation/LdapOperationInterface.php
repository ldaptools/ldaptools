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

/**
 * The interface to represent a LDAP operation to be executed.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
interface LdapOperationInterface
{
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
}

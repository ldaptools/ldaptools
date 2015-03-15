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

/**
 * Abstract away the error handling to allow for directory specific error messages and numbers.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LastErrorStrategy
{
    /**
     * This seems to be an undocumented LDAP_OPT_* constant value for retrieving diagnostic messages from LDAP.
     */
    const DIAGNOSTIC_MESSAGE_OPT = 0x0032;

    /**
     * @var resource
     */
    protected $connection;

    /**
     * @param resource $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param string $type
     * @param resource $connection
     * @return ADLastErrorStrategy|LastErrorStrategy
     */
    public static function getInstance($type, $connection)
    {
        if (LdapConnection::TYPE_AD == $type) {
            return new ADLastErrorStrategy($connection);
        } else {
            return new self($connection);
        }
    }

    /**
     * Get the last error message from LDAP.
     *
     * @return string
     */
    public function getLastErrorMessage()
    {
        return ldap_error($this->connection);
    }

    /**
     * Get the last error number from LDAP.
     *
     * @return int
     */
    public function getErrorNumber()
    {
        return ldap_errno($this->connection);
    }

    /**
     * Is there a generic way to do this that isn't directory implementation specific? The constant
     * LDAP_OPT_ERROR_NUMBER seems like the place to start, but it is not documented anywhere. So currently
     * this will only return the last generic error number unless overridden.
     *
     * @return int
     */
    public function getExtendedErrorNumber()
    {
        return $this->getErrorNumber();
    }
}

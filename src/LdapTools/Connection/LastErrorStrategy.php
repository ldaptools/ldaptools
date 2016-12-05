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

use LdapTools\Connection\AD\ADLastErrorStrategy;

/**
 * Abstract away the error handling to allow for directory specific error messages and numbers.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LastErrorStrategy
{
    /**
     * @var resource
     */
    protected $connection;

    /**
     * @var string
     */
    protected $diagnosticOpt = 'LDAP_OPT_ERROR_STRING';

    /**
     * @param resource $connection
     */
    public function __construct($connection)
    {
        $this->connection = $connection;
        if (defined('LDAP_OPT_DIAGNOSTIC_MESSAGE')) {
            $this->diagnosticOpt = 'LDAP_OPT_DIAGNOSTIC_MESSAGE';
        }
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

    /**
     * Get the full diagnostic error message.
     *
     * @return string
     */
    public function getDiagnosticMessage()
    {
        @ldap_get_option($this->connection, constant($this->diagnosticOpt), $message);
        
        return $message;
    }
}

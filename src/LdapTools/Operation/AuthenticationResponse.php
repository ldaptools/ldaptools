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
 * Encapsulates the LDAP response to an authentication operation.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class AuthenticationResponse
{
    /**
     * @var bool Whether on not the authentication attempt succeeded.
     */
    protected $isAuthenticated = false;

    /**
     * @var string The authentication error message, if any.
     */
    protected $errorMessage;

    /**
     * @var int The authentication error code, if any.
     */
    protected $errorCode;

    /**
     * @param bool $authenticated
     * @param null|string $message
     * @param null|int $code
     */
    public function __construct($authenticated, $message = null, $code = null)
    {
        $this->isAuthenticated = (bool) $authenticated;
        $this->errorMessage = $message;
        $this->errorCode = $code;
    }

    /**
     * @return null|string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @return int|null
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->isAuthenticated;
    }
}

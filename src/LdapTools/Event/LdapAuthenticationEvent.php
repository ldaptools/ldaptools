<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Event;

use LdapTools\Operation\AuthenticationOperation;
use LdapTools\Operation\AuthenticationResponse;

/**
 * Represents a LDAP authentication event.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class LdapAuthenticationEvent extends Event
{
    /**
     * @var AuthenticationOperation
     */
    protected $operation;

    /**
     * @var AuthenticationResponse
     */
    protected $response;

    /**
     * @param string $eventName
     * @param AuthenticationOperation $operation
     * @param AuthenticationResponse|null $response
     */
    public function __construct($eventName, AuthenticationOperation $operation, AuthenticationResponse $response = null)
    {
        $this->operation = $operation;
        $this->response = $response;
        parent::__construct($eventName);
    }

    /**
     * Get the authentication operation to be executed against LDAP.
     *
     * @return AuthenticationOperation
     */
    public function getOperation()
    {
        return $this->operation;
    }

    /**
     * Get the response LDAP returned from the authentication request.
     *
     * @return AuthenticationResponse|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}

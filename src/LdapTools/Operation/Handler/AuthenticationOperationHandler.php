<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace LdapTools\Operation\Handler;

use LdapTools\Event\Event;
use LdapTools\Event\LdapAuthenticationEvent;
use LdapTools\Exception\LdapBindException;
use LdapTools\Operation\AuthenticationOperation;
use LdapTools\Operation\AuthenticationResponse;
use LdapTools\Operation\LdapOperationInterface;

/**
 * Handles a LDAP authentication operation to return an object with the authentication response details.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class AuthenticationOperationHandler implements OperationHandlerInterface
{
    use OperationHandlerTrait;

    /**
     * {@inheritdoc}
     */
    public function execute(LdapOperationInterface $operation)
    {
        /** @var AuthenticationOperation $operation */
        $this->dispatcher->dispatch(new LdapAuthenticationEvent(Event::LDAP_AUTHENTICATION_BEFORE, $operation));

        $wasBound = $this->connection->isBound();
        $response = $this->getAuthenticationResponse($operation);

        if ($response->isAuthenticated() && $operation->getSwitchToCredentials()) {
            $this->switchCredentials($operation);
        } elseif ($wasBound) {
            $this->connection->close()->connect();
        } else {
            $this->connection->close();
        }
        $this->dispatcher->dispatch(new LdapAuthenticationEvent(Event::LDAP_AUTHENTICATION_AFTER, $operation, $response));

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(LdapOperationInterface $operation)
    {
        return $operation instanceof AuthenticationOperation;
    }

    /**
     * Attempts to connect with the given credentials and returns the response.
     *
     * @param AuthenticationOperation $operation
     * @return AuthenticationResponse
     */
    protected function getAuthenticationResponse(AuthenticationOperation $operation)
    {
        $authenticated = false;
        $errorMessage = null;
        $errorCode = null;

        // Only catch a bind failure. Let the others through, as it's probably a sign of other issues.
        try {
            $authenticated = (bool) $this->connection->close()->connect(...$operation->getArguments());
        } catch (LdapBindException $e) {
            $errorMessage = $this->connection->getLastError();
            $errorCode = $this->connection->getExtendedErrorNumber();
        }

        return new AuthenticationResponse($authenticated, $errorMessage, $errorCode);
    }

    /**
     * If the operation requested that the credentials be switched, then update the credential information in the
     * connections config. Otherwise it will switch again on other auth-attempts or re-connects.
     *
     * @param AuthenticationOperation $operation
     */
    protected function switchCredentials(AuthenticationOperation $operation)
    {
        $this->connection->getConfig()->setUsername($operation->getUsername());
        $this->connection->getConfig()->setPassword($operation->getPassword());
    }
}

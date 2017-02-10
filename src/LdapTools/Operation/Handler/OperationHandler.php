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

use LdapTools\Exception\LdapConnectionException;
use LdapTools\Operation\AddOperation;
use LdapTools\Operation\BatchModifyOperation;
use LdapTools\Operation\DeleteOperation;
use LdapTools\Operation\LdapOperationInterface;
use LdapTools\Operation\RenameOperation;

/**
 * Handles basic LDAP operations that do not require any additional logic.
 *
 * @author Chad Sikorra <Chad.Sikorra@gmail.com>
 */
class OperationHandler implements OperationHandlerInterface
{
    use OperationHandlerTrait;

    /**
     * {@inheritdoc}
     */
    public function execute(LdapOperationInterface $operation)
    {
        $result = @call_user_func(
            $operation->getLdapFunction(),
            $this->connection->getResource(),
            ...$operation->getArguments()
        );

        if ($result === false) {
            throw new LdapConnectionException(sprintf(
                'LDAP %s Operation Error. Diagnostic message: "%s"',
                $operation->getName(),
                $this->connection->getDiagnosticMessage()
            ));
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(LdapOperationInterface $operation)
    {
        return $operation instanceof AddOperation
            || $operation instanceof DeleteOperation
            || $operation instanceof RenameOperation
            || $operation instanceof BatchModifyOperation;
    }
}

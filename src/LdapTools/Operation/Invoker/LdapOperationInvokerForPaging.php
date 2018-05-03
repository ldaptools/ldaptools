<?php

namespace LdapTools\Operation\Invoker;

use LdapTools\Operation\Handler\AuthenticationOperationHandler;
use LdapTools\Operation\Handler\OperationHandler;
use LdapTools\Operation\Handler\PagedQueryOperationHandler;
use LdapTools\Operation\LdapOperationInterface;

/**
 * The class just change the QueryOperationHandler for the PagedQueryOperationHandler. The rest
 * of the functionality remains the same
 */
class LdapOperationInvokerForPaging extends LdapOperationInvoker
{
    public function __construct()
    {
        $this->addHandler(new OperationHandler());
        $this->addHandler(new PagedQueryOperationHandler());
        $this->addHandler(new AuthenticationOperationHandler());
    }
}

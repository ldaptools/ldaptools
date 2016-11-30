<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Operation\Handler;

use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Operation\AddOperation;
use LdapTools\Operation\AuthenticationOperation;
use LdapTools\Operation\BatchModifyOperation;
use LdapTools\Operation\DeleteOperation;
use LdapTools\Operation\QueryOperation;
use LdapTools\Operation\RenameOperation;
use PhpSpec\ObjectBehavior;

class OperationHandlerSpec extends ObjectBehavior
{
    function let(LdapConnectionInterface $connection)
    {
        $this->setConnection($connection);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Operation\Handler\OperationHandler');
    }

    function it_should_support_an_add_delete_rename_or_modify_operation()
    {
        $this->supports(new AddOperation('foo'))->shouldBeEqualTo(true);
        $this->supports(new DeleteOperation('foo'))->shouldBeEqualTo(true);
        $this->supports(new RenameOperation('foo'))->shouldBeEqualTo(true);
        $this->supports(new BatchModifyOperation('foo'))->shouldBeEqualTo(true);
    }

    function it_should_not_support_query_or_authentication_operations()
    {
        $this->supports(new AuthenticationOperation())->shouldBeEqualTo(false);
        $this->supports(new QueryOperation('(foo=bar)'))->shouldBeEqualTo(false);
    }
}

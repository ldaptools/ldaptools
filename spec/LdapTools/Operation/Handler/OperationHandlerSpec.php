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
use Prophecy\Argument;

class OperationHandlerSpec extends ObjectBehavior
{
    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function let($connection)
    {
        $this->connection = $connection;
        $this->setConnection($connection);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Operation\Handler\OperationHandler');
    }

    function it_should_support_an_add_delete_rename_or_modify_operation()
    {
        $this->supports(new AddOperation())->shouldBeEqualTo(true);
        $this->supports(new DeleteOperation())->shouldBeEqualTo(true);
        $this->supports(new RenameOperation())->shouldBeEqualTo(true);
        $this->supports(new BatchModifyOperation())->shouldBeEqualTo(true);
    }

    function it_should_not_support_query_or_authentication_operations()
    {
        $this->supports(new AuthenticationOperation())->shouldBeEqualTo(false);
        $this->supports(new QueryOperation())->shouldBeEqualTo(false);
    }
}

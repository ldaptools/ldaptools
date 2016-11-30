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
use LdapTools\Connection\PageControl;
use LdapTools\DomainConfiguration;
use LdapTools\Exception\LdapConnectionException;
use LdapTools\Object\LdapObject;
use LdapTools\Operation\AddOperation;
use LdapTools\Operation\AuthenticationOperation;
use LdapTools\Operation\BatchModifyOperation;
use LdapTools\Operation\DeleteOperation;
use LdapTools\Operation\QueryOperation;
use LdapTools\Operation\RenameOperation;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class QueryOperationHandlerSpec extends ObjectBehavior
{
    function let(PageControl $pager, LdapConnectionInterface $connection)
    {
        $this->beConstructedWith($pager);
        $this->setConnection($connection);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Operation\Handler\QueryOperationHandler');
    }

    function it_should_implement_the_operation_handler_interface()
    {
        $this->shouldImplement('\LdapTools\Operation\Handler\OperationHandlerInterface');
    }

    function it_should_support_a_query_operation()
    {
        $this->supports(new QueryOperation('(foo=bar)'))->shouldBeEqualTo(true);
    }

    function it_should_not_support_other_operations()
    {
        $this->supports(new AddOperation('foo'))->shouldBeEqualTo(false);
        $this->supports(new DeleteOperation('foo'))->shouldBeEqualTo(false);
        $this->supports(new RenameOperation('foo'))->shouldBeEqualTo(false);
        $this->supports(new AuthenticationOperation())->shouldBeEqualTo(false);
        $this->supports(new BatchModifyOperation('foo'))->shouldBeEqualTo(false);
    }

    function it_should_enable_paging_when_executing_an_operation_that_uses_paging($pager)
    {
        $operation = new QueryOperation('(sAMAccountName=foo)', ['cn']);
        $operation->setPageSize(10)->setUsePaging(true)->setBaseDn('example.local');

        $pager->setIsEnabled(true)->shouldBeCalled();
        $pager->start(10, 0)->shouldBeCalled();
        $pager->next()->shouldBeCalled();

        // Cannot simulate this without a connection. But the above control logic will be validated anyway.
        $this->shouldThrow('\LdapTools\Exception\LdapConnectionException')->duringExecute($operation);
    }

    function it_should_use_a_size_limit_with_the_pager_when_paging_is_enabled($pager)
    {
        $operation = new QueryOperation('(sAMAccountName=foo)', ['cn']);
        $operation->setPageSize(10)
            ->setSizeLimit(20)
            ->setUsePaging(true)
            ->setBaseDn('example.local');

        $pager->setIsEnabled(true)->shouldBeCalled();
        $pager->start(10, 20)->shouldBeCalled();
        $pager->next()->shouldBeCalled();

        // Cannot simulate this without a connection. But the above control logic will be validated anyway.
        $this->shouldThrow('\LdapTools\Exception\LdapConnectionException')->duringExecute($operation);
    }

    function it_should_NOT_enable_paging_when_executing_an_operation_that_disables_paging($pager)
    {
        $operation = new QueryOperation('(sAMAccountName=foo)', ['cn']);
        $operation->setUsePaging(false)->setBaseDn('example.local');

        $pager->setIsEnabled(false)->shouldBeCalled();
        $pager->start(null, 0)->shouldBeCalled();
        $pager->next()->shouldBeCalled();

        // Cannot simulate this without a connection. But the above control logic will be validated anyway.
        $this->shouldThrow('\LdapTools\Exception\LdapConnectionException')->duringExecute($operation);

        $operation = new QueryOperation('(sAMAccountName=foo)', ['cn']);
        $operation->setScope(QueryOperation::SCOPE['BASE'])->setUsePaging(true)->setBaseDn('example.local');

        // Cannot simulate this without a connection. But the above control logic will be validated anyway.
        $this->shouldThrow('\LdapTools\Exception\LdapConnectionException')->duringExecute($operation);
    }

    function it_should_set_the_defaults_from_the_rootdse_when_the_basedn_for_the_query_operation_is_not_set($connection)
    {
        $object = new LdapObject(['defaultNamingContext' => 'dc=foo,dc=bar']);
        $config = new DomainConfiguration('foo.bar');

        $connection->getConfig()->willReturn($config);
        $connection->getRootDse()->shouldBeCalled();
        $connection->getRootDse()->willReturn($object);
        $connection->getServer()->willReturn('foo');

        $this->setOperationDefaults(new QueryOperation('(foo=bar)'));
    }

    function it_should_set_the_defaults_from_the_config_when_the_basedn_for_the_query_operation_is_not_set($connection)
    {
        $config = (new DomainConfiguration('foo.bar'))->setBaseDn('dc=foo,dc=bar');

        $connection->getConfig()->willReturn($config);
        $connection->getRootDse()->shouldNotBeCalled();
        $connection->getServer()->willReturn('foo');

        $this->setOperationDefaults(new QueryOperation('(foo=bar)'));
    }

    function it_should_not_set_the_defaults_when_they_are_explicitly_set(DomainConfiguration $config, $connection)
    {
        $connection->getConfig()->willReturn($config);
        $connection->getServer()->shouldNotBeCalled();
        $config->getBaseDn()->shouldNotBeCalled();
        $config->getUsePaging()->shouldNotBeCalled();
        $config->getPageSize()->shouldNotBeCalled();

        $operation = (new QueryOperation('(foo=bar)'))->setUsePaging(true)->setPageSize(10)->setBaseDn('ou=users,dc=foo,dc=bar')->setServer('foo');
        $this->setOperationDefaults($operation);
    }

    function it_should_set_the_defaults_when_they_are_not_explicitly_set(DomainConfiguration $config, $connection)
    {
        $connection->getConfig()->willReturn($config);
        $connection->getServer()->willReturn('foo');
        $config->getBaseDn()->willReturn('dc=foo,dc=bar');
        $config->getBaseDn()->shouldBeCalled();
        $config->getUsePaging()->shouldBeCalled();
        $config->getPageSize()->shouldBeCalled();

        $this->setOperationDefaults(new QueryOperation('(foo=bar)'));
    }

    function it_should_throw_an_exception_when_the_base_dn_cannot_be_found($connection)
    {
        $object = new LdapObject([],['user'],'user','user');
        $config = new DomainConfiguration('foo.bar');

        $connection->getConfig()->willReturn($config);
        $connection->getRootDse()->shouldBeCalled();
        $connection->getRootDse()->willReturn($object);

        $ex = new LdapConnectionException('The base DN is not defined and could not be found in the RootDSE.');
        $this->shouldThrow($ex)->duringSetOperationDefaults(new QueryOperation('(foo=bar)'));
    }
}

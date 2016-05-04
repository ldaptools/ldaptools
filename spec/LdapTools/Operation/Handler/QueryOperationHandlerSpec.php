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
    /**
     * @var PageControl
     */
    protected $pager;

    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @param \LdapTools\Connection\PageControl $pager
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function let($pager, $connection)
    {
        $this->pager = $pager;
        $this->connection = $connection;
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
        $this->supports(new QueryOperation())->shouldBeEqualTo(true);
    }

    function it_should_not_support_other_operations()
    {
        $this->supports(new AddOperation('foo'))->shouldBeEqualTo(false);
        $this->supports(new DeleteOperation('foo'))->shouldBeEqualTo(false);
        $this->supports(new RenameOperation('foo'))->shouldBeEqualTo(false);
        $this->supports(new AuthenticationOperation())->shouldBeEqualTo(false);
        $this->supports(new BatchModifyOperation('foo'))->shouldBeEqualTo(false);
    }

    function it_should_enable_paging_when_executing_an_operation_that_uses_paging()
    {
        $operation = new QueryOperation();
        $operation->setPageSize(10)
            ->setUsePaging(true)
            ->setAttributes(['cn'])
            ->setBaseDn('example.local')
            ->setFilter('(sAMAccountName=foo)');

        $this->pager->setIsEnabled(true)->shouldBeCalled();
        $this->pager->start(10, 0)->shouldBeCalled();
        $this->pager->next()->shouldBeCalled();

        // Cannot simulate this without a connection. But the above control logic will be validated anyway.
        $this->shouldThrow('\LdapTools\Exception\LdapConnectionException')->duringExecute($operation);
    }

    function it_should_use_a_size_limit_with_the_pager_when_paging_is_enabled()
    {
        $operation = new QueryOperation();
        $operation->setPageSize(10)
            ->setSizeLimit(20)
            ->setUsePaging(true)
            ->setAttributes(['cn'])
            ->setBaseDn('example.local')
            ->setFilter('(sAMAccountName=foo)');

        $this->pager->setIsEnabled(true)->shouldBeCalled();
        $this->pager->start(10, 20)->shouldBeCalled();
        $this->pager->next()->shouldBeCalled();

        // Cannot simulate this without a connection. But the above control logic will be validated anyway.
        $this->shouldThrow('\LdapTools\Exception\LdapConnectionException')->duringExecute($operation);
    }

    function it_should_NOT_enable_paging_when_executing_an_operation_that_disables_paging()
    {
        $operation = new QueryOperation();
        $operation->setUsePaging(false)
            ->setAttributes(['cn'])
            ->setBaseDn('example.local')
            ->setFilter('(sAMAccountName=foo)');

        $this->pager->setIsEnabled(false)->shouldBeCalled();
        $this->pager->start(null, 0)->shouldBeCalled();
        $this->pager->next()->shouldBeCalled();

        // Cannot simulate this without a connection. But the above control logic will be validated anyway.
        $this->shouldThrow('\LdapTools\Exception\LdapConnectionException')->duringExecute($operation);

        $operation = new QueryOperation();
        $operation->setScope(QueryOperation::SCOPE['BASE'])
            ->setUsePaging(true)
            ->setAttributes(['cn'])
            ->setBaseDn('example.local')
            ->setFilter('(sAMAccountName=foo)');

        // Cannot simulate this without a connection. But the above control logic will be validated anyway.
        $this->shouldThrow('\LdapTools\Exception\LdapConnectionException')->duringExecute($operation);
    }

    function it_should_set_the_defaults_from_the_rootdse_when_the_basedn_for_the_query_operation_is_not_set()
    {
        $object = new LdapObject(['defaultNamingContext' => 'dc=foo,dc=bar']);
        $config = new DomainConfiguration('foo.bar');

        $this->connection->getConfig()->willReturn($config);
        $this->connection->getRootDse()->shouldBeCalled();
        $this->connection->getRootDse()->willReturn($object);
        $this->connection->getServer()->willReturn('foo');

        $this->setOperationDefaults(new QueryOperation());
    }

    function it_should_set_the_defaults_from_the_config_when_the_basedn_for_the_query_operation_is_not_set()
    {
        $config = (new DomainConfiguration('foo.bar'))->setBaseDn('dc=foo,dc=bar');

        $this->connection->getConfig()->willReturn($config);
        $this->connection->getRootDse()->shouldNotBeCalled();
        $this->connection->getServer()->willReturn('foo');

        $this->setOperationDefaults(new QueryOperation());
    }

    /**
     * @param \LdapTools\DomainConfiguration $config
     */
    function it_should_not_set_the_defaults_when_they_are_explicitly_set($config)
    {
        $this->connection->getConfig()->willReturn($config);
        $this->connection->getServer()->shouldNotBeCalled();
        $config->getBaseDn()->shouldNotBeCalled();
        $config->getUsePaging()->shouldNotBeCalled();
        $config->getPageSize()->shouldNotBeCalled();

        $operation = (new QueryOperation())->setUsePaging(true)->setPageSize(10)->setBaseDn('ou=users,dc=foo,dc=bar')->setServer('foo');
        $this->setOperationDefaults($operation);
    }

    /**
     * @param \LdapTools\DomainConfiguration $config
     */
    function it_should_set_the_defaults_when_they_are_not_explicitly_set($config)
    {
        $this->connection->getConfig()->willReturn($config);
        $this->connection->getServer()->willReturn('foo');
        $config->getBaseDn()->willReturn('dc=foo,dc=bar');
        $config->getBaseDn()->shouldBeCalled();
        $config->getUsePaging()->shouldBeCalled();
        $config->getPageSize()->shouldBeCalled();

        $this->setOperationDefaults(new QueryOperation());
    }

    function it_should_throw_an_exception_when_the_base_dn_cannot_be_found()
    {
        $object = new LdapObject([],['user'],'user','user');
        $config = new DomainConfiguration('foo.bar');

        $this->connection->getConfig()->willReturn($config);
        $this->connection->getRootDse()->shouldBeCalled();
        $this->connection->getRootDse()->willReturn($object);

        $ex = new LdapConnectionException('The base DN is not defined and could not be found in the RootDSE.');
        $this->shouldThrow($ex)->duringSetOperationDefaults(new QueryOperation());
    }
}

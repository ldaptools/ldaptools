<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Operation\Invoker;

use LdapTools\BatchModify\BatchCollection;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Connection\LdapControl;
use LdapTools\Connection\LdapControlType;
use LdapTools\DomainConfiguration;
use LdapTools\Event\Event;
use LdapTools\Event\EventDispatcherInterface;
use LdapTools\Log\LdapLoggerInterface;
use LdapTools\Operation\AddOperation;
use LdapTools\Operation\AuthenticationOperation;
use LdapTools\Operation\BatchModifyOperation;
use LdapTools\Operation\DeleteOperation;
use LdapTools\Operation\Handler\OperationHandler;
use LdapTools\Operation\Handler\QueryOperationHandler;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapOperationInvokerSpec extends ObjectBehavior
{
    function let(LdapConnectionInterface $connection, EventDispatcherInterface $dispatcher, LdapLoggerInterface $logger)
    {
        $connection->getConfig()->willReturn(new DomainConfiguration('example.local'));
        $connection->getConnection()->willReturn(null);
        $connection->isBound()->willReturn(true);
        $connection->getServer()->willReturn('foo');
        $connection->getIdleTime()->willReturn(1);

        $this->setConnection($connection);
        $this->setEventDispatcher($dispatcher);
        $this->setLogger($logger);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Operation\Invoker\LdapOperationInvoker');
    }

    function it_should_implement_the_operation_invoker_interface()
    {
        $this->shouldImplement('\LdapTools\Operation\Invoker\LdapOperationInvokerInterface');
    }

    function it_should_add_a_handler()
    {
        $this->addHandler(new QueryOperationHandler());
    }

    function it_should_execute_an_operation_with_the_correct_handler($dispatcher, $connection, OperationHandler $handler, QueryOperationHandler $queryHandler, DeleteOperation $operation)
    {
        $queryHandler->supports($operation)->willReturn(false);
        $queryHandler->execute($operation)->shouldNotBeCalled();

        $handler->supports($operation)->willReturn(true);
        $handler->setConnection($connection)->shouldBeCalled();
        $handler->setEventDispatcher($dispatcher)->shouldBeCalled();
        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->execute($operation)->shouldBeCalled();
        $operation->getServer()->willReturn('foo');
        $operation->getControls()->willReturn([]);
        $operation->getPreOperations()->willReturn([]);
        $operation->getPostOperations()->willReturn([]);

        // This should not be called unless a control is explicitly set
        $connection->setControl(Argument::any())->shouldNotBeCalled();

        $this->addHandler($handler);
        $this->addHandler($queryHandler);
        $this->execute($operation);
    }

    function it_should_switch_the_server_if_the_operation_requested_it(OperationHandler $handler, $connection, $dispatcher)
    {
        $operation = (new DeleteOperation('foo'))->setServer('bar');
        $handler->supports($operation)->willReturn(true);
        $handler->setConnection($connection)->shouldBeCalled();
        $handler->setEventDispatcher($dispatcher)->shouldBeCalled();
        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->execute($operation)->shouldBeCalled();
        $connection->close()->shouldBeCalled();
        $connection->connect(null, null, false, 'foo')->shouldBeCalled();
        $connection->connect(null, null, false, 'bar')->shouldBeCalled();

        // Apparently this is the magic/undocumented way to say that calling this function will return X value on
        // the Nth attempt, where Nth is the argument number passed to willReturn(). *sigh* ... ridiculousness.
        $connection->getServer()->willReturn('foo','foo', 'foo', 'bar');

        $this->addHandler($handler);
        $this->execute($operation);
    }

    function it_should_NOT_switch_the_server_if_the_operation_doesnt_request_it(OperationHandler $handler, $connection, $dispatcher)
    {
        $operation = new DeleteOperation('foo');
        $handler->supports($operation)->willReturn(true);
        $handler->setConnection($connection)->shouldBeCalled();
        $handler->setEventDispatcher($dispatcher)->shouldBeCalled();
        $handler->setOperationDefaults($operation)->will(function() use ($operation) {
            $operation->setServer('foo');
        });
        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->execute($operation)->shouldBeCalled();

        $connection->close()->shouldNotBeCalled();
        $connection->connect(null, null, false, 'foo')->shouldNotBeCalled();
        $connection->connect(null, null, false, 'bar')->shouldNotBeCalled();

        $connection->getServer()->willReturn('foo','foo');

        $this->addHandler($handler);
        $this->execute($operation);
    }

    function it_should_NOT_switch_the_server_if_the_server_is_already_active(OperationHandler $handler, $connection, $dispatcher)
    {
        $operation = (new DeleteOperation('foo'))->setServer('foo');
        $handler->supports($operation)->willReturn(true);
        $handler->setConnection($connection)->shouldBeCalled();
        $handler->setEventDispatcher($dispatcher)->shouldBeCalled();
        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->execute($operation)->shouldBeCalled();

        $connection->close()->shouldNotBeCalled();
        $connection->connect(null, null, false, 'foo')->shouldNotBeCalled();
        $connection->getServer()->willReturn('foo');

        $this->addHandler($handler);
        $this->execute($operation);
    }

    function it_should_not_connect_before_or_after_an_authentication_operation_with_a_specific_server_set($connection)
    {
        $operation = (new AuthenticationOperation())->setUsername('foo')->setPassword('foo')->setServer('foo');

        $connection->close()->willReturn($connection);
        // One to close the original connection. Another to close the temp auth connection.
        $connection->close()->shouldBeCalledTimes(2);
        $connection->connect('foo','foo', false, 'foo')->shouldBeCalledTimes(1);
        $connection->connect()->shouldBeCalledTimes(1);
        // This would be called in switch server, which should not be called...
        $connection->connect(null, null, false, Argument::any())->shouldNotBeCalled();
        $connection->getServer()->willReturn('bar');
        $this->setConnection($connection);

        $this->execute($operation);
    }

    function it_should_set_controls_specified_by_the_operation(OperationHandler $handler, $connection, $dispatcher)
    {
        $control = new LdapControl(LdapControlType::SUB_TREE_DELETE);
        $operation = (new DeleteOperation('ou=test,dc=foo,dc=bar'))->addControl($control);
        $handler->supports($operation)->willReturn(true);
        $handler->setConnection($connection)->shouldBeCalled();
        $handler->setEventDispatcher($dispatcher)->shouldBeCalled();
        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->execute($operation)->shouldBeCalled();

        $connection->close()->shouldBeCalled();
        $connection->connect(null, null, false, null)->shouldBeCalled();
        $connection->setControl($control)->shouldBeCalled();

        $reset = clone $control;
        $reset->setValue(false);

        // It should also reset the control too...
        $connection->setControl($reset)->shouldBeCalled();

        $this->addHandler($handler);
        $this->execute($operation);
    }

    function it_should_trigger_an_event_before_and_after_operation_execution($dispatcher)
    {
        $operation = new DeleteOperation('dc=foo,dc=bar');

        $dispatcher->dispatch(Argument::which('getName', Event::LDAP_OPERATION_EXECUTE_BEFORE))->shouldBeCalled();
        $dispatcher->dispatch(Argument::which('getName', Event::LDAP_OPERATION_EXECUTE_AFTER))->shouldBeCalled();

        $this->execute($operation);
    }

    function it_should_execute_all_child_operations($connection, $dispatcher, OperationHandler $handler, DeleteOperation $operation, AddOperation $preOperation, AddOperation $postOperation)
    {
        $handler->supports($operation)->willReturn(true);
        $handler->supports($preOperation)->willReturn(true);
        $handler->supports($postOperation)->willReturn(true);

        $handler->setConnection($connection)->shouldBeCalled();
        $handler->setEventDispatcher($dispatcher)->shouldBeCalled();

        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->setOperationDefaults($preOperation)->shouldBeCalled();
        $handler->setOperationDefaults($postOperation)->shouldBeCalled();

        $handler->execute($operation)->shouldBeCalled();
        $handler->execute($preOperation)->shouldBeCalled();
        $handler->execute($postOperation)->shouldBeCalled();

        $operation->getServer()->willReturn('foo');
        $operation->getControls()->willReturn([]);
        $operation->getPreOperations()->willReturn([$preOperation]);
        $operation->getPostOperations()->willReturn([$postOperation]);

        foreach ([$preOperation, $postOperation] as $op) {
            $op->getServer()->willReturn('foo');
            $op->getControls()->willReturn([]);
            $op->getPreOperations()->willReturn([]);
            $op->getPostOperations()->willReturn([]);
        }

        $this->addHandler($handler);
        $this->execute($operation);
    }

    function it_should_skip_batch_operations_that_are_empty(OperationHandler $handler)
    {
        $operation = new BatchModifyOperation('dc=foo,dc=bar', new BatchCollection());
        $handler->execute($operation)->shouldNotBeCalled(true);
        
        $this->addHandler($handler);
        $this->execute($operation);
    }

    function it_should_reconnect_a_connection_that_has_been_idle_too_long(OperationHandler $handler, $connection, $dispatcher)
    {
        $operation = (new DeleteOperation('foo'))->setServer('foo');
        $handler->supports($operation)->willReturn(true);
        $handler->setConnection($connection)->shouldBeCalled();
        $handler->setEventDispatcher($dispatcher)->shouldBeCalled();
        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->execute($operation)->shouldBeCalled();
        
        $connection->getIdleTime()->willReturn(600);
        $connection->close()->shouldBeCalled()->willReturn($connection);
        $connection->connect()->shouldBeCalled();
        $this->addHandler($handler);
        $this->execute($operation);
    }

    function it_should_not_reconnect_a_connection_that_hasnt_been_idle_too_long(OperationHandler $handler, $connection, $dispatcher)
    {
        $operation = (new DeleteOperation('foo'))->setServer('foo');
        $handler->supports($operation)->willReturn(true);
        $handler->setConnection($connection)->shouldBeCalled();
        $handler->setEventDispatcher($dispatcher)->shouldBeCalled();
        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->execute($operation)->shouldBeCalled();

        $connection->getIdleTime()->willReturn(599);
        $connection->close()->shouldNotBeCalled();
        $connection->connect()->shouldNotBeCalled();
        $this->addHandler($handler);
        $this->execute($operation);
    }

    function it_should_not_idle_reconnect_on_an_authentication_operation(OperationHandler $handler, $connection, $dispatcher)
    {
        $operation = (new AuthenticationOperation('foo', 'bar'))->setServer('foo');
        $handler->supports($operation)->willReturn(true);
        $handler->setConnection($connection)->shouldBeCalled();
        $handler->setEventDispatcher($dispatcher)->shouldBeCalled();
        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->execute($operation)->shouldBeCalled();

        $connection->getIdleTime()->willReturn(601);
        $connection->close()->shouldNotBeCalled();
        $connection->connect()->shouldNotBeCalled();
        $this->addHandler($handler);
        $this->execute($operation);
    }

    function it_should_connect_if_not_yet_bound_on_execution(OperationHandler $handler, $dispatcher, $connection)
    {
        $operation = new DeleteOperation('foo');
        $handler->supports($operation)->willReturn(true);
        $handler->setConnection($connection)->shouldBeCalled();
        $handler->setEventDispatcher($dispatcher)->shouldBeCalled();
        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->execute($operation)->shouldBeCalled();

        $connection->getServer()->willReturn(null);
        $connection->isBound()->willReturn(false);
        $connection->getIdleTime()->willReturn(1);
        
        $connection->close()->shouldNotBeCalled();
        $connection->connect()->shouldBeCalled();
        $this->addHandler($handler);
        $this->execute($operation);
    }

    function it_should_not_connect_if_not_yet_bound_on_an_AuthenticationOperation(OperationHandler $handler, $connection, $dispatcher)
    {
        $operation = new AuthenticationOperation('foo', 'bar');
        $handler->supports($operation)->willReturn(true);
        $handler->setConnection($connection)->shouldBeCalled();
        $handler->setEventDispatcher($dispatcher)->shouldBeCalled();
        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->execute($operation)->shouldBeCalled();

        $connection->getServer()->willReturn(null);
        $connection->isBound()->willReturn(false);
        $connection->getIdleTime()->willReturn(1);

        $connection->close()->shouldNotBeCalled();
        $connection->connect()->shouldNotBeCalled();
        $this->addHandler($handler);
        $this->execute($operation);
    }
}

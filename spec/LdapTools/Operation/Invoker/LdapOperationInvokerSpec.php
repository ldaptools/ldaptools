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

use LdapTools\Connection\LdapControl;
use LdapTools\Connection\LdapControlType;
use LdapTools\DomainConfiguration;
use LdapTools\Operation\AuthenticationOperation;
use LdapTools\Operation\DeleteOperation;
use LdapTools\Operation\Handler\QueryOperationHandler;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapOperationInvokerSpec extends ObjectBehavior
{
    protected $connection;

    protected $dispatcher;

    protected $logger;

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     * @param \LdapTools\Event\EventDispatcherInterface $dispatcher
     * @param \LdapTools\Log\LdapLoggerInterface $logger
     */
    function let($connection, $dispatcher, $logger)
    {
        $this->connection = $connection;
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $connection->getConfig()->willReturn(new DomainConfiguration('example.local'));
        $connection->getConnection()->willReturn(null);
        $connection->isBound()->willReturn(true);
        $connection->getServer()->willReturn('foo');

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

    /**
     * @param \LdapTools\Operation\Handler\OperationHandler $handler
     * @param \LdapTools\Operation\Handler\QueryOperationHandler $queryHandler
     * @param \LdapTools\Operation\DeleteOperation $operation
     */
    function it_should_execute_an_operation_with_the_correct_handler($handler, $queryHandler, $operation)
    {
        $queryHandler->supports($operation)->willReturn(false);
        $queryHandler->execute($operation)->shouldNotBeCalled();

        $handler->supports($operation)->willReturn(true);
        $handler->setConnection($this->connection)->shouldBeCalled();
        $handler->setEventDispatcher($this->dispatcher)->shouldBeCalled();
        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->execute($operation)->shouldBeCalled();
        $operation->getServer()->willReturn('foo');
        $operation->getControls()->willReturn([]);

        // This should not be called unless a control is explicitly set
        $this->connection->setControl(Argument::any())->shouldNotBeCalled();

        $this->addHandler($handler);
        $this->addHandler($queryHandler);
        $this->execute($operation);
    }

    /**
     * @param \LdapTools\Operation\Handler\OperationHandler $handler
     */
    function it_should_switch_the_server_if_the_operation_requested_it($handler)
    {
        $operation = (new DeleteOperation('foo'))->setServer('bar');
        $handler->supports($operation)->willReturn(true);
        $handler->setConnection($this->connection)->shouldBeCalled();
        $handler->setEventDispatcher($this->dispatcher)->shouldBeCalled();
        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->execute($operation)->shouldBeCalled();
        $this->connection->close()->shouldBeCalled();
        $this->connection->connect(null, null, false, 'foo')->shouldBeCalled();
        $this->connection->connect(null, null, false, 'bar')->shouldBeCalled();

        // Apparently this is the magic/undocumented way to say that calling this function will return X value on
        // the Nth attempt, where Nth is the argument number passed to willReturn(). *sigh* ... ridiculousness.
        $this->connection->getServer()->willReturn('foo','foo','bar');

        $this->addHandler($handler);
        $this->execute($operation);
    }

    /**
     * @param \LdapTools\Operation\Handler\OperationHandler $handler
     */
    function it_should_NOT_switch_the_server_if_the_operation_doesnt_request_it($handler)
    {
        $operation = new DeleteOperation('foo');
        $handler->supports($operation)->willReturn(true);
        $handler->setConnection($this->connection)->shouldBeCalled();
        $handler->setEventDispatcher($this->dispatcher)->shouldBeCalled();
        $handler->setOperationDefaults($operation)->will(function() use ($operation) {
            $operation->setServer('foo');
        });
        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->execute($operation)->shouldBeCalled();

        $this->connection->close()->shouldNotBeCalled();
        $this->connection->connect(null, null, false, 'foo')->shouldNotBeCalled();
        $this->connection->connect(null, null, false, 'bar')->shouldNotBeCalled();

        $this->connection->getServer()->willReturn('foo','foo');

        $this->addHandler($handler);
        $this->execute($operation);
    }

    /**
     * @param \LdapTools\Operation\Handler\OperationHandler $handler
     */
    function it_should_NOT_switch_the_server_if_the_server_is_already_active($handler)
    {
        $operation = (new DeleteOperation('foo'))->setServer('foo');
        $handler->supports($operation)->willReturn(true);
        $handler->setConnection($this->connection)->shouldBeCalled();
        $handler->setEventDispatcher($this->dispatcher)->shouldBeCalled();
        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->execute($operation)->shouldBeCalled();

        $this->connection->close()->shouldNotBeCalled();
        $this->connection->connect(null, null, false, 'foo')->shouldNotBeCalled();
        $this->connection->getServer()->willReturn('foo');

        $this->addHandler($handler);
        $this->execute($operation);
    }

    /**
     * @param \LdapTools\Operation\Handler\AuthenticationOperationHandler $handler
     */
    function it_should_not_connect_before_or_after_an_authentication_operation_with_a_specific_server_set($handler)
    {
        $operation = (new AuthenticationOperation())->setUsername('foo')->setPassword('foo')->setServer('foo');

        $this->connection->close()->willReturn($this->connection);
        // One to close the original connection. Another to close the temp auth connection.
        $this->connection->close()->shouldBeCalledTimes(2);
        $this->connection->connect('foo','foo', false, 'foo')->shouldBeCalledTimes(1);
        $this->connection->connect()->shouldBeCalledTimes(1);
        // This would be called in switch server, which should not be called...
        $this->connection->connect(null, null, false, Argument::any())->shouldNotBeCalled();
        $this->connection->getServer()->willReturn('bar');
        $this->setConnection($this->connection);

        $this->execute($operation);
    }

    /**
     * @param \LdapTools\Operation\Handler\OperationHandler $handler
     */
    function it_should_set_controls_specified_by_the_operation($handler)
    {
        $control = new LdapControl(LdapControlType::SUB_TREE_DELETE);
        $operation = (new DeleteOperation('ou=test,dc=foo,dc=bar'))->addControl($control);
        $handler->supports($operation)->willReturn(true);
        $handler->setConnection($this->connection)->shouldBeCalled();
        $handler->setEventDispatcher($this->dispatcher)->shouldBeCalled();
        $handler->setOperationDefaults($operation)->shouldBeCalled();
        $handler->execute($operation)->shouldBeCalled();

        $this->connection->close()->shouldBeCalled();
        $this->connection->connect(null, null, false, null)->shouldBeCalled();
        $this->connection->setControl($control)->shouldBeCalled();

        $reset = clone $control;
        $reset->setValue(false);

        // It should also reset the control too...
        $this->connection->setControl($reset)->shouldBeCalled();

        $this->addHandler($handler);
        $this->execute($operation);
    }
}

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

use LdapTools\DomainConfiguration;
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

        $this->addHandler($handler);
        $this->addHandler($queryHandler);
        $this->execute($operation);
    }
}

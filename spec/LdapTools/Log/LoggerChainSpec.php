<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Log;

use LdapTools\Log\EchoLdapLogger;
use LdapTools\Log\LdapLoggerInterface;
use LdapTools\Log\LogOperation;
use LdapTools\Operation\DeleteOperation;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LoggerChainSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Log\LoggerChain');
    }

    function it_should_add_a_logger()
    {
        $this->addLogger(new EchoLdapLogger());
    }

    /**
     * @param \LdapTools\Logger\EchoLdapLogger
     * @param \LdapTools\Logger\EchoLdapLogger
     */
    function it_should_call_the_loggers_on_start_and_end()
    {
        $operation = (new DeleteOperation())->setDn('foo');
        $log = new LogOperation($operation);
        $this->addLogger(new LoggerTest1());
        $this->shouldThrow(new \InvalidArgumentException("Start=foo"))->duringStart($log);
        $this->shouldThrow(new \InvalidArgumentException("End=foo"))->duringEnd($log);
    }
}

// A rather hacky way to verify what is being done. Not sure how else to configure this at the moment.
class LoggerTest1 implements LdapLoggerInterface
{
    public function start(\LdapTools\Log\LogOperation $operation)
    {
        throw new \InvalidArgumentException("Start=".$operation->getOperation()->getDn());
    }

    public function end(\LdapTools\Log\LogOperation $operation)
    {
        throw new \InvalidArgumentException("End=".$operation->getOperation()->getDn());
    }
}

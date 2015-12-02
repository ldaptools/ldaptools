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

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConnectionStateSpec extends ObjectBehavior
{
    /**
     * @param  \LdapTools\Connection\LdapConnectionInterface $connection
     */
    public function let($connection)
    {
        $connection->isBound()->willReturn(true);
        $connection->getServer()->willReturn('foo');

        $this->beConstructedWith($connection);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Operation\Invoker\ConnectionState');
    }

    function it_should_return_the_last_server()
    {
        $this->getLastServer()->shouldBeEqualTo('foo');
    }

    function it_should_get_if_the_connection_was_bound()
    {
        $this->getWasBound()->shouldBeEqualTo(true);
    }
}

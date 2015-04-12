<?php

namespace spec\LdapTools\Utilities;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TcpSocketSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith('80');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Utilities\TcpSocket');
    }
}

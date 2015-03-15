<?php

namespace spec\LdapTools\Connection;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ADLastErrorStrategySpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedThrough('getInstance', ['ad', true]);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\ADLastErrorStrategy');
    }
}

<?php

namespace spec\LdapTools\Factory;

use LdapTools\Factory\HydratorFactory;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class HydratorFactorySpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Factory\HydratorFactory');
    }

    function it_should_return_an_ArrayHydrator_when_calling_get()
    {
        $this->get(HydratorFactory::TO_ARRAY)->shouldReturnAnInstanceOf('\LdapTools\Hydrator\ArrayHydrator');
    }

    function it_should_throw_InvalidArgumentException_when_calling_get_with_an_invalid_hydrator()
    {
        $this->shouldThrow('\InvalidArgumentException')->duringGet('foo');
    }
}

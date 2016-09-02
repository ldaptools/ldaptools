<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Factory;

use LdapTools\Factory\HydratorFactory;
use PhpSpec\ObjectBehavior;

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
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->duringGet('foo');
    }
}

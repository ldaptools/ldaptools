<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Event;

use LdapTools\Object\LdapObject;
use PhpSpec\ObjectBehavior;

class LdapObjectEventSpec extends ObjectBehavior
{
    function let()
    {
        $ldapObject = new LdapObject(['foo' => 'bar'], ['user'], 'user', 'user');
        $this->beConstructedWith('foo', $ldapObject);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Event\LdapObjectEvent');
    }

    function it_should_have_type_Event()
    {
        $this->shouldHaveType('LdapTools\Event\Event');
    }

    function it_should_implement_EventInterface()
    {
        $this->shouldImplement('LdapTools\Event\EventInterface');
    }

    function it_should_get_the_event_name()
    {
        $this->getName()->shouldBeEqualTo('foo');
    }

    function it_should_get_the_ldap_object_for_the_event()
    {
        $this->getLdapObject()->shouldReturnAnInstanceOf('LdapTools\Object\LdapObject');
    }
}

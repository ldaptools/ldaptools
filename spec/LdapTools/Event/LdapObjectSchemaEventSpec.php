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

use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;

class LdapObjectSchemaEventSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('foo', new LdapObjectSchema('ad', 'user'));
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Event\LdapObjectSchemaEvent');
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

    function it_should_get_the_ldap_object_schema_for_the_event()
    {
        $this->getLdapObjectSchema()->shouldReturnAnInstanceOf('LdapTools\Schema\LdapObjectSchema');
    }
}

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

use LdapTools\Event\Event;
use LdapTools\Operation\AuthenticationOperation;
use LdapTools\Operation\AuthenticationResponse;
use PhpSpec\ObjectBehavior;

class LdapAuthenticationEventSpec extends ObjectBehavior
{
    protected $operation;

    protected $response;

    function let()
    {
        $this->operation = (new AuthenticationOperation())->setUsername('foo')->setPassword('bar');
        $this->beConstructedWith(Event::LDAP_AUTHENTICATION_BEFORE, $this->operation);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Event\LdapAuthenticationEvent');
    }

    function it_should_implement_EventInterface()
    {
        $this->shouldImplement('LdapTools\Event\EventInterface');
    }

    function it_should_set_the_name_correctly()
    {
        $this->getName()->shouldBeEqualTo(Event::LDAP_AUTHENTICATION_BEFORE);
    }

    function it_should_get_the_operation()
    {
        $this->getOperation()->shouldBeEqualTo($this->operation);
    }

    function it_should_have_an_null_response_if_it_is_not_set_yet()
    {
        $this->getResponse()->shouldBeEqualTo(null);
    }

    function it_should_get_the_ldap_response()
    {
        $response = new AuthenticationResponse(true);
        $this->beConstructedWith(Event::LDAP_AUTHENTICATION_BEFORE, $this->operation, $response);

        $this->getResponse()->shouldBeEqualTo($response);
    }
}

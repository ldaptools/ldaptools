<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Operation;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class AuthenticationOperationSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Operation\AuthenticationOperation');
    }

    function it_should_implement_LdapOperationInterface()
    {
        $this->shouldImplement('\LdapTools\Operation\LdapOperationInterface');
    }

    function it_should_set_the_username_for_the_authentication_operation()
    {
        $this->getUsername()->shouldBeEqualTo(null);
        $this->setUsername('foo');
        $this->getUsername()->shouldBeEqualTo('foo');
    }

    function it_should_set_the_password_for_the_authentication_operation()
    {
        $this->getPassword()->shouldBeEqualTo(null);
        $this->setPassword('foo');
        $this->getPassword()->shouldBeEqualTo('foo');
    }

    function it_should_set_if_it_is_an_anonymous_bind_for_the_authentication_operation()
    {
        $this->getIsAnonymousBind()->shouldBeEqualTo(false);
        $this->setIsAnonymousBind(true);
        $this->getIsAnonymousBind()->shouldBeEqualTo(true);
    }

    function it_should_set_if_the_connection_credentials_should_be_switched_during_the_authentication_operation()
    {
        $this->getSwitchToCredentials()->shouldBeEqualTo(false);
        $this->setSwitchToCredentials(true);
        $this->getSwitchToCredentials()->shouldBeEqualTo(true);
    }

    function it_should_chain_the_setters()
    {
        $this->setUsername('foo')->shouldReturnAnInstanceOf('\LdapTools\Operation\AuthenticationOperation');
        $this->setPassword('foo')->shouldReturnAnInstanceOf('\LdapTools\Operation\AuthenticationOperation');
    }

    function it_should_get_the_name_of_the_operation()
    {
        $this->getName()->shouldBeEqualTo('Authentication');
    }

    function it_should_get_the_correct_ldap_function()
    {
        $this->getLdapFunction()->shouldBeEqualTo('ldap_bind');
    }

    function it_should_return_the_arguments_for_the_ldap_function_in_the_correct_order()
    {
        $args = [
            'foo',
            'bar',
            false,
            null,
        ];
        $this->setUsername($args[0]);
        $this->setPassword($args[1]);
        $this->getArguments()->shouldBeEqualTo($args);
    }

    function it_should_get_a_log_formatted_array()
    {
        $this->getLogArray()->shouldBeArray();
        $this->getLogArray()->shouldHaveKey('Username');
        $this->getLogArray()->shouldHaveKey('Password');
        $this->getLogArray()->shouldHaveKey('Server');
        $this->getLogArray()->shouldHaveKey('Controls');
    }

    function it_should_error_when_validating_on_get_arguments()
    {
        $this->shouldThrow('\Exception')->duringGetArguments();
        $this->setUsername('foo');
        $this->shouldThrow('\Exception')->duringGetArguments();
        $this->setUsername('');
        $this->setPassword('foo');
        $this->shouldThrow('\Exception')->duringGetArguments();
        $this->setIsAnonymousBind(true);
        $this->shouldNotThrow('\Exception')->duringGetArguments();
        $this->setIsAnonymousBind(false);
        $this->setUsername('foo');
        $this->setPassword('bar');
        $this->shouldNotThrow('\Exception')->duringGetArguments();
    }
}

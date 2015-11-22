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

class DeleteOperationSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Operation\DeleteOperation');
    }

    function it_should_implement_LdapOperationInterface()
    {
        $this->shouldImplement('\LdapTools\Operation\LdapOperationInterface');
    }

    function it_should_set_the_dn_for_the_delete_operation()
    {
        $dn = 'cn=foo,dc=example,dc=local';
        $this->setDn($dn);
        $this->getDn()->shouldBeEqualTo($dn);
    }

    function it_should_chain_the_setters()
    {
        $this->setDn('foo')->shouldReturnAnInstanceOf('\LdapTools\Operation\DeleteOperation');
    }

    function it_should_get_the_name_of_the_operation()
    {
        $this->getName()->shouldBeEqualTo('Delete');
    }

    function it_should_get_the_correct_ldap_function()
    {
        $this->getLdapFunction()->shouldBeEqualTo('ldap_delete');
    }

    function it_should_return_the_arguments_for_the_ldap_function_in_the_correct_order()
    {
        $args = ['cn=foo,dc=example,dc=local'];
        $this->setDn($args[0]);
        $this->getArguments()->shouldBeEqualTo($args);
    }

    function it_should_get_a_log_formatted_array()
    {
        $this->getLogArray()->shouldBeArray();
        $this->getLogArray()->shouldHaveKey('DN');
    }
}

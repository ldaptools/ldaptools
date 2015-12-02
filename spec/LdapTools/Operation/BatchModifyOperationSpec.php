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

class BatchModifyOperationSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Operation\BatchModifyOperation');
    }

    function it_should_implement_LdapOperationInterface()
    {
        $this->shouldImplement('\LdapTools\Operation\LdapOperationInterface');
    }

    function it_should_set_the_batch_modifications_for_the_batch_operation()
    {
        $batch = [
            [
                "attrib"  => "telephoneNumber",
                "modtype" => LDAP_MODIFY_BATCH_ADD,
                "values"  => ["+1 555 555 1717"],
            ],
        ];
        $this->setBatch($batch);
        $this->getBatch()->shouldBeEqualTo($batch);
    }

    function it_should_set_the_DN_for_the_add_operation()
    {
        $dn = 'cn=foo,dc=example,dc=local';
        $this->setDn($dn);
        $this->getDn()->shouldBeEqualTo($dn);
    }

    function it_should_chain_the_setters()
    {
        $this->setDn('foo')->shouldReturnAnInstanceOf('\LdapTools\Operation\BatchModifyOperation');
        $this->setBatch(['foo' => 'bar'])->shouldReturnAnInstanceOf('\LdapTools\Operation\BatchModifyOperation');
    }

    function it_should_get_the_name_of_the_operation()
    {
        $this->getName()->shouldBeEqualTo('Batch Modify');
    }

    function it_should_get_the_correct_ldap_function()
    {
        $this->getLdapFunction()->shouldBeEqualTo('ldap_modify_batch');
    }

    function it_should_return_the_arguments_for_the_ldap_function_in_the_correct_order()
    {
        $args = [
            'cn=foo,dc=example,dc=local',
            ['foo' => 'bar'],
        ];
        $this->setDn($args[0]);
        $this->setBatch($args[1]);
        $this->getArguments()->shouldBeEqualTo($args);
    }

    function it_should_get_a_log_formatted_array()
    {
        $this->getLogArray()->shouldBeArray();
        $this->getLogArray()->shouldHaveKey('DN');
        $this->getLogArray()->shouldHaveKey('Batch');
        $this->getLogArray()->shouldHaveKey('Server');
    }
}

<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Object;

use PhpSpec\ObjectBehavior;

class LdapObjectTypeSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Object\LdapObjectType');
    }

    function it_should_have_a_user_type_constant()
    {
        $this->shouldHaveConstant('USER');
    }

    function it_should_have_a_group_type_constant()
    {
        $this->shouldHaveConstant('GROUP');
    }

    function it_should_have_a_contact_type_constant()
    {
        $this->shouldHaveConstant('CONTACT');
    }

    function it_should_have_a_computer_type_constant()
    {
        $this->shouldHaveConstant('COMPUTER');
    }

    function it_should_have_a_container_type_constant()
    {
        $this->shouldHaveConstant('CONTAINER');
    }

    function it_should_have_an_ou_type_constant()
    {
        $this->shouldHaveConstant('OU');
    }

    function it_should_have_a_deleted_type_constant()
    {
        $this->shouldHaveConstant('DELETED');
    }

    function it_should_have_an_exchange_server_type_constant()
    {
        $this->shouldHaveConstant('EXCHANGE_SERVER');
    }

    function it_should_have_an_exchange_database_type_constant()
    {
        $this->shouldHaveConstant('EXCHANGE_DATABASE');
    }

    function it_should_have_an_exchange_recipient_policy_type_constant()
    {
        $this->shouldHaveConstant('EXCHANGE_RECIPIENT_POLICY');
    }

    function it_should_have_an_exchange_activesync_policy_type_constant()
    {
        $this->shouldHaveConstant('EXCHANGE_ACTIVESYNC_POLICY');
    }

    function it_should_have_an_exchange_rbac_policy_type_constant()
    {
        $this->shouldHaveConstant('EXCHANGE_RBAC_POLICY');
    }

    function it_should_have_an_exchange_transport_rule_type_constant()
    {
        $this->shouldHaveConstant('EXCHANGE_TRANSPORT_RULE');
    }

    function it_should_have_an_exchange_dag_type_constant()
    {
        $this->shouldHaveConstant('EXCHANGE_DAG');
    }

    function it_should_have_an_exchange_owa_type_constant()
    {
        $this->shouldHaveConstant('EXCHANGE_OWA');
    }

    public function getMatchers()
    {
        return [
            'haveConstant' => function($subject, $constant) {
                return defined('\LdapTools\Object\LdapObjectType::'.$constant);
            }
        ];
    }
}

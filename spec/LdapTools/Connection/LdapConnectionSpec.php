<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Connection;

use LdapTools\Connection\LdapConnection;
use LdapTools\Connection\LdapConnectionInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use \LdapTools\DomainConfiguration;

class LdapConnectionSpec extends ObjectBehavior
{
    function let()
    {
        $config = new DomainConfiguration('example.com');
        $config->setServers(['test'])
            ->setBaseDn('dc=example,dc=local')
            ->setLazyBind(true);
        $this->beConstructedWith($config);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\LdapConnection');
    }

    function it_should_error_when_authenticate_is_called_without_username_or_password()
    {
        $this->shouldThrow('\Exception')->duringAuthenticate();
        $this->shouldThrow('\Exception')->duringAuthenticate('foo');
        $this->shouldThrow('\Exception')->duringAuthenticate('','bar');
    }

    function it_should_return_false_when_calling_isBound_and_there_is_no_connection_yet()
    {
        $this->isBound()->shouldBeEqualTo(false);
    }

    function it_should_return_self_when_calling_setLdapOption()
    {
        $this->setOptionOnConnect(LDAP_OPT_DEBUG_LEVEL, 8)->shouldReturnAnInstanceOf('\LdapTools\Connection\LdapConnection');
    }

    function it_should_set_the_ldap_option_when_calling_setLdapOption()
    {
        $this->setOptionOnConnect(LDAP_OPT_DEBUG_LEVEL, 8);
        $this->getOptionsOnConnect()->shouldHaveKeyWithValue(LDAP_OPT_DEBUG_LEVEL, 8);
    }

    function it_should_overwrite_an_ldap_option_when_calling_setLdapOption()
    {
        $this->setOptionOnConnect(LDAP_OPT_DEBUG_LEVEL, 8);
        $this->setOptionOnConnect(LDAP_OPT_DEBUG_LEVEL, 3);
        $this->getOptionsOnConnect()->shouldHaveKeyWithValue(LDAP_OPT_DEBUG_LEVEL, 3);
    }

    function it_should_have_an_ldap_type_of_ad()
    {
        $this->getLdapType()->shouldBeEqualTo(LdapConnection::TYPE_AD);
    }

    function it_should_have_a_schema_name_with_the_same_name_as_the_ldap_type_by_default()
    {
        $this->getSchemaName()->shouldBeEqualTo(LdapConnection::TYPE_AD);
    }

    function it_should_honor_an_explicitly_set_schema_name_if_present()
    {
        $config = new DomainConfiguration('example.com');
        $config->setServers(['test'])
            ->setBaseDn('dc=example,dc=local')
            ->setLazyBind(true)
            ->setSchemaName('foo');
        $this->beConstructedWith($config);

        $this->getSchemaName()->shouldBeEqualTo('foo');
    }

    function it_should_have_a_page_size_as_specified_from_the_config()
    {
        $config = new DomainConfiguration('example.com');
        $config->setServers(['test'])
            ->setBaseDn('dc=example,dc=local')
            ->setLazyBind(true)
            ->setPageSize(250);
        $this->beConstructedWith($config);

        $this->getPageSize()->shouldBeEqualTo(250);
    }

    function it_should_have_the_base_dn_from_the_config()
    {
        $this->getBaseDn()->shouldBeEqualTo('dc=example,dc=local');
    }

    public function getMatchers()
    {
        return [
            'haveKeyWithValue' => function($subject, $key, $value) {
                return isset($subject[$key]) && ($subject[$key] === $value);
            },
        ];
    }
}

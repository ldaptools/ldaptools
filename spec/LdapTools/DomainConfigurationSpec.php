<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools;

use LdapTools\Connection\LdapConnection;
use LdapTools\Connection\LdapServerPool;
use PhpSpec\ObjectBehavior;

class DomainConfigurationSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('example.com');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\DomainConfiguration');
    }

    function it_should_have_a_default_schema_name_of_the_ldap_type()
    {
        $this->getSchemaName()->shouldBeEqualTo('ad');
    }

    function it_should_set_the_username_when_calling_setUsername()
    {
        $this->setUsername('bar');
        $this->getUsername()->shouldBeEqualTo('bar');
    }

    function it_should_set_the_password_when_calling_setPassword()
    {
        $this->setPassword('foo');
        $this->getPassword()->shouldBeEqualTo('foo');
    }

    function it_should_have_the_correct_string_as_domain_name()
    {
        $this->getDomainName()->shouldBeEqualTo('example.com');
        $this->setDomainName('foo.bar');
        $this->getDomainName()->shouldBeEqualTo('foo.bar');
    }

    function it_should_properly_set_the_base_dn_when_calling_setBaseDn()
    {
        $this->setBaseDn('dc=foo,dc=bar');
        $this->getBaseDn()->shouldBeEqualTo('dc=foo,dc=bar');
    }

    function it_should_set_use_ssl_when_calling_setUseSsl()
    {
        $this->getUseSsl()->shouldBeEqualTo(false);
        $this->setUseSsl(true);
        $this->getUseSsl()->shouldBeEqualTo(true);
    }

    function it_should_set_use_tls_when_calling_setUseTls()
    {
        $this->getUseTls()->shouldBeEqualTo(false);
        $this->setUseTls(true);
        $this->getUseTls()->shouldBeEqualTo(true);
    }

    function it_should_have_the_correct_port_after_calling_setPort()
    {
        $this->getPort()->shouldBeEqualTo(389);
        $this->setPort(9001);
        $this->getPort()->shouldBeEqualTo(9001);
    }

    function it_should_allow_a_numeric_string_when_setting_port()
    {
        $this->shouldNotThrow('\LdapTools\Exception\InvalidArgumentException')->duringSetPort('123');
    }

    function it_should_throw_InvalidTypeException_when_setting_port_as_non_int()
    {
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->duringSetPort('test');
    }

    function it_should_return_the_correct_page_size_after_calling_setPageSize()
    {
        $this->getPageSize()->shouldBeEqualTo(1000);
        $this->setPageSize(9001);
        $this->getPageSize()->shouldBeEqualTo(9001);
        $this->setPageSize(0);
        $this->getPageSize()->shouldBeEqualTo(0);
    }

    function it_should_allow_a_numeric_string_when_setting_page_size()
    {
        $this->setPort('1000')->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
    }

    function it_should_throw_InvalidTypeException_when_setting_page_size_as_non_int()
    {
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->duringSetPageSize('test');
    }

    function it_should_return_a_string_when_calling_getSchemaName()
    {
        $this->getSchemaName()->shouldBeString();
    }

    function it_should_return_the_correct_schema_name_when_it_is_set()
    {
        $this->setSchemaName('test');
        $this->getSchemaName()->shouldBeEqualTo('test');
    }

    function it_should_return_the_correct_servers_after_calling_setServers()
    {
        $servers = ['foo', 'bar'];
        $this->getServers()->shouldBeArray();
        $this->setServers($servers);
        $this->getServers()->shouldBeEqualTo($servers);
    }

    function it_should_have_the_correct_value_for_lazy_bind_after_calling_setLazyBind()
    {
        $this->getLazyBind()->shouldBeEqualTo(false);
        $this->setLazyBind(true);
        $this->getLazyBind()->shouldBeEqualTo(true);
    }

    function it_should_have_the_correct_ldap_type_after_calling_setLdapType()
    {
        $this->getLdapType()->shouldBeEqualTo(LdapConnection::TYPE_AD);
        $this->setLdapType(LdapConnection::TYPE_OPENLDAP);
        $this->getLdapType()->shouldBeEqualTo(LdapConnection::TYPE_OPENLDAP);
    }

    function it_should_have_active_directory_set_as_the_default_ldap_type()
    {
        $this->getLdapType()->shouldBeEqualTo(LdapConnection::TYPE_AD);
    }

    function it_should_error_when_setting_an_unknown_ldap_type()
    {
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->duringSetLdapType('SuperHappyFunTime');
    }

    function it_should_return_the_correct_server_selection_type_after_calling_setServerSelection()
    {
        $this->setServerSelection(LdapServerPool::SELECT_RANDOM);
        $this->getServerSelection()->shouldBeEqualTo(LdapServerPool::SELECT_RANDOM);
    }

    function it_should_have_the_server_selection_type_as_order_by_default()
    {
        $this->getServerSelection()->shouldBeEqualTo(LdapServerPool::SELECT_ORDER);
    }

    function it_should_have_the_correct_encoding_after_calling_setEncoding()
    {
        $this->getEncoding()->shouldBeEqualTo('UTF-8');
        $this->setEncoding('Foo');
        $this->getEncoding()->shouldBeEqualTo('Foo');
    }

    function it_should_set_whether_paging_control_should_be_used()
    {
        $this->getUsePaging()->shouldBeEqualTo(true);
        $this->setUsePaging(false);
        $this->getUsePaging()->shouldBeEqualTo(false);
    }

    function it_should_set_the_ldap_option_when_calling_setLdapOption()
    {
        $this->setLdapOption(LDAP_OPT_DEBUG_LEVEL, 8);
        $this->getLdapOptions()->shouldHaveKeyWithValue(LDAP_OPT_DEBUG_LEVEL, 8);
    }

    function it_should_overwrite_an_ldap_option_when_calling_setLdapOption()
    {
        $this->setLdapOption(LDAP_OPT_DEBUG_LEVEL, 8);
        $this->setLdapOption(LDAP_OPT_DEBUG_LEVEL, 3);
        $this->getLdapOptions()->shouldHaveKeyWithValue(LDAP_OPT_DEBUG_LEVEL, 3);
    }

    function it_should_allow_a_string_representation_of_a_connection_option()
    {
        $this->setLdapOption('ldap_opt_debug_level', 3);
        $this->getLdapOptions()->shouldHaveKeyWithValue(LDAP_OPT_DEBUG_LEVEL, 3);
        $this->setLdapOptions(['ldap_opt_debug_level' => 8]);
        $this->getLdapOptions()->shouldHaveKeyWithValue(LDAP_OPT_DEBUG_LEVEL, 8);
    }

    function it_should_use_ldap_v3_by_default_and_not_follow_referrals()
    {
        $this->getLdapOptions()->shouldHaveKeyWithValue(LDAP_OPT_PROTOCOL_VERSION, 3);
        $this->getLdapOptions()->shouldHaveKeyWithValue(LDAP_OPT_REFERRALS, 0);
    }
    
    function it_should_set_the_idle_reconnection_time()
    {
        $this->getIdleReconnect()->shouldBeEqualTo(600);
        $this->setIdleReconnect(0);
        $this->getIdleReconnect()->shouldBeEqualTo(0);
    }

    function it_should_set_the_connect_timeout_time()
    {
        $this->getConnectTimeout()->shouldBeEqualTo(1);
        $this->setConnectTimeout(5);
        $this->getConnectTimeout()->shouldBeEqualTo(5);
    }

    function it_should_return_self_when_calling_the_setters()
    {
        $this->setUsePaging(true)->shouldReturnAnInstanceOf('\LdapTools\DomainConfiguration');
        $this->setPassword('test')->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->setDomainName('example.com')->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->setBaseDn('dc=example,dc=com')->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->setUseSsl(true)->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->setUseTls(true)->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->setPort(123)->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->setPageSize(500)->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->setServers(['test'])->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->setSchemaName('custom')->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->setLazyBind(true)->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->setLdapType(LdapConnection::TYPE_AD)->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->setBindFormat('%username%')->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->setUsername('test')->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->setEncoding('UTF-8')->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->setServerSelection(LdapServerPool::SELECT_RANDOM)->shouldReturnAnInstanceOf('\LdapTools\DomainConfiguration');
        $this->setLdapOption(LDAP_OPT_DEBUG_LEVEL, 8)->shouldReturnAnInstanceOf('\LdapTools\DomainConfiguration');
        $this->setLdapOptions([LDAP_OPT_DEBUG_LEVEL => 8])->shouldReturnAnInstanceOf('\LdapTools\DomainConfiguration');
        $this->setIdleReconnect(0)->shouldReturnAnInstanceOf('\LdapTools\DomainConfiguration');
    }

    function it_should_have_the_correct_encoding_after_calling_setBindFormat()
    {
        $this->getBindFormat()->shouldBeEqualTo('');
        $this->setBindFormat('%username%');
        $this->getBindFormat()->shouldBeEqualTo('%username%');
    }

    function it_should_load_an_array_for_the_configuration()
    {
        $config = [
            'domain_name' => 'example.local',
            'base_dn' => 'dc=example,dc=local',
            'username' => 'admin',
            'password' => '12345',
            'servers' => ['test'],
            'ldap_options' => ['ldap_opt_protocol_version' => 2],
            'use_paging' => false
        ];
        $this->load($config)->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->getDomainName()->shouldBeEqualTo('example.local');
        $this->getUsername()->shouldBeEqualTo('admin');
        $this->getPassword()->shouldBeEqualTo('12345');
        $this->getServers()->shouldReturn(['test']);
        $this->getLdapOptions()->shouldHaveKeyWithValue(LDAP_OPT_PROTOCOL_VERSION, 2);
        $this->getUsePaging()->shouldBeEqualTo(false);
    }

    function it_should_error_when_missing_required_config_values()
    {
        $config = [
            'domain_name' => 'example.local',
            'username' => 'admin',
            'lazy_bind' => true,
        ];
        $this->shouldThrow('\LdapTools\Exception\ConfigurationException')->duringLoad($config);
    }

    function it_should_error_on_unknown_configuration_options()
    {
        $config = [
            'domain_name' => 'example.local',
            'username' => 'admin',
            'password' => '12345',
            'awesome_level' => 9001,
        ];
        $this->shouldThrow('\LdapTools\Exception\ConfigurationException')->duringLoad($config);
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

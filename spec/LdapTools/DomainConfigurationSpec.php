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
use LdapTools\DomainConfiguration;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

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

    function it_should_set_the_username_when_calling_setUsername()
    {
        $this->setUsername('bar');
        $this->getUsername()->shouldBeEqualTo('bar');
    }

    function it_should_return_self_when_setting_username()
    {
        $this->setUsername('test')->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
    }

    function it_should_set_the_password_when_calling_setPassword()
    {
        $this->setPassword('foo');
        $this->getPassword()->shouldBeEqualTo('foo');
    }

    function it_should_return_self_when_setting_password()
    {
        $this->setPassword('test')->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
    }

    function it_should_have_the_correct_string_as_domain_name()
    {
        $this->getDomainName()->shouldBeEqualTo('example.com');
        $this->setDomainName('foo.bar');
        $this->getDomainName()->shouldBeEqualTo('foo.bar');
    }

    function it_should_return_self_when_setting_domain_name()
    {
        $this->setDomainName('example.com')->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
    }

    function it_should_properly_set_the_base_dn_when_calling_setBaseDn()
    {
        $this->setBaseDn('dc=foo,dc=bar');
        $this->getBaseDn()->shouldBeEqualTo('dc=foo,dc=bar');
    }

    function it_should_return_self_when_setting_baseDn()
    {
        $this->setBaseDn('dc=example,dc=com')->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
    }

    function it_should_set_use_ssl_when_calling_setUseSsl()
    {
        $this->getUseSsl()->shouldBeEqualTo(false);
        $this->setUseSsl(true);
        $this->getUseSsl()->shouldBeEqualTo(true);
    }

    function it_should_return_self_when_setting_use_ssl()
    {
        $this->setUseSsl(true)->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
    }

    function it_should_set_use_tls_when_calling_setUseTls()
    {
        $this->getUseTls()->shouldBeEqualTo(false);
        $this->setUseTls(true);
        $this->getUseTls()->shouldBeEqualTo(true);
    }

    function it_should_return_self_when_setting_use_tls()
    {
        $this->setUseTls(true)->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
    }

    function it_should_have_the_correct_port_after_calling_setPort()
    {
        $this->getPort()->shouldBeEqualTo(389);
        $this->setPort(9001);
        $this->getPort()->shouldBeEqualTo(9001);
    }

    function it_should_allow_a_numeric_string_when_setting_port()
    {
        $this->setPort('123')->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
    }

    function it_should_throw_InvalidTypeException_when_setting_port_as_non_int()
    {
        $this->shouldThrow('\InvalidArgumentException')->duringSetPort('test');
    }

    function it_should_return_self_when_setting_port()
    {
        $this->setPort(123)->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
    }

    function it_should_return_the_correct_page_size_after_calling_setPageSize()
    {
        $this->getPageSize()->shouldBeEqualTo(1000);
        $this->setPageSize(9001);
        $this->getPageSize()->shouldBeEqualTo(9001);
    }

    function it_should_allow_a_numeric_string_when_setting_page_size()
    {
        $this->setPort('1000')->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
    }

    function it_should_throw_InvalidTypeException_when_setting_page_size_as_non_int()
    {
        $this->shouldThrow('\InvalidArgumentException')->duringSetPageSize('test');
    }

    function it_should_return_self_when_setting_page_size()
    {
        $this->setPageSize(500)->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
    }

    function it_should_return_self_when_setting_servers()
    {
        $this->setServers(['test'])->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
    }

    function it_should_return_self_when_setting_schema_name()
    {
        $this->setSchemaName('custom')->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
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

    function it_should_return_self_when_setting_lazy_bind()
    {
        $this->setLazyBind(true)->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
    }

    function it_should_have_the_correct_value_for_lazy_bind_after_calling_setLazyBind()
    {
        $this->getLazyBind()->shouldBeEqualTo(false);
        $this->setLazyBind(true);
        $this->getLazyBind()->shouldBeEqualTo(true);
    }

    function it_should_return_self_when_setting_ldap_type()
    {
        $this->setLdapType(LdapConnection::TYPE_AD)->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
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
        $this->shouldThrow('\InvalidArgumentException')->duringSetLdapType('SuperHappyFunTime');
    }

    function it_should_load_an_array_for_the_configuration()
    {
        $config = [
            'domain_name' => 'example.local',
            'username' => 'admin',
            'password' => '12345',
            'servers' => ['test'],
        ];
        $this->load($config)->shouldReturnAnInstanceOf('LdapTools\DomainConfiguration');
        $this->getDomainName()->shouldBeEqualTo('example.local');
        $this->getUsername()->shouldBeEqualTo('admin');
        $this->getPassword()->shouldBeEqualTo('12345');
        $this->getServers()->shouldReturn(['test']);
    }

    function it_should_error_when_missing_required_config_values()
    {
        $config = [
            'domain_name' => 'example.local',
            'username' => 'admin',
            'password' => '12345',
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
}

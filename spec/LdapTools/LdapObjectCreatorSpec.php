<?php

namespace spec\LdapTools;

use LdapTools\AttributeConverter\EncodeWindowsPassword;
use LdapTools\Configuration;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\DomainConfiguration;
use LdapTools\Factory\CacheFactory;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Factory\SchemaParserFactory;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapObjectCreatorSpec extends ObjectBehavior
{
    public function let(LdapConnectionInterface $connection)
    {
        $connection->getSchemaName()->willReturn('ad');
        $connection->__toString()->willReturn('example.com');

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), $config->getSchemaFolder());
        $cache = CacheFactory::get('none', []);
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\LdapObjectCreator');
    }

    function it_should_chain_calls_when_adding_attributes()
    {
        $this->with(['foo' => 'bar'])->shouldReturnAnInstanceOf('\LdapTools\LdapObjectCreator');
    }

    function it_should_chain_calls_when_creating_a_user()
    {
        $this->createUser()->shouldReturnAnInstanceOf('\LdapTools\LdapObjectCreator');
    }

    function it_should_chain_calls_when_creating_a_group()
    {
        $this->createGroup()->shouldReturnAnInstanceOf('\LdapTools\LdapObjectCreator');
    }

    function it_should_chain_calls_when_creating_a_contact()
    {
        $this->createContact()->shouldReturnAnInstanceOf('\LdapTools\LdapObjectCreator');
    }

    function it_should_chain_calls_when_creating_a_computer()
    {
        $this->createComputer()->shouldReturnAnInstanceOf('\LdapTools\LdapObjectCreator');
    }

    function it_should_chain_calls_when_setting_the_container()
    {
        $this->in('dc=foo,dc=bar')->shouldReturnAnInstanceOf('\LdapTools\LdapObjectCreator');
    }

    function it_should_chain_calls_when_setting_a_parameter()
    {
        $this->setParameter('foo', 'bar')->shouldReturnAnInstanceOf('\LdapTools\LdapObjectCreator');
    }

    function it_should_chain_calls_when_setting_the_dn()
    {
        $this->setDn('cn=foo,dc=example,dc=local')->shouldReturnAnInstanceOf('\LdapTools\LdapObjectCreator');
    }

    function it_should_throw_an_exception_when_passing_an_invalid_object_to_create()
    {
        $this->shouldThrow('\InvalidArgumentException')->duringCreate(new DomainConfiguration('foo.bar'));
    }

    function it_should_throw_an_exception_when_passing_an_unknown_ldap_object_type_to_create()
    {
        $this->shouldThrow('\Exception')->duringCreate('foo');
    }

    function it_should_set_parameters_for_the_attributes_sent_to_ldap(LdapConnectionInterface $connection)
    {
        $arg = Argument::allOf(
            Argument::withEntry('cn', 'somedude'),
            Argument::withEntry('sAMAccountName', 'somedude'),
            Argument::withEntry('unicodePwd', (new EncodeWindowsPassword())->toLdap('12345'))
        );
        $connection->getSchemaName()->willReturn('ad');
        $connection->__toString()->willReturn('example.com');
        $connection->add("cn=somedude,dc=foo,dc=bar", $arg)->willReturn(null);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), $config->getSchemaFolder());
        $cache = CacheFactory::get('none', []);
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);

        $this->createUser()
            ->with(['username' => '%foo%', 'password' => '%bar%'])
            ->in('dc=foo,dc=bar')
            ->setParameter('foo', 'somedude')
            ->setParameter('bar', '12345');

        $this->execute();
    }

    function it_should_respect_an_explicitly_set_dn(LdapConnectionInterface $connection)
    {
        $connection->getSchemaName()->willReturn('ad');
        $connection->__toString()->willReturn('example.com');
        $connection->add("cn=chad,ou=users,dc=foo,dc=bar", Argument::any())->willReturn(null);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), $config->getSchemaFolder());
        $cache = CacheFactory::get('none', []);
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);

        $this->createUser()
            ->with(['username' => '%foo%', 'password' => '%bar%'])
            ->setDn('cn=chad,ou=users,dc=foo,dc=bar')
            ->execute();
    }
}

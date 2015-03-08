<?php

namespace spec\LdapTools\Object;

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
        $connection->getSchemaName()->willReturn('example');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Object\LdapObjectCreator');
    }

    function it_should_chain_calls_when_adding_attributes()
    {
        $this->with(['foo' => 'bar'])->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCreator');
    }

    function it_should_chain_calls_when_creating_a_user()
    {
        $this->createUser()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCreator');
    }

    function it_should_chain_calls_when_creating_a_group()
    {
        $this->createGroup()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCreator');
    }

    function it_should_chain_calls_when_creating_a_contact()
    {
        $this->createContact()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCreator');
    }

    function it_should_chain_calls_when_creating_a_computer()
    {
        $this->createComputer()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCreator');
    }

    function it_should_chain_calls_when_setting_the_container()
    {
        $this->in('dc=foo,dc=bar')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCreator');
    }

    function it_should_chain_calls_when_setting_a_parameter()
    {
        $this->setParameter('foo', 'bar')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCreator');
    }

    function it_should_chain_calls_when_setting_the_dn()
    {
        $this->setDn('cn=foo,dc=example,dc=local')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCreator');
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
        $connection->getEncoding()->willReturn('UTF-8');
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
        $connection->getEncoding()->willReturn('UTF-8');
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

    function it_should_escape_the_base_dn_name_properly_when_using_a_schema(LdapConnectionInterface $connection)
    {
        $connection->getSchemaName()->willReturn('ad');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');
        $connection->add('cn=foo\\3d\\2cbar,dc=foo,dc=bar', Argument::withEntry('sAMAccountName', 'foobar'))->willReturn(null);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), $config->getSchemaFolder());
        $cache = CacheFactory::get('none', []);
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);

        $this->createUser()
            ->with(['name' => 'foo=,bar', 'username' => 'foobar', 'password' => '12345'])
            ->in('dc=foo,dc=bar')
            ->execute();
    }

    function it_should_thrown_an_exception_when_no_container_is_specified()
    {
        $this->createGroup()
            ->with(['name' => 'foo']);
        $this->shouldThrow(new \LogicException('You must specify a container or OU to place this LDAP object in.'))->duringExecute();
    }

    function it_should_use_a_default_container_defined_in_the_schema(LdapConnectionInterface $connection)
    {
        $connection->getSchemaName()->willReturn('example');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');
        $connection->add('cn=foobar,ou=foo,ou=bar,dc=example,dc=local', Argument::any())->willReturn(null);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);

        $this->createUser()
            ->with(['username' => 'foobar', 'password' => '12345'])
            ->execute();
    }

    function it_should_allow_a_default_container_to_be_overwritten(LdapConnectionInterface $connection)
    {
        $connection->getSchemaName()->willReturn('example');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');
        $connection->add('cn=foobar,ou=employees,dc=example,dc=local', Argument::any())->willReturn(null);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);

        $this->createUser()
            ->with(['username' => 'foobar', 'password' => '12345'])
            ->in('ou=employees,dc=example,dc=local')
            ->execute();
    }
}

<?php

namespace spec\LdapTools\Object;

use LdapTools\AttributeConverter\EncodeWindowsPassword;
use LdapTools\Configuration;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\DomainConfiguration;
use LdapTools\Event\Event;
use LdapTools\Event\EventDispatcherInterface;
use LdapTools\Event\LdapObjectCreationEvent;
use LdapTools\Factory\CacheFactory;
use LdapTools\Event\SymfonyEventDispatcher;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Factory\SchemaParserFactory;
use LdapTools\Object\LdapObject;
use LdapTools\Operation\AddOperation;
use PhpSpec\ObjectBehavior;

class LdapObjectCreatorSpec extends ObjectBehavior
{
    /**
     * @var AddOperation
     */
    protected $addOperation;

    protected $attributes = [
        'cn' => 'somedude',
        'displayname' => 'somedude',
        'givenName' => 'somedude',
        'userPrincipalName' => 'somedude@example.com',
        'objectclass' => ['top', 'person', 'organizationalPerson', 'user'],
        'userAccountControl' => '512',
        'sAMAccountName' => 'somedude',
        'unicodePwd' => null,
    ];

    /**
     * @var DomainConfiguration
     */
    protected $config;

    /**
     * @var LdapObjectSchemaFactory
     */
    protected $schemaFactory;

    /**
     * @var LdapObjectSchemaFactory
     */
    protected $schemaFactoryTest;

    protected $dispatcher;

    public function let(LdapConnectionInterface $connection)
    {
        $this->config = (new DomainConfiguration('example.com'))->setSchemaName('example');
        $this->config->setUseTls(true);
        $ldapObject = new LdapObject(['defaultNamingContext' => 'dc=example,dc=com'],['*'], '','ad');
        $connection->getConfig()->willReturn($this->config);
        $connection->getRootDse()->willReturn($ldapObject);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), $config->getSchemaFolder());
        $parserTest = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $this->dispatcher = new SymfonyEventDispatcher();
        $this->schemaFactoryTest = new LdapObjectSchemaFactory($cache, $parserTest, $this->dispatcher);
        $this->schemaFactory = new LdapObjectSchemaFactory($cache, $parser, $this->dispatcher);
        $this->attributes['unicodePwd'] = (new EncodeWindowsPassword())->toLdap('12345');
        $this->addOperation = (new AddOperation('foo'))->setDn("cn=somedude,dc=foo,dc=bar")->setAttributes($this->attributes);

        $this->beConstructedWith($connection, $this->schemaFactoryTest, $this->dispatcher);
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

    function it_should_chain_calls_when_creating_an_ou()
    {
        $this->createOU()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCreator');
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
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->duringCreate(new DomainConfiguration('foo.bar'));
    }

    function it_should_throw_an_exception_when_passing_an_unknown_ldap_object_type_to_create()
    {
        $this->shouldThrow('\Exception')->duringCreate('foo');
    }

    function it_should_set_parameters_for_the_attributes_sent_to_ldap($connection)
    {
        $this->addOperation->setLocation('dc=foo,dc=bar');
        $connection->execute($this->addOperation)->willReturn(true);

        $this->createUser()
            ->with(['username' => '%foo%', 'password' => '%bar%'])
            ->in('dc=foo,dc=bar')
            ->setParameter('foo', 'somedude')
            ->setParameter('bar', '12345');

        $this->execute();
    }

    function it_should_respect_an_explicitly_set_dn($connection)
    {
        $connection->execute($this->addOperation)->willReturn(true);
        $this->addOperation->setDn('cn=chad,ou=users,dc=foo,dc=bar');

        $this->config->setSchemaName('ad');
        $this->beConstructedWith($connection, $this->schemaFactory, $this->dispatcher);

        $this->createUser()
            ->with(['username' => 'somedude', 'password' => '12345'])
            ->setDn('cn=chad,ou=users,dc=foo,dc=bar')
            ->execute();
    }

    function it_should_escape_the_base_dn_name_properly_when_using_a_schema($connection)
    {
        $attributes = $this->attributes;
        $attributes['cn'] = 'foo=,bar';
        $operation = new AddOperation('cn=foo\\3d\\2cbar,dc=foo,dc=bar', $attributes);
        $operation->setLocation('dc=foo,dc=bar');
        $connection->execute($operation)->willReturn(true);

        $this->config->setSchemaName('ad');
        $this->beConstructedWith($connection, $this->schemaFactory, $this->dispatcher);

        $this->createUser()
            ->with(['name' => 'foo=,bar', 'username' => 'somedude', 'password' => '12345'])
            ->in('dc=foo,dc=bar')
            ->execute();
    }

    function it_should_throw_an_exception_when_no_container_is_specified()
    {
        $this->createGroup()->with(['name' => 'foo']);
        $this->shouldThrow(new \LogicException('You must specify a container or OU to place this LDAP object in.'))->duringExecute();
    }

    function it_should_use_a_default_container_defined_in_the_schema($connection)
    {
        $operation = clone $this->addOperation;
        $operation->setDn('cn=somedude,ou=foo,ou=bar,dc=example,dc=local');
        $operation->setLocation('ou=foo,ou=bar,dc=example,dc=local');
        $connection->execute($operation)->willReturn(true);

        $this->createUser()
            ->with(['username' => 'somedude', 'password' => '12345'])
            ->execute();
    }

    function it_should_allow_a_default_container_to_be_overwritten($connection)
    {
        $operation = clone $this->addOperation;
        $operation->setDn('cn=somedude,ou=employees,dc=example,dc=local');
        $operation->setLocation('ou=employees,dc=example,dc=local');
        $connection->execute($operation)->willReturn(true);

        $this->createUser()
            ->with(['username' => 'somedude', 'password' => '12345'])
            ->in('ou=employees,dc=example,dc=local')
            ->execute();
    }

    function it_should_set_parameters_for_the_container_of_the_ldap_object($connection)
    {
        $operation = clone $this->addOperation;
        $operation->setDn("cn=somedude,ou=Sales,dc=example,dc=com");
        $operation->setLocation('%SalesOU%,%_defaultnamingcontext_%');
        $connection->execute($operation)->willReturn(true);

        $this->config->setSchemaName('ad');
        $this->beConstructedWith($connection, $this->schemaFactory, $this->dispatcher);

        $this->createUser()
            ->with(['username' => '%foo%', 'password' => '%bar%'])
            ->in('%SalesOU%,%_defaultnamingcontext_%')
            ->setParameter('foo', 'somedude')
            ->setParameter('SalesOU', 'ou=Sales')
            ->setParameter('bar', '12345');
        $this->execute();
    }

    function it_should_call_creation_events_when_creating_a_ldap_object(EventDispatcherInterface $dispatcher, $connection)
    {
        $this->addOperation->setLocation('dc=foo,dc=bar');
        $connection->execute($this->addOperation)->willReturn(true);

        $beforeEvent = new LdapObjectCreationEvent(Event::LDAP_OBJECT_BEFORE_CREATE, 'user');
        $beforeEvent->setContainer('dc=foo,dc=bar');
        $beforeEvent->setData(['username' => '%foo%', 'password' => '%bar%']);
        $beforeEvent->setDn('');
        $afterEvent = new LdapObjectCreationEvent(Event::LDAP_OBJECT_AFTER_CREATE, 'user');
        $afterEvent->setContainer('dc=foo,dc=bar');
        $afterEvent->setData(['username' => 'somedude', 'password' => '12345']);
        $afterEvent->setDn('cn=somedude,dc=foo,dc=bar');
        $dispatcher->dispatch($beforeEvent)->shouldBeCalled();
        $dispatcher->dispatch($afterEvent)->shouldBeCalled();

        $this->config->setSchemaName('ad');
        $this->beConstructedWith($connection, $this->schemaFactory, $dispatcher);

        $this->createUser()
            ->with(['username' => '%foo%', 'password' => '%bar%'])
            ->in('dc=foo,dc=bar')
            ->setParameter('foo', 'somedude')
            ->setParameter('bar', '12345');

        $this->execute();
    }

    function it_should_allow_a_ldap_server_to_be_set($connection)
    {
        $operation = clone $this->addOperation;
        $operation->setLocation('ou=employees,dc=example,dc=local');
        $operation->setDn('cn=somedude,ou=employees,dc=example,dc=local');
        $operation->setServer('foo');
        $connection->execute($operation)->willReturn(true);

        $this->createUser()
            ->with(['username' => 'somedude', 'password' => '12345'])
            ->in('ou=employees,dc=example,dc=local')
            ->setServer('foo')
            ->execute();
    }

    function it_should_get_the_ldap_server_set()
    {
        $this->getServer()->shouldBeEqualTo(null);
        $this->setServer('foo')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCreator');
        $this->getServer()->shouldBeEqualTo('foo');
    }

    function it_should_filter_out_empty_strings_and_null_values_before_the_operation_is_executed($connection)
    {
        $operation = clone $this->addOperation;
        $attributes = $this->attributes;
        unset($attributes['givenName']);
        $attributes['cn'] = ' ';
        $operation->setDn('cn=\20,cn=users,dc=example,dc=local');
        $operation->setLocation('cn=users,dc=example,dc=local');
        $operation->setAttributes($attributes);
        
        $connection->execute($operation)->shouldBeCalled();
        $this->createUser()
            ->with([
                'name' => ' ',
                'firstName' => null,
                'lastName' => '',
                'otherFaxes' => [],
                'username' => 'somedude',
                'password' => '12345'
            ])
            ->in('cn=users,dc=example,dc=local')
            ->execute();
    }
}

<?php

namespace spec\LdapTools\Object;

use LdapTools\AttributeConverter\EncodeWindowsPassword;
use LdapTools\Configuration;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\DomainConfiguration;
use LdapTools\Event\Event;
use LdapTools\Event\LdapObjectCreationEvent;
use LdapTools\Factory\CacheFactory;
use LdapTools\Event\SymfonyEventDispatcher;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Factory\SchemaParserFactory;
use LdapTools\Object\LdapObject;
use LdapTools\Operation\AddOperation;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

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
        'sAMAccountName' => 'somedude',
        'unicodePwd' => null,
        'userAccountControl' => '512',
    ];

    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

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

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    public function let($connection)
    {
        $this->config = (new DomainConfiguration('example.com'))->setSchemaName('example');
        $this->config->setUseTls(true);
        $connection->__toString()->willReturn('example.com');
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
        $this->addOperation = (new AddOperation())->setDn("cn=somedude,dc=foo,dc=bar")->setAttributes($this->attributes);
        $this->connection = $connection;

        $this->beConstructedWith($this->connection, $this->schemaFactoryTest, $this->dispatcher);
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
        $this->shouldThrow('\InvalidArgumentException')->duringCreate(new DomainConfiguration('foo.bar'));
    }

    function it_should_throw_an_exception_when_passing_an_unknown_ldap_object_type_to_create()
    {
        $this->shouldThrow('\Exception')->duringCreate('foo');
    }

    function it_should_set_parameters_for_the_attributes_sent_to_ldap()
    {
        $this->connection->execute($this->addOperation)->willReturn(true);

        $this->createUser()
            ->with(['username' => '%foo%', 'password' => '%bar%'])
            ->in('dc=foo,dc=bar')
            ->setParameter('foo', 'somedude')
            ->setParameter('bar', '12345');

        $this->execute();
    }

    function it_should_respect_an_explicitly_set_dn()
    {
        $this->connection->execute($this->addOperation)->willReturn(true);
        $this->addOperation->setDn('cn=chad,ou=users,dc=foo,dc=bar');

        $this->config->setSchemaName('ad');
        $this->beConstructedWith($this->connection, $this->schemaFactory, $this->dispatcher);

        $this->createUser()
            ->with(['username' => 'somedude', 'password' => '12345'])
            ->setDn('cn=chad,ou=users,dc=foo,dc=bar')
            ->execute();
    }

    function it_should_escape_the_base_dn_name_properly_when_using_a_schema()
    {
        $attributes = $this->attributes;
        $attributes['cn'] = 'foo=,bar';
        $operation = (new AddOperation())->setDn('cn=foo\\3d\\2cbar,dc=foo,dc=bar')->setAttributes($attributes);
        $this->connection->execute($operation)->willReturn(true);

        $this->config->setSchemaName('ad');
        $this->beConstructedWith($this->connection, $this->schemaFactory, $this->dispatcher);

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

    function it_should_use_a_default_container_defined_in_the_schema()
    {
        $operation = clone $this->addOperation;
        $operation->setDn('cn=somedude,ou=foo,ou=bar,dc=example,dc=local');
        $this->connection->execute($operation)->willReturn(true);

        $this->createUser()
            ->with(['username' => 'somedude', 'password' => '12345'])
            ->execute();
    }

    function it_should_allow_a_default_container_to_be_overwritten()
    {
        $operation = clone $this->addOperation;
        $operation->setDn('cn=somedude,ou=employees,dc=example,dc=local');
        $this->connection->execute($operation)->willReturn(true);

        $this->createUser()
            ->with(['username' => 'somedude', 'password' => '12345'])
            ->in('ou=employees,dc=example,dc=local')
            ->execute();
    }

    function it_should_set_parameters_for_the_container_of_the_ldap_object()
    {
        $operation = clone $this->addOperation;
        $operation->setDn("cn=somedude,ou=Sales,dc=example,dc=com");
        $this->connection->execute($operation)->willReturn(true);

        $this->config->setSchemaName('ad');
        $this->beConstructedWith($this->connection, $this->schemaFactory, $this->dispatcher);

        $this->createUser()
            ->with(['username' => '%foo%', 'password' => '%bar%'])
            ->in('%SalesOU%,%_defaultnamingcontext_%')
            ->setParameter('foo', 'somedude')
            ->setParameter('SalesOU', 'ou=Sales')
            ->setParameter('bar', '12345');
        $this->execute();
    }

    /**
     * @param \LdapTools\Event\EventDispatcherInterface $dispatcher
     */
    function it_should_call_creation_events_when_creating_a_ldap_object($dispatcher)
    {
        $this->connection->execute($this->addOperation)->willReturn(true);

        $beforeEvent = new LdapObjectCreationEvent(Event::LDAP_OBJECT_BEFORE_CREATE);
        $beforeEvent->setContainer('dc=foo,dc=bar');
        $beforeEvent->setData(['username' => '%foo%', 'password' => '%bar%']);
        $beforeEvent->setDn('');
        $afterEvent = new LdapObjectCreationEvent(Event::LDAP_OBJECT_AFTER_CREATE);
        $afterEvent->setContainer('dc=foo,dc=bar');
        $afterEvent->setData(['username' => 'somedude', 'password' => '12345']);
        $afterEvent->setDn('cn=somedude,dc=foo,dc=bar');
        $dispatcher->dispatch($beforeEvent)->shouldBeCalled();
        $dispatcher->dispatch($afterEvent)->shouldBeCalled();

        $this->config->setSchemaName('ad');
        $this->beConstructedWith($this->connection, $this->schemaFactory, $dispatcher);

        $this->createUser()
            ->with(['username' => '%foo%', 'password' => '%bar%'])
            ->in('dc=foo,dc=bar')
            ->setParameter('foo', 'somedude')
            ->setParameter('bar', '12345');

        $this->execute();
    }
}

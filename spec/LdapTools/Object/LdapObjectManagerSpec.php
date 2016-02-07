<?php

namespace spec\LdapTools\Object;

use LdapTools\BatchModify\Batch;
use LdapTools\BatchModify\BatchCollection;
use LdapTools\Configuration;
use LdapTools\Connection\LdapControl;
use LdapTools\Connection\LdapControlType;
use LdapTools\DomainConfiguration;
use LdapTools\Event\Event;
use LdapTools\Event\LdapObjectEvent;
use LdapTools\Event\LdapObjectMoveEvent;
use LdapTools\Factory\CacheFactory;
use LdapTools\Event\SymfonyEventDispatcher;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Factory\SchemaParserFactory;
use LdapTools\Object\LdapObject;
use LdapTools\Operation\BatchModifyOperation;
use LdapTools\Operation\DeleteOperation;
use LdapTools\Operation\RenameOperation;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapObjectManagerSpec extends ObjectBehavior
{
    protected $ldapEntries = [
        'count' => 1,
        0 => [
            'cn' => [
                'count' => 1,
                0 => "foo",
            ],
            0 => "cn",
            'count' => 1,
            'dn' => "CN=foo,DC=foo,DC=bar",
        ]
    ];

    protected $connection;

    protected $objectSchemaFactoryTest;

    protected $objectSchemaFactory;

    protected $dispatcher;

    protected $dispatcherTest;

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function let($connection)
    {
        $config = (new DomainConfiguration('example.com'))->setSchemaName('example');
        $connection->getConfig()->willReturn($config);
        $this->connection = $connection;

        $config = new Configuration();
        $parserTest = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../resources/schema');
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $this->dispatcherTest = new SymfonyEventDispatcher();
        $this->dispatcher = new SymfonyEventDispatcher();
        $this->objectSchemaFactoryTest = new LdapObjectSchemaFactory($cache, $parserTest, $this->dispatcherTest);
        $this->objectSchemaFactory = new LdapObjectSchemaFactory($cache, $parser, $this->dispatcher);

        $this->beConstructedWith($connection, $this->objectSchemaFactory, $this->dispatcher);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Object\LdapObjectManager');
    }

    function it_should_error_on_update_or_delete_if_the_dn_is_not_set()
    {
        $ldapObject = new LdapObject(['foo' => 'bar'], [], 'user', 'user');
        $ldapObject->set('foo', 'foobar');

        $this->shouldThrow('\InvalidArgumentException')->duringPersist($ldapObject);
        $this->shouldThrow('\InvalidArgumentException')->duringDelete($ldapObject);
    }

    function it_should_delete_a_ldap_object_from_its_dn()
    {
        $this->connection->execute(new DeleteOperation('cn=foo,dc=foo,dc=bar'))->willReturn(true);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $this->delete($ldapObject);
    }

    function it_should_delete_a_ldap_object_recursively_if_specified()
    {
        $control = (new LdapControl(LdapControlType::SUB_TREE_DELETE))->setCriticality(true);
        $this->connection->execute((new DeleteOperation('cn=foo,dc=foo,dc=bar'))->addControl($control))
            ->shouldBeCalled()
            ->willReturn(true);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $this->delete($ldapObject, true);
    }

    function it_should_update_a_ldap_object_using_batch_modify()
    {
        $dn = 'cn=foo,dc=foo,dc=bar';
        $batch = new BatchCollection($dn);
        $batch->add(new Batch(Batch::TYPE['REPLACE'], 'givenName', 'Chad'));
        $batch->add(new Batch(Batch::TYPE['ADD'], 'sn', 'Sikorra'));
        $batch->add(new Batch(Batch::TYPE['REMOVE'], 'sAMAccountName', 'csikorra'));
        $batch->add(new Batch(Batch::TYPE['REMOVE_ALL'], 'mail'));

        $this->connection->execute(new BatchModifyOperation($dn, $batch))->willReturn(null);

        $ldapObject = new LdapObject(['dn' => $dn], [], 'user', 'user');
        $ldapObject->set('firstName', 'Chad');
        $ldapObject->add('lastName', 'Sikorra');
        $ldapObject->remove('username', 'csikorra');
        $ldapObject->reset('emailAddress');
        $this->persist($ldapObject);
    }

    function it_should_move_a_ldap_object_using_move()
    {
        $operation = new RenameOperation(
            'cn=foo,dc=foo,dc=bar',
            'cn=foo',
            'ou=employees,dc=foo,dc=bar'
        );
        $this->connection->execute($operation)->willReturn(true);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar', 'name' => 'foo'], [], 'user', 'user');
        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    function it_should_escape_the_RDN_when_moving_a_ldap_object()
    {
        $operation = new RenameOperation(
            'cn=foo\, bar,dc=foo,dc=bar',
            'cn=foo\2c bar',
            'ou=employees,dc=foo,dc=bar'
        );
        $this->connection->execute($operation)->willReturn(true);

        $ldapObject = new LdapObject(['dn' => 'cn=foo\, bar,dc=foo,dc=bar', 'name' => 'foo, bar'], [], 'user', 'user');
        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    function it_should_move_an_object_without_a_schema_type()
    {
        $operation = new RenameOperation(
            'cn=foo,dc=foo,dc=bar',
            'cn=foo',
            'ou=employees,dc=foo,dc=bar'
        );
        $this->connection->execute($operation)->willReturn(true);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', '');
        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    function it_should_not_query_ldap_for_the_RDN_when_moving_an_object_and_the_name_attribute_was_not_selected()
    {
        $this->connection->execute(Argument::type('\LdapTools\Operation\QueryOperation'))->shouldNotBeCalled();
        $this->connection->execute(Argument::type('\LdapTools\Operation\RenameOperation'))->shouldBeCalled();

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    /**
     * @param \LdapTools\Event\EventDispatcherInterface $dispatcher
     */
    function it_should_call_the_event_dispatcher_delete_events_when_deleting_an_object($dispatcher)
    {
        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $beforeEvent = new LdapObjectEvent(Event::LDAP_OBJECT_BEFORE_DELETE, $ldapObject);
        $afterEvent = new LdapObjectEvent(Event::LDAP_OBJECT_AFTER_DELETE, $ldapObject);

        $this->connection->execute(new DeleteOperation('cn=foo,dc=foo,dc=bar'))->willReturn(true);
        $dispatcher->dispatch($beforeEvent)->shouldBeCalled();
        $dispatcher->dispatch($afterEvent)->shouldBeCalled();

        $this->beConstructedWith($this->connection, $this->objectSchemaFactory, $dispatcher);
        $this->delete($ldapObject);
    }

    /**
     * @param \LdapTools\Event\EventDispatcherInterface $dispatcher
     */
    function it_should_call_the_event_dispatcher_move_events_when_moving_an_object($dispatcher)
    {
        $operation = new RenameOperation(
            'cn=foo,dc=foo,dc=bar',
            'cn=foo',
            'ou=employees,dc=foo,dc=bar'
        );

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar', 'name' => 'foo'], [], 'user', 'user');
        $beforeEvent = new LdapObjectMoveEvent(Event::LDAP_OBJECT_BEFORE_MOVE, $ldapObject, 'ou=employees,dc=foo,dc=bar');
        $afterEvent = new LdapObjectMoveEvent(Event::LDAP_OBJECT_AFTER_MOVE, $ldapObject, 'ou=employees,dc=foo,dc=bar');

        $this->connection->execute($operation)->willReturn(true);
        $dispatcher->dispatch($beforeEvent)->shouldBeCalled();
        $dispatcher->dispatch($afterEvent)->shouldBeCalled();
        $this->beConstructedWith($this->connection, $this->objectSchemaFactory, $dispatcher);

        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    /**
     * @param \LdapTools\Event\EventDispatcherInterface $dispatcher
     */
    function it_should_call_the_event_dispatcher_modify_events_when_persisting_an_object($dispatcher)
    {
        $dn = 'cn=foo,dc=foo,dc=bar';
        $batch = new BatchCollection($dn);
        $batch->add(new Batch(Batch::TYPE['REPLACE'], 'givenName', 'Chad'));
        $batch->add(new Batch(Batch::TYPE['ADD'], 'sn', 'Sikorra'));
        $batch->add(new Batch(Batch::TYPE['REMOVE'], 'sAMAccountName', 'csikorra'));
        $batch->add(new Batch(Batch::TYPE['REMOVE_ALL'], 'mail'));

        $this->connection->execute(new BatchModifyOperation($dn, $batch))->willReturn(null);
        $this->beConstructedWith($this->connection, $this->objectSchemaFactory, $dispatcher);

        $ldapObject = new LdapObject(['dn' => $dn], [], 'user', 'user');
        $ldapObject->set('firstName', 'Chad');
        $ldapObject->add('lastName', 'Sikorra');
        $ldapObject->remove('username', 'csikorra');
        $ldapObject->reset('emailAddress');

        $beforeEvent = new LdapObjectEvent(Event::LDAP_OBJECT_BEFORE_MODIFY, $ldapObject);
        $afterEvent = new LdapObjectEvent(Event::LDAP_OBJECT_AFTER_MODIFY, $ldapObject);
        $dispatcher->dispatch($beforeEvent)->shouldBeCalled();
        $dispatcher->dispatch($afterEvent)->shouldBeCalled();

        $this->persist($ldapObject);
    }

    function it_should_not_try_to_modify_an_ldap_object_that_has_not_changed()
    {
        $ldapObject = new LdapObject(['dn' => 'cn=user,dc=foo,dc=bar'], [], 'user', 'user');
        $this->connection->execute(Argument::any())->shouldNotBeCalled();

        $this->persist($ldapObject);
    }

    /**
     * @param \LdapTools\Event\EventDispatcherInterface $dispatcher
     */
    function it_should_not_call_the_event_dispatcher_modify_events_when_an_object_has_not_changed($dispatcher)
    {
        $this->beConstructedWith($this->connection, $this->objectSchemaFactory, $dispatcher);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $beforeEvent = new LdapObjectEvent(Event::LDAP_OBJECT_BEFORE_MODIFY, $ldapObject);
        $afterEvent = new LdapObjectEvent(Event::LDAP_OBJECT_AFTER_MODIFY, $ldapObject);
        $dispatcher->dispatch($beforeEvent)->shouldNotBeCalled();
        $dispatcher->dispatch($afterEvent)->shouldNotBeCalled();

        $this->persist($ldapObject);
    }

    function it_should_persist_an_ldap_object_that_has_no_schema_type()
    {
        $dn = 'cn=user,dc=foo,dc=bar';
        $ldapObject = new LdapObject(['dn' => $dn], ['user'], 'user', '');
        $ldapObject->set('foo', 'bar');
        $batch = new BatchCollection($dn);
        $batch->add(new Batch(Batch::TYPE['REPLACE'],'foo','bar'));

        $this->connection->execute(new BatchModifyOperation($dn, $batch))->shouldBeCalled();
        $this->persist($ldapObject);
    }
}

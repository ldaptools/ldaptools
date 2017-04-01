<?php

namespace spec\LdapTools\Object;

use LdapTools\BatchModify\Batch;
use LdapTools\BatchModify\BatchCollection;
use LdapTools\Configuration;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Connection\LdapControl;
use LdapTools\Connection\LdapControlType;
use LdapTools\DomainConfiguration;
use LdapTools\Event\Event;
use LdapTools\Event\EventDispatcherInterface;
use LdapTools\Event\LdapObjectEvent;
use LdapTools\Event\LdapObjectMoveEvent;
use LdapTools\Event\LdapObjectRestoreEvent;
use LdapTools\Exception\InvalidArgumentException;
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

    protected $objectSchemaFactoryTest;

    protected $objectSchemaFactory;

    protected $dispatcher;

    protected $dispatcherTest;

    function let(LdapConnectionInterface $connection)
    {
        $config = (new DomainConfiguration('example.com'))->setSchemaName('example');
        $connection->getConfig()->willReturn($config);

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

    function it_should_error_on_update_delete_move_or_restore_if_the_dn_is_not_set()
    {
        $ldapObject = new LdapObject(['foo' => 'bar'], 'user');
        $ldapObject->set('foo', 'foobar');

        $this->shouldThrow('\InvalidArgumentException')->duringPersist($ldapObject);
        $this->shouldThrow('\InvalidArgumentException')->duringDelete($ldapObject);
        $this->shouldThrow('\InvalidArgumentException')->duringRestore($ldapObject);
        $this->shouldThrow('\InvalidArgumentException')->duringMove($ldapObject, 'dc=foo,dc=bar');
    }

    function it_should_delete_a_ldap_object_from_its_dn($connection)
    {
        $connection->execute(new DeleteOperation('cn=foo,dc=foo,dc=bar'))->willReturn(true);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], 'user');
        $this->delete($ldapObject);
    }

    function it_should_delete_a_ldap_object_recursively_if_specified($connection)
    {
        $control = (new LdapControl(LdapControlType::SUB_TREE_DELETE))->setCriticality(true);
        $connection->execute((new DeleteOperation('cn=foo,dc=foo,dc=bar'))->addControl($control))
            ->shouldBeCalled()
            ->willReturn(true);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $this->delete($ldapObject, true);
    }

    function it_should_update_a_ldap_object_using_batch_modify($connection)
    {
        $dn = 'cn=foo,dc=foo,dc=bar';
        $batch = new BatchCollection($dn);
        $batch->add(new Batch(Batch::TYPE['REPLACE'], 'givenName', 'Chad'));
        $batch->add(new Batch(Batch::TYPE['ADD'], 'sn', 'Sikorra'));
        $batch->add(new Batch(Batch::TYPE['REMOVE'], 'sAMAccountName', 'csikorra'));
        $batch->add(new Batch(Batch::TYPE['REMOVE_ALL'], 'mail'));

        $connection->execute(new BatchModifyOperation($dn, $batch))->willReturn(null);

        $ldapObject = new LdapObject(['dn' => $dn], 'user');
        $ldapObject->set('firstName', 'Chad');
        $ldapObject->add('lastName', 'Sikorra');
        $ldapObject->remove('username', 'csikorra');
        $ldapObject->reset('emailAddress');
        $this->persist($ldapObject);
    }

    function it_should_move_a_ldap_object_using_move($connection)
    {
        $operation = new RenameOperation(
            'cn=foo,dc=foo,dc=bar',
            'cn=foo',
            'ou=employees,dc=foo,dc=bar'
        );
        $connection->execute($operation)->willReturn(true);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], 'user');
        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    function it_should_escape_the_RDN_when_moving_a_ldap_object($connection)
    {
        $operation = new RenameOperation(
            'cn=foo\, bar,dc=foo,dc=bar',
            'cn=foo\2c bar',
            'ou=employees,dc=foo,dc=bar'
        );
        $connection->execute($operation)->willReturn(true);

        $ldapObject = new LdapObject(['dn' => 'cn=foo\, bar,dc=foo,dc=bar', 'name' => 'foo, bar'], 'user');
        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    function it_should_move_an_object_without_a_schema_type($connection)
    {
        $operation = new RenameOperation(
            'cn=foo,dc=foo,dc=bar',
            'cn=foo',
            'ou=employees,dc=foo,dc=bar'
        );
        $connection->execute($operation)->willReturn(true);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar']);
        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    function it_should_restore_a_ldap_object_using_restore($connection)
    {
        $dn = 'cn=foo\0ADEL:0101011,cn=Deleted Objects,dc=example,dc=local';

        $ldapObject1 = new LdapObject(['dn' => $dn, 'lastKnownLocation' => 'cn=Users,dc=example,dc=local'], 'deleted');
        $ldapObject2 = new LdapObject(['dn' => $dn, 'lastKnownLocation' => 'cn=Users,dc=example,dc=local'], 'deleted');

        $connection->execute(Argument::that(function($operation) use ($dn) {
            /** @var BatchModifyOperation $operation */
            $batches = $operation->getBatchCollection()->toArray();

            return $batches[0]->isTypeRemoveAll() && $batches[0]->getAttribute() == 'isDeleted'
                && $batches[1]->isTypeReplace() && $batches[1]->getAttribute() == 'distinguishedName'
                && $batches[1]->getValues() == ['cn=foo,cn=Users,dc=example,dc=local']
                && $operation->getDn() == $dn;
        }))->shouldBeCalled();
        $this->restore($ldapObject1);

        $connection->execute(Argument::that(function($operation) use ($dn) {
            /** @var BatchModifyOperation $operation */
            $batches = $operation->getBatchCollection()->toArray();

            return $batches[0]->isTypeRemoveAll() && $batches[0]->getAttribute() == 'isDeleted'
                && $batches[1]->isTypeReplace() && $batches[1]->getAttribute() == 'distinguishedName'
                && $batches[1]->getValues() == ['cn=foo,ou=Employees,dc=example,dc=local']
                && $operation->getDn() == $dn;
        }))->shouldBeCalled();
        $this->restore($ldapObject2, 'ou=Employees,dc=example,dc=local');
    }

    function it_should_error_on_restore_if_the_last_known_location_cannot_be_found_and_none_was_specified($connection)
    {
        $dn = 'cn=foo\0ADEL:0101011,cn=Deleted Objects,dc=example,dc=local';
        $ldapObject = new LdapObject(['dn' => $dn], 'deleted');

        $connection->execute(Argument::type('\LdapTools\Operation\QueryOperation'))->shouldBeCalled()->willReturn(['count' => 0]);
        $connection->getRootDse()->willReturn(new LdapObject(['defaultNamingContext' => 'dc=foo,dc=bar']));

        $this->shouldThrow(new InvalidArgumentException('No restore location specified and cannot find the last known location for "'.$dn.'".'))->duringRestore($ldapObject);
    }
    
    function it_should_not_query_ldap_for_the_RDN_when_moving_an_object_and_the_name_attribute_was_not_selected($connection)
    {
        $connection->execute(Argument::type('\LdapTools\Operation\QueryOperation'))->shouldNotBeCalled();
        $connection->execute(Argument::type('\LdapTools\Operation\RenameOperation'))->shouldBeCalled();

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], 'user');
        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    function it_should_call_the_event_dispatcher_delete_events_when_deleting_an_object(EventDispatcherInterface $dispatcher, $connection)
    {
        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], 'user');
        $beforeEvent = new LdapObjectEvent(Event::LDAP_OBJECT_BEFORE_DELETE, $ldapObject);
        $afterEvent = new LdapObjectEvent(Event::LDAP_OBJECT_AFTER_DELETE, $ldapObject);

        $connection->execute(new DeleteOperation('cn=foo,dc=foo,dc=bar'))->willReturn(true);
        $dispatcher->dispatch($beforeEvent)->shouldBeCalled();
        $dispatcher->dispatch($afterEvent)->shouldBeCalled();

        $this->beConstructedWith($connection, $this->objectSchemaFactory, $dispatcher);
        $this->delete($ldapObject);
    }

    function it_should_call_the_event_dispatcher_move_events_when_moving_an_object(EventDispatcherInterface $dispatcher, $connection)
    {
        $operation = new RenameOperation(
            'cn=foo,dc=foo,dc=bar',
            'cn=foo',
            'ou=employees,dc=foo,dc=bar'
        );

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar', 'name' => 'foo'], 'user');
        $beforeEvent = new LdapObjectMoveEvent(Event::LDAP_OBJECT_BEFORE_MOVE, $ldapObject, 'ou=employees,dc=foo,dc=bar');
        $afterEvent = new LdapObjectMoveEvent(Event::LDAP_OBJECT_AFTER_MOVE, $ldapObject, 'ou=employees,dc=foo,dc=bar');

        $connection->execute($operation)->willReturn(true);
        $dispatcher->dispatch($beforeEvent)->shouldBeCalled();
        $dispatcher->dispatch($afterEvent)->shouldBeCalled();
        $this->beConstructedWith($connection, $this->objectSchemaFactory, $dispatcher);

        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    function it_should_call_the_event_dispatcher_modify_events_when_persisting_an_object(EventDispatcherInterface $dispatcher, $connection)
    {
        $dn = 'cn=foo,dc=foo,dc=bar';
        $batch = new BatchCollection($dn);
        $batch->add(new Batch(Batch::TYPE['REPLACE'], 'givenName', 'Chad'));
        $batch->add(new Batch(Batch::TYPE['ADD'], 'sn', 'Sikorra'));
        $batch->add(new Batch(Batch::TYPE['REMOVE'], 'sAMAccountName', 'csikorra'));
        $batch->add(new Batch(Batch::TYPE['REMOVE_ALL'], 'mail'));

        $connection->execute(new BatchModifyOperation($dn, $batch))->willReturn(null);
        $this->beConstructedWith($connection, $this->objectSchemaFactory, $dispatcher);

        $ldapObject = new LdapObject(['dn' => $dn], 'user');
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

    function it_should_call_the_event_dispatcher_restore_events_when_restoring_an_object(EventDispatcherInterface $dispatcher, $connection)
    {
        $dn = 'cn=foo\0ADEL:0101011,cn=Deleted Objects,dc=example,dc=local';
        $ldapObject = new LdapObject(['dn' => $dn, 'lastKnownLocation' => 'cn=Users,dc=example,dc=local'], 'deleted');
        
        $beforeEvent = new LdapObjectRestoreEvent(Event::LDAP_OBJECT_BEFORE_RESTORE, $ldapObject, 'ou=employees,dc=foo,dc=bar');
        $afterEvent = new LdapObjectRestoreEvent(Event::LDAP_OBJECT_AFTER_RESTORE, $ldapObject, 'ou=employees,dc=foo,dc=bar');

        $connection->execute(Argument::type('\LdapTools\Operation\BatchModifyOperation'))->willReturn(true);
        $dispatcher->dispatch($beforeEvent)->shouldBeCalled();
        $dispatcher->dispatch($afterEvent)->shouldBeCalled();
        $this->beConstructedWith($connection, $this->objectSchemaFactory, $dispatcher);

        $this->restore($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    function it_should_not_try_to_modify_an_ldap_object_that_has_not_changed($connection)
    {
        $ldapObject = new LdapObject(['dn' => 'cn=user,dc=foo,dc=bar'], 'user');
        $connection->execute(Argument::any())->shouldNotBeCalled();

        $this->persist($ldapObject);
    }

    function it_should_not_call_the_event_dispatcher_modify_events_when_an_object_has_not_changed(EventDispatcherInterface $dispatcher, $connection)
    {
        $this->beConstructedWith($connection, $this->objectSchemaFactory, $dispatcher);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], 'user');
        $beforeEvent = new LdapObjectEvent(Event::LDAP_OBJECT_BEFORE_MODIFY, $ldapObject);
        $afterEvent = new LdapObjectEvent(Event::LDAP_OBJECT_AFTER_MODIFY, $ldapObject);
        $dispatcher->dispatch($beforeEvent)->shouldNotBeCalled();
        $dispatcher->dispatch($afterEvent)->shouldNotBeCalled();

        $this->persist($ldapObject);
    }

    function it_should_persist_an_ldap_object_that_has_no_schema_type($connection)
    {
        $dn = 'cn=user,dc=foo,dc=bar';
        $ldapObject = new LdapObject(['dn' => $dn]);
        $ldapObject->set('foo', 'bar');
        $batch = new BatchCollection($dn);
        $batch->add(new Batch(Batch::TYPE['REPLACE'],'foo','bar'));

        $connection->execute(new BatchModifyOperation($dn, $batch))->shouldBeCalled();
        $this->persist($ldapObject);
    }

    function it_should_perform_rename_operations_when_an_RDN_change_is_persisted($connection)
    {
        $dn = 'cn=user,dc=foo,dc=bar';
        $ldapObject = new LdapObject(['dn' => 'cn=user,dc=foo,dc=bar'], 'user');
        $ldapObject->set('name', 'The, Dude');

        $batch = new BatchCollection($dn);
        $operation = new BatchModifyOperation($dn, $batch);
        $operation->addPostOperation(new RenameOperation($dn, 'cn=The\2c Dude'));

        $connection->execute($operation)->shouldBeCalled();
        $this->persist($ldapObject);
    }
}

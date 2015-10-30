<?php

namespace spec\LdapTools\Object;

use LdapTools\Configuration;
use LdapTools\Event\Event;
use LdapTools\Event\LdapObjectEvent;
use LdapTools\Event\LdapObjectMoveEvent;
use LdapTools\Factory\CacheFactory;
use LdapTools\Event\SymfonyEventDispatcher;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Factory\SchemaParserFactory;
use LdapTools\Object\LdapObject;
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

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function let($connection)
    {
        $connection->getSchemaName()->willReturn('example');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');
        $this->connection = $connection;

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../resources/schema');
        $cache = CacheFactory::get('none', []);
        $dispatcher = new SymfonyEventDispatcher();
        $factory = new LdapObjectSchemaFactory($cache, $parser, $dispatcher);

        $this->beConstructedWith($connection, $factory, $dispatcher);
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

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_delete_a_ldap_object_from_its_dn($connection)
    {
        $connection->getSchemaName()->willReturn('example');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');
        $connection->delete('cn=foo,dc=foo,dc=bar')->willReturn(null);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../resources/schema');
        $cache = CacheFactory::get('none', []);
        $dispatcher = new SymfonyEventDispatcher();
        $factory = new LdapObjectSchemaFactory($cache, $parser, $dispatcher);

        $this->beConstructedWith($connection, $factory, $dispatcher);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $this->delete($ldapObject);
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_update_a_ldap_object_using_batch_modify($connection)
    {
        $batch = [
            [
                'attrib' => 'givenName',
                'modtype' => LDAP_MODIFY_BATCH_REPLACE,
                'values' => ['Chad'],
            ],
            [
                'attrib' => 'sn',
                'modtype' => LDAP_MODIFY_BATCH_ADD,
                'values' => ['Sikorra'],
            ],
            [
                'attrib' => 'sAMAccountName',
                'modtype' => LDAP_MODIFY_BATCH_REMOVE,
                'values' => ['csikorra'],
            ],
            [
                'attrib' => 'mail',
                'modtype' => LDAP_MODIFY_BATCH_REMOVE_ALL,
            ],
        ];
        $connection->getSchemaName()->willReturn('example');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');
        $connection->modifyBatch('cn=foo,dc=foo,dc=bar', $batch)->willReturn(null);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $dispatcher = new SymfonyEventDispatcher();
        $factory = new LdapObjectSchemaFactory($cache, $parser, $dispatcher);

        $this->beConstructedWith($connection, $factory, $dispatcher);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $ldapObject->set('firstName', 'Chad');
        $ldapObject->add('lastName', 'Sikorra');
        $ldapObject->remove('username', 'csikorra');
        $ldapObject->reset('emailAddress');
        $this->persist($ldapObject);
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_move_a_ldap_object_using_move($connection)
    {
        $connection->getSchemaName()->willReturn('example');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');
        $connection->move('cn=foo,dc=foo,dc=bar', 'cn=foo', 'ou=employees,dc=foo,dc=bar')->willReturn(null);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $dispatcher = new SymfonyEventDispatcher();
        $factory = new LdapObjectSchemaFactory($cache, $parser, $dispatcher);

        $this->beConstructedWith($connection, $factory, $dispatcher);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar', 'name' => 'foo'], [], 'user', 'user');
        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_escape_the_RDN_when_moving_a_ldap_object($connection)
    {
        $connection->getSchemaName()->willReturn('example');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');
        $connection->move('cn=foo\, bar,dc=foo,dc=bar', 'cn=foo\2c bar', 'ou=employees,dc=foo,dc=bar')->willReturn(null);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $dispatcher = new SymfonyEventDispatcher();
        $factory = new LdapObjectSchemaFactory($cache, $parser, $dispatcher);

        $this->beConstructedWith($connection, $factory, $dispatcher);

        $ldapObject = new LdapObject(['dn' => 'cn=foo\, bar,dc=foo,dc=bar', 'name' => 'foo, bar'], [], 'user', 'user');
        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    function it_should_move_an_object_without_a_schema_type()
    {
        $this->connection->move('cn=foo,dc=foo,dc=bar', 'cn=foo', 'ou=employees,dc=foo,dc=bar')->willReturn(null);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', '');
        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_query_ldap_for_the_RDN_when_moving_an_object_and_the_name_attribute_was_not_selected($connection, $dispatcher)
    {
        $connection->getSchemaName()->willReturn('example');
        $connection->getLdapType()->willReturn('ad');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');
        $connection->move('cn=foo,dc=foo,dc=bar', 'cn=foo', 'ou=employees,dc=foo,dc=bar')->willReturn(null);
        $connection->search('(&(&(objectCategory=\70\65\72\73\6f\6e)(objectClass=\75\73\65\72))(&(dn=\63\6e\3d\66\6f\6f\2c\64\63\3d\66\6f\6f\2c\64\63\3d\62\61\72)))',["cn"], null,'subtree', null)->willReturn($this->ldapEntries);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $dispatcher = new SymfonyEventDispatcher();
        $factory = new LdapObjectSchemaFactory($cache, $parser, $dispatcher);

        $this->beConstructedWith($connection, $factory, $dispatcher);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     * @param \LdapTools\Event\EventDispatcherInterface $dispatcher
     */
    function it_should_call_the_event_dispatcher_delete_events_when_deleting_an_object($connection, $dispatcher)
    {
        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $beforeEvent = new LdapObjectEvent(Event::LDAP_OBJECT_BEFORE_DELETE, $ldapObject);
        $afterEvent = new LdapObjectEvent(Event::LDAP_OBJECT_AFTER_DELETE, $ldapObject);
        $connection->getSchemaName()->willReturn('example');
        $connection->getLdapType()->willReturn('ad');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');
        $connection->delete('cn=foo,dc=foo,dc=bar')->willReturn(null);
        $dispatcher->dispatch($beforeEvent)->shouldBeCalled();
        $dispatcher->dispatch($afterEvent)->shouldBeCalled();

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $factoryDispatcher = new SymfonyEventDispatcher();
        $factory = new LdapObjectSchemaFactory($cache, $parser, $factoryDispatcher);

        $this->beConstructedWith($connection, $factory, $dispatcher);
        $this->delete($ldapObject);
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     * @param \LdapTools\Event\EventDispatcherInterface $dispatcher
     */
    function it_should_call_the_event_dispatcher_move_events_when_moving_an_object($connection, $dispatcher)
    {
        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar', 'name' => 'foo'], [], 'user', 'user');
        $beforeEvent = new LdapObjectMoveEvent(Event::LDAP_OBJECT_BEFORE_MOVE, $ldapObject, 'ou=employees,dc=foo,dc=bar');
        $afterEvent = new LdapObjectMoveEvent(Event::LDAP_OBJECT_AFTER_MOVE, $ldapObject, 'ou=employees,dc=foo,dc=bar');
        $connection->getSchemaName()->willReturn('example');
        $connection->getLdapType()->willReturn('ad');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');
        $connection->move('cn=foo,dc=foo,dc=bar', 'cn=foo', 'ou=employees,dc=foo,dc=bar')->willReturn(null);
        $dispatcher->dispatch($beforeEvent)->shouldBeCalled();
        $dispatcher->dispatch($afterEvent)->shouldBeCalled();

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $factoryDispatcher = new SymfonyEventDispatcher();
        $factory = new LdapObjectSchemaFactory($cache, $parser, $factoryDispatcher);

        $this->beConstructedWith($connection, $factory, $dispatcher);
        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     * @param \LdapTools\Event\EventDispatcherInterface $dispatcher
     */
    function it_should_call_the_event_dispatcher_modify_events_when_persisting_an_object($connection, $dispatcher)
    {
        $batch = [
            [
                'attrib' => 'givenName',
                'modtype' => LDAP_MODIFY_BATCH_REPLACE,
                'values' => ['Chad'],
            ],
            [
                'attrib' => 'sn',
                'modtype' => LDAP_MODIFY_BATCH_ADD,
                'values' => ['Sikorra'],
            ],
            [
                'attrib' => 'sAMAccountName',
                'modtype' => LDAP_MODIFY_BATCH_REMOVE,
                'values' => ['csikorra'],
            ],
            [
                'attrib' => 'mail',
                'modtype' => LDAP_MODIFY_BATCH_REMOVE_ALL,
            ],
        ];
        $connection->getSchemaName()->willReturn('example');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');
        $connection->modifyBatch('cn=foo,dc=foo,dc=bar', $batch)->willReturn(null);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $schemaDispatcher = new SymfonyEventDispatcher();
        $factory = new LdapObjectSchemaFactory($cache, $parser, $schemaDispatcher);

        $this->beConstructedWith($connection, $factory, $dispatcher);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
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
        $this->connection->modifyBatch(Argument::any(), Argument::any())->shouldNotBeCalled();

        $this->persist($ldapObject);
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     * @param \LdapTools\Event\EventDispatcherInterface $dispatcher
     */
    function it_should_not_call_the_event_dispatcher_modify_events_when_an_object_has_not_changed($connection, $dispatcher)
    {
        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $schemaDispatcher = new SymfonyEventDispatcher();
        $factory = new LdapObjectSchemaFactory($cache, $parser, $schemaDispatcher);

        $this->beConstructedWith($connection, $factory, $dispatcher);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $beforeEvent = new LdapObjectEvent(Event::LDAP_OBJECT_BEFORE_MODIFY, $ldapObject);
        $afterEvent = new LdapObjectEvent(Event::LDAP_OBJECT_AFTER_MODIFY, $ldapObject);
        $dispatcher->dispatch($beforeEvent)->shouldNotBeCalled();
        $dispatcher->dispatch($afterEvent)->shouldNotBeCalled();

        $this->persist($ldapObject);
    }

    function it_should_persist_an_ldap_object_that_has_no_schema_type()
    {
        $ldapObject = new LdapObject(['dn' => 'cn=user,dc=foo,dc=bar'], ['user'], 'user', '');
        $ldapObject->set('foo', 'bar');

        $this->connection->modifyBatch("cn=user,dc=foo,dc=bar", [["attrib" => "foo", "modtype" => 3, "values" => ["bar"]]])->shouldBeCalled();
        $this->persist($ldapObject);
    }
}

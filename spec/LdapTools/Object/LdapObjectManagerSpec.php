<?php

namespace spec\LdapTools\Object;

use LdapTools\Configuration;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Factory\CacheFactory;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Factory\SchemaParserFactory;
use LdapTools\Object\LdapObject;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapObjectManagerSpec extends ObjectBehavior
{
    function let(LdapConnectionInterface $connection)
    {
        $connection->getSchemaName()->willReturn('example');
        $connection->__toString()->willReturn('example.com');

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../resources/schema');
        $cache = CacheFactory::get('none', []);
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Object\LdapObjectManager');
    }

    function it_should_error_on_update_or_delete_if_the_dn_is_not_set()
    {
        $ldapObject = new LdapObject(['foo' => 'bar'], [], 'user', 'user');

        $this->shouldThrow('\InvalidArgumentException')->duringPersist($ldapObject);
        $this->shouldThrow('\InvalidArgumentException')->duringDelete($ldapObject);
    }

    function it_should_delete_a_ldap_object_from_its_dn(LdapConnectionInterface $connection)
    {
        $connection->getSchemaName()->willReturn('example');
        $connection->__toString()->willReturn('example.com');
        $connection->delete('cn=foo,dc=foo,dc=bar')->willReturn(null);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../resources/schema');
        $cache = CacheFactory::get('none', []);
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $this->delete($ldapObject);
    }

    function it_should_update_a_ldap_object_using_batch_modify(LdapConnectionInterface $connection)
    {
        $batch = [
            [
                'attrib' => 'givenName',
                'modtype' => LDAP_MODIFY_BATCH_REPLACE,
                'values' => 'Chad',
            ],
            [
                'attrib' => 'sn',
                'modtype' => LDAP_MODIFY_BATCH_ADD,
                'values' => 'Sikorra',
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
        $connection->modifyBatch('cn=foo,dc=foo,dc=bar', $batch)->willReturn(null);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $ldapObject->set('firstName', 'Chad');
        $ldapObject->add('lastName', 'Sikorra');
        $ldapObject->remove('username', 'csikorra');
        $ldapObject->reset('emailAddress');
        $this->persist($ldapObject);
    }
}

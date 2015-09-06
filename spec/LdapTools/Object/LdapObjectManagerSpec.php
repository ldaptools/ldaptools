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

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function let($connection)
    {
        $connection->getSchemaName()->willReturn('example');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');

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
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);

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
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);

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
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);

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
        $connection->move('cn=foo,dc=foo,dc=bar', 'cn=foo\2c bar', 'ou=employees,dc=foo,dc=bar')->willReturn(null);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar', 'name' => 'foo, bar'], [], 'user', 'user');
        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_error_moving_if_a_schema_type_is_not_defined($connection)
    {
        $connection->getSchemaName()->willReturn('example');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');
        $connection->move('cn=foo,dc=foo,dc=bar', 'cn=foo', 'ou=employees,dc=foo,dc=bar')->willReturn(null);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', '');
        $this->shouldThrow(new \InvalidArgumentException("The LDAP object must have a schema type defined to perform this action."))->duringMove($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_error_moving_if_a_schema_does_not_have_a_name_attribute($connection)
    {
        $connection->getSchemaName()->willReturn('example');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');
        $connection->move('cn=foo,dc=foo,dc=bar', 'cn=foo', 'ou=employees,dc=foo,dc=bar')->willReturn(null);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'noname');
        $this->shouldThrow(new \InvalidArgumentException('The LdapObject type "noname" needs a "name" attribute defined that references the RDN.'))->duringMove($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_error_moving_if_the_ldap_object_name_field_cannot_be_queried_when_not_selected($connection)
    {
        $connection->getSchemaName()->willReturn('example');
        $connection->getLdapType()->willReturn('ad');
        $connection->__toString()->willReturn('example.com');
        $connection->getEncoding()->willReturn('UTF-8');
        $connection->move('cn=foo,dc=foo,dc=bar', 'cn=foo', 'ou=employees,dc=foo,dc=bar')->willReturn(null);
        $connection->search('(&(&(objectCategory=\70\65\72\73\6f\6e)(objectClass=\75\73\65\72))(&(dn=\63\6e\3d\66\6f\6f\2c\64\63\3d\66\6f\6f\2c\64\63\3d\62\61\72)))',["cn"], null,'subtree', null)->willReturn([]);

        $config = new Configuration();
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), __DIR__.'/../../resources/schema');
        $cache = CacheFactory::get('none', []);
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $this->shouldThrow(new \RuntimeException("Unable to retrieve the RDN value for the LdapObject"))->duringMove($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_query_ldap_for_the_RDN_when_moving_an_object_and_the_name_attribute_was_not_selected($connection)
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
        $factory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($connection, $factory);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $this->move($ldapObject, 'ou=employees,dc=foo,dc=bar');
    }
}

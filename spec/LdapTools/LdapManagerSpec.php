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

use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Connection\LdapControl;
use LdapTools\Connection\LdapControlType;
use LdapTools\Exception\InvalidArgumentException;
use LdapTools\Object\LdapObject;
use LdapTools\Operation\AuthenticationOperation;
use LdapTools\Operation\AuthenticationResponse;
use LdapTools\Operation\BatchModifyOperation;
use LdapTools\Operation\DeleteOperation;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use \LdapTools\Configuration;
use \LdapTools\DomainConfiguration;

class LdapManagerSpec extends ObjectBehavior
{
    /**
     * @var Configuration
     */
    protected $config;

    /**
     * @var DomainConfiguration
     */
    protected $domain;
    
    function let()
    {
        $config = new Configuration();

        $domain = new DomainConfiguration('example.com');
        $domain->setServers(['example'])
            ->setLazyBind(true)
            ->setLdapType('openldap')
            ->setBaseDn('dc=example,dc=com');

        $anotherDomain = new DomainConfiguration('test.com');
        $anotherDomain->setServers(['test'])
            ->setLazyBind(true)
            ->setLdapType('ad')
            ->setBaseDn('dc=test,dc=com');

        $config->addDomain($domain, $anotherDomain);

        $this->config = $config;
        $this->domain = $domain;
        $this->beConstructedWith($config);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\LdapManager');
    }

    function it_should_allow_ldap_connections_to_be_passed_to_the_constructor(LdapConnectionInterface $connection, LdapConnectionInterface $connection2)
    {
        $domainConfig2 = new DomainConfiguration('foo.bar');
        $connection2->getConfig()->willReturn($domainConfig2);

        $domainConfig = new DomainConfiguration('example.local');
        $connection->getConfig()->willReturn($domainConfig);

        $config = new Configuration();
        $this->beConstructedWith($config, $connection, $connection2);

        $this->getDomainContext()->shouldBeEqualTo('example.local');
        $this->getConnection()->shouldBeEqualTo($connection);
        $this->switchDomain('foo.bar')->getConnection()->shouldBeEqualTo($connection2);
    }

    function it_should_return_a_ldap_connection_when_calling_getConnection()
    {
        $this->getConnection()->shouldReturnAnInstanceOf('\LdapTools\Connection\LdapConnectionInterface');
        $this->getConnection()->getConfig()->getDomainName()->shouldBeEqualTo('example.com');
    }

    function it_should_return_a_ldap_connection_when_calling_getConnection_with_a_specific_domain()
    {
        $this->getConnection('test.com')->shouldReturnAnInstanceOf('\LdapTools\Connection\LdapConnectionInterface');
        $this->getConnection('test.com')->getConfig()->getDomainName()->shouldBeEqualTo('test.com');
        $this->getDomainContext()->shouldBeEqualTo('example.com');
    }

    function it_should_error_when_trying_to_get_a_connection_that_doesnt_exist()
    {
        $e = new InvalidArgumentException('Domain "foo" is not valid. Valid domains are: example.com, test.com');

        $this->shouldThrow($e)->duringGetConnection('foo');
    }

    function it_should_return_a_LdapQueryBuilder_when_calling_buildLdapQuery()
    {
        $this->buildLdapQuery()->shouldHaveType('\LdapTools\Query\LdapQueryBuilder');
    }

    function it_should_return_the_first_added_domain_when_calling_getDomainContext()
    {
        $this->getDomainContext()->shouldBeEqualTo('example.com');
    }

    function it_should_switch_the_domain_context_when_calling_switchDomain()
    {
        $this->switchDomain('test.com');
        $this->getDomainContext()->shouldBeEqualTo('test.com');
    }

    function it_should_return_an_array_when_calling_getDomains()
    {
        $this->getDomains()->shouldBeArray();
    }

    function it_should_return_the_correct_number_of_domains_when_calling_getDomains()
    {
        $this->getDomains()->shouldHaveCount(2);
    }

    function it_should_return_the_correct_domains_when_calling_getDomains()
    {
        $this->getDomains()->shouldContain('test.com');
        $this->getDomains()->shouldContain('example.com');
    }

    function it_should_throw_a_RuntimeException_when_adding_a_config_with_no_domains()
    {
        $this->shouldThrow('\RuntimeException')->during('__construct', [ new Configuration() ]);
    }

    function it_should_honor_the_default_domain_configuration_option()
    {
        $config = new Configuration();

        $domain = new DomainConfiguration('example.com');
        $domain->setServers(['example'])
            ->setLazyBind(true)
            ->setBaseDn('dc=example,dc=com');

        $anotherDomain = new DomainConfiguration('test.com');
        $anotherDomain->setServers(['test'])
            ->setLazyBind(true)
            ->setBaseDn('dc=test,dc=com');

        $config->addDomain($domain, $anotherDomain);
        $config->setDefaultDomain('test.com');

        $this->beConstructedWith($config);
        $this->getDomainContext()->shouldBeEqualTo('test.com');
    }

    function it_should_return_a_ldap_object_repository_when_calling_getRepository()
    {
        $this->getRepository('user')->shouldHaveType('\LdapTools\Object\LdapObjectRepository');
    }

    function it_should_error_when_calling_getRepository_for_a_type_that_does_not_exist()
    {
        $this->config->setSchemaFolder(__DIR__.'/../resources/schema');
        $this->domain->setSchemaName('example');
        $this->beConstructedWith($this->config);
        $this->shouldThrow(new \RuntimeException('Unable to load Repository for type "foo": Cannot find object type "foo" in schema.'))->duringGetRepository('foo');
        $this->shouldThrow(new \RuntimeException('Unable to load Repository for type "user": Repository class "\Foo\Bar" not found.'))->duringGetRepository('user');
    }

    function it_should_return_a_ldap_object_creator_when_calling_createLdapObject()
    {
        $this->createLdapObject()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCreator');
    }

    function it_should_allow_specifying_the_object_type_to_create_when_calling_createLdapObject(LdapConnectionInterface $connection)
    {
        $domain = (new DomainConfiguration('foo.bar'))->setBaseDn('dc=foo,dc=bar')->setUseTls(true);
        $connection->getConfig()->willReturn($domain);
        $connection->getRootDse()->willReturn(new LdapObject(['dc' => '']));
        $this->beConstructedWith(new Configuration(), $connection);

        $connection->execute(Argument::that(function($operation) {
            return array_key_exists('samaccountname', array_change_key_case($operation->getAttributes()));
        }))->shouldBeCalled();
        $this->createLdapObject('user')->with(['username' => 'foo', 'password' => 'bar'])->in('dc=foo,dc=bar')->execute();
    }

    function it_should_attempt_to_authenticate_a_username_and_password(LdapConnectionInterface $connection)
    {
        $operation = new AuthenticationOperation();
        $operation->setUsername('foo')->setPassword('bar');
        $response = new AuthenticationResponse(true);
        $domainConfig = new DomainConfiguration('example.local');
        $connection->getConfig()->willReturn($domainConfig);
        $connection->execute($operation)->willReturn($response);
        $this->beConstructedWith(new Configuration(), $connection);

        $this->authenticate('foo','bar')->shouldBeEqualTo(true);
    }

    function it_should_set_a_ldap_connection(LdapConnectionInterface $connection, LdapConnectionInterface $connection2)
    {
        $domainConfig = new DomainConfiguration('foo.bar');
        $connection->getConfig()->willReturn($domainConfig);

        $domainConfig2 = new DomainConfiguration('chad.sikorra');
        $connection2->getConfig()->willReturn($domainConfig2);

        $this->addConnection($connection, $connection2)->shouldReturnAnInstanceOf('\LdapTools\LdapManager');
        $this->getConnection('foo.bar')->shouldBeEqualTo($connection);
        $this->getConnection('chad.sikorra')->shouldBeEqualTo($connection2);
    }

    function it_should_register_converters_listed_in_the_config()
    {
        $config = new Configuration();
        $config->setSchemaFolder(__DIR__.'/../resources/schema');
        $config->setAttributeConverters(['my_bool' => '\LdapTools\AttributeConverter\ConvertBoolean']);
        $domain = new DomainConfiguration('example.com');
        $domain->setServers(['example'])
            ->setLazyBind(true)
            ->setBaseDn('dc=example,dc=com')
            ->setSchemaName('example');
        $config->addDomain($domain);

        $this->beConstructedWith($config);
        $this->buildLdapQuery()
            ->from('custom_converter')
            ->where(['foo' => true])
            ->toLdapFilter()->shouldBeEqualTo('(&(objectClass=foo)(&(bar=TRUE)))');
    }

    function it_should_return_the_cache_class_in_use()
    {
        $this->getCache()->shouldReturnAnInstanceOf('\LdapTools\Cache\CacheInterface');
    }

    function it_should_return_the_schema_parser_in_use()
    {
        $this->getSchemaParser()->shouldReturnAnInstanceOf('\LdapTools\Schema\Parser\SchemaParserInterface');
    }

    function it_should_return_the_ldap_object_schema_factory_in_use()
    {
        $this->getSchemaFactory()->shouldReturnAnInstanceOf('\LdapTools\Factory\LdapObjectSchemaFactory');
    }

    function it_should_return_the_event_dispatcher_instance()
    {
        $this->getEventDispatcher()->shouldReturnAnInstanceOf('\LdapTools\Event\EventDispatcherInterface');
    }

    function it_should_delete_a_ldap_object(LdapConnectionInterface $connection)
    {
        $domainConfig = new DomainConfiguration('example.local');
        $connection->getConfig()->willReturn($domainConfig);
        $this->beConstructedWith(new Configuration(), $connection);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], 'user');
        $operation = new DeleteOperation($ldapObject->get('dn'));
        $connection->execute($operation)->shouldBeCalled();

        $this->delete($ldapObject);

        $operation->addControl((new LdapControl(LdapControlType::SUB_TREE_DELETE))->setCriticality(true));
        $connection->execute($operation)->shouldBeCalled();

        $this->delete($ldapObject, true);
    }

    function it_should_restore_a_ldap_object(LdapConnectionInterface $connection)
    {
        $domainConfig = new DomainConfiguration('example.local');
        $connection->getConfig()->willReturn($domainConfig);
        $this->beConstructedWith(new Configuration(), $connection);
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

    function it_should_get_a_ldif_object()
    {
        $this->createLdif()->shouldReturnAnInstanceOf('\LdapTools\Ldif\Ldif');
    }
}

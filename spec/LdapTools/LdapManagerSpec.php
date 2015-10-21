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

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use \LdapTools\Configuration;
use \LdapTools\DomainConfiguration;

class LdapManagerSpec extends ObjectBehavior
{
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

        $this->beConstructedWith($config);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\LdapManager');
    }

    function it_should_return_a_ldap_connection_when_calling_getConnection()
    {
        $this->getConnection()->shouldReturnAnInstanceOf('\LdapTools\Connection\LdapConnectionInterface');
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
        $this->shouldThrow('\Exception')->duringGetRepository('foo');
    }

    function it_should_return_a_ldap_object_creator_when_calling_createLdapObject()
    {
        $this->createLdapObject()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCreator');
    }

    /**
     * Unsure of how to really mock this behavior since the connection is instantiated from the config.
     */
    function it_should_attempt_to_authenticate_a_username_and_password()
    {
        $this->shouldThrow('\LdapTools\Exception\LdapConnectionException')->duringAuthenticate('foo','bar');
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
            ->select()
            ->from('custom_converter')
            ->where(['foo' => true])
            ->getLdapFilter()->shouldBeEqualTo('(&(objectClass=\66\6f\6f)(&(bar=\54\52\55\45)))');
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

    function it_should_return_the_event_dispatcher()
    {
        $this->getEventDispatcher()->shouldReturnAnInstanceOf('\LdapTools\Event\EventDispatcherInterface');
    }
}

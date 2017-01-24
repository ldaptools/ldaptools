<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Object;

use LdapTools\Cache\NoCache;
use LdapTools\Configuration;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\DomainConfiguration;
use LdapTools\Factory\CacheFactory;
use LdapTools\Event\SymfonyEventDispatcher;
use LdapTools\Factory\LdapObjectSchemaFactory;
use LdapTools\Factory\SchemaParserFactory;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapObjectRepositorySpec extends ObjectBehavior
{
    protected $ldapEntries = [
        'count' => 2,
        0 => [
            'cn' => [
                'count' => 1,
                0 => "Smith, Archie",
            ],
            0 => "cn",
            'sn' => [
                'count' => 1,
                0 => "Smith",
            ],
            1 => "sn",
            'givenname' => [
                'count' => 1,
                0 => "Archie",
            ],
            2 => "givenname",
            'whencreated' => [
                'count' => 1,
                0 => "19960622123421Z",
            ],
            3 => "whencreated",
            'count' => 3,
            'dn' => "CN=Smith\, Archie,OU=DE,OU=Employees,DC=example,DC=local",
        ],
        1 => [
            'cn' => [
                'count' => 1,
                0 => "Smith, John",
            ],
            0 => "cn",
            'sn' => [
                'count' => 1,
                0 => "Smith",
            ],
            1 => "sn",
            'givenname' => [
                'count' => 1,
                0 => "John",
            ],
            2 => "givenname",
            'whenCreated' => [
                'count' => 1,
                0 => "19920622123421Z",
            ],
            3 => "whenCreated",
            'count' => 3,
            'dn' => "CN=Smith\, John,OU=DE,OU=Employees,DC=example,DC=local",

        ]
    ];

    public function let(LdapConnectionInterface $connection)
    {
        $config = new Configuration();
        $config->setCache(new NoCache());
        $connection->execute(Argument::any())->willReturn($this->ldapEntries);
        $connection->getConfig()->willReturn(new DomainConfiguration('example.local'));

        $cache = CacheFactory::get($config->getCacheType(), $config->getCacheOptions());
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), $config->getSchemaFolder());
        $dispatcher = new SymfonyEventDispatcher();
        $schemaFactory = new LdapObjectSchemaFactory($cache, $parser, $dispatcher);

        $this->beConstructedWith($schemaFactory->get('ad', 'user'), $connection);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Object\LdapObjectRepository');
    }

    function it_should_call_findOneByGuid_and_return_a_LdapObject($connection)
    {
        $results = $this->ldapEntries;
        $results['count'] = 1;
        unset($results[1]);
        $this->setAttributes(['guid']);
        $connection->execute(Argument::any())->willReturn($results);
        $this->findOneByGuid('8E1F85EB-4882-4920-88A5-CF52F31D8D31')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
    }

    function it_should_call_findByFirstName()
    {
        $this->findByFirstName('foo')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCollection');
    }

    function it_should_call_findAll()
    {
        $this->findAll()->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCollection');
    }

    function it_should_not_error_when_the_attribute_is_not_in_the_schema()
    {
        $this->findByFooBar('test')->shouldHaveCount(2);
    }

    function it_should_set_default_attributes_when_calling_setAttributes()
    {
        $this->setAttributes(['foo']);
        $this->getAttributes()->shouldBeEqualTo(['foo']);
    }

    function it_should_get_set_the_hydration_mode_if_calling_set_hydration_mode()
    {
        $this->setHydrationMode('array');
        $this->getHydrationMode()->shouldBeEqualTo('array');
    }

    function it_should_respect_the_explicitly_set_hydration_mode()
    {
        $this->setHydrationMode('array');
        $this->findByFirstName('foo')->shouldBeArray();
    }

    function it_should_return_a_LdapQueryBuilder_instance_when_calling_build_ldap_query()
    {
        $this->buildLdapQuery()->shouldReturnAnInstanceOf('\LdapTools\Query\LdapQueryBuilder');

        $this->buildLdapQuery()->toLdapFilter()->shouldBeEqualTo('(&(objectCategory=person)(objectClass=user))');
    }
}

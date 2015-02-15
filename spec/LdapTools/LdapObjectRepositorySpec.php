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

use LdapTools\Configuration;
use LdapTools\Connection\LdapConnection;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Factory\CacheFactory;
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

    public function let(LdapConnectionInterface $ldap)
    {
        $config = new Configuration();
        $config->setCacheType('none');
        $ldap->search(Argument::any(), Argument::any(), Argument::any(), Argument::any(), Argument::any())->willReturn($this->ldapEntries);
        $ldap->getLdapType()->willReturn(LdapConnection::TYPE_AD);

        $cache = CacheFactory::get($config->getCacheType(), $config->getCacheOptions());
        $parser = SchemaParserFactory::get($config->getSchemaFormat(), $config->getSchemaFolder());
        $schemaFactory = new LdapObjectSchemaFactory($cache, $parser);

        $this->beConstructedWith($schemaFactory->get('ad', 'user'), $ldap);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\LdapObjectRepository');
    }

    function it_should_call_findOneByGuid_and_return_a_LdapObject()
    {
        $this->findOneByGuid('foo')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
    }

    function it_should_call_findByFirstName()
    {
        $this->findByFirstName('foo')->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCollection');
    }

    function it_should_error_when_calling_findOneByFooBar()
    {
        $this->shouldThrow('\RuntimeException')->duringfindOneByFooBar('test');
    }

    function it_should_error_when_calling_findByFooBar()
    {
        $this->shouldThrow('\RuntimeException')->duringfindOneByFooBar('test');
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
}

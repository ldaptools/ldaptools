<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Resolver;

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\DomainConfiguration;
use LdapTools\Operation\AddOperation;
use LdapTools\Schema\LdapObjectSchema;
use LdapTools\Schema\Parser\SchemaYamlParser;
use PhpSpec\ObjectBehavior;

class AttributeValueResolverSpec extends ObjectBehavior
{
    protected $entryTo = [
        'username' => 'chad',
        'emailAddress' => 'Chad.Sikorra@gmail.com',
        'disabled' => false,
        'passwordMustChange' => true,
        'groups' => ['foo'],
    ];

    protected $entryFrom = [
        'username' => 'chad',
        'emailAddress' => 'Chad.Sikorra@gmail.com',
        'disabled' => 512,
        'passwordMustChange' => 0,
    ];

    protected $expectedResult = [
        'count' => 1,
        0 => [
            'userAccountControl' => [
                'count' => 1,
                0 => "512",
            ],
            'count' => 2,
            'dn' => "CN=foo,DC=foo,DC=bar",
        ],
    ];

    /**
     * @var array
     */
    protected $expectedElcResult = [
        'count' => 1,
        0 => [
            'msExchELCMailboxFlags' => [
                'count' => 1,
                0 => "2",
            ],
            'count' => 1,
            'dn' => "CN=foo,DC=foo,DC=bar",
        ],
    ];

    /**
     * @var LdapObjectSchema
     */
    protected $schema;

    function let(LdapConnectionInterface $connection, AddOperation $operation)
    {
        $parser = new SchemaYamlParser(__DIR__.'/../../../resources/schema');
        $this->schema = $parser->parse('exchange', 'ExchangeMailboxUser');
        $connection->getConfig()->willReturn(new DomainConfiguration('foo.bar'));

        $this->beConstructedThrough('getInstance', [$this->schema, $this->entryTo, AttributeConverterInterface::TYPE_CREATE]);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Resolver\AttributeValueResolver');
    }

    function it_should_allow_setting_the_dn()
    {
        $this->setDn('cn=foo,dc=foo,dc=bar')->shouldBeNull();
    }

    function it_should_allow_setting_the_ldap_connection($connection)
    {
        $this->setLdapConnection($connection);
    }

    function it_should_convert_values_to_ldap($operation)
    {
        $this->setOperation($operation);
        $this->toLdap()->shouldHaveKeyWithValue('userAccountControl','512');
        $this->toLdap()->shouldHaveKeyWithValue('username','chad');
        $this->toLdap()->shouldHaveKeyWithValue('emailAddress','Chad.Sikorra@gmail.com');
        $this->toLdap()->shouldHaveKeyWithValue('passwordMustChange','0');
    }

    function it_should_aggregate_properly_on_creation($operation)
    {
        $entry = $this->entryTo;
        $entry['disabled'] = true;
        $entry['passwordNeverExpires'] = true;
        $entry['trustedForAllDelegation'] = true;

        $this->beConstructedWith($this->schema, $entry, AttributeConverterInterface::TYPE_CREATE);
        $this->setOperation($operation);
        $this->toLdap()->shouldHaveKeyWithValue('userAccountControl','590338');
    }

    function it_should_convert_values_from_ldap()
    {
        $this->beConstructedWith($this->schema, $this->entryFrom, AttributeConverterInterface::TYPE_SEARCH_FROM);

        $this->fromLdap()->shouldHaveKeyWithValue('disabled', false);
        $this->fromLdap()->shouldHaveKeyWithValue('username','chad');
        $this->fromLdap()->shouldHaveKeyWithValue('emailAddress','Chad.Sikorra@gmail.com');
        $this->fromLdap()->shouldHaveKeyWithValue('passwordMustChange', true);
    }

    function it_should_remove_attributes_when_specified_by_a_converter_implementing_OperationGeneratorInterface($operation)
    {
        $this->setOperation($operation);
        $this->toLdap()->shouldNotContain('groups');
    }
}

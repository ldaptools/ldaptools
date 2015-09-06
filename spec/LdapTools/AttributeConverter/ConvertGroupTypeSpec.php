<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\AttributeConverter;

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\Connection\LdapConnectionInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertGroupTypeSpec extends ObjectBehavior
{
    protected $connection;

    protected $expectedSearch = [
        '(&(distinguishedName=\63\6e\3d\66\6f\6f\2c\64\63\3d\66\6f\6f\2c\64\63\3d\62\61\72))',
        ['groupType'],
        null,
        "subtree",
        null,
    ];

    protected $expectedResult = [
        'count' => 1,
        0 => [
            'groupType' => [
                'count' => 1,
                0 => "-2147483646",
            ],
            'count' => 2,
            'dn' => "CN=foo,DC=foo,DC=bar",
        ],
    ];

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function let($connection)
    {
        $this->connection = $connection;
        $options = [
            'defaultValue' => '-2147483646',
            'distribution' =>'typeDistribution',
            'types' => [
                'scope' => [ 'scopeDomainLocal', 'scopeGlobal', 'scopeUniversal' ],
                'type' => [ 'typeBuiltin', 'typeSecurity', 'typeDistribution' ],
            ],
            'typeMap' => [
                'typeBuiltin' => '1',
                'typeSecurity' => '2147483648',
                'typeDistribution' => '2147483648',
                'scopeDomainLocal' => '4',
                'scopeGlobal' => '2',
                'scopeUniversal' => '8',
            ],
        ];
        $this->setOptions($options);
        $this->setLdapConnection($connection);
        $this->setDn('cn=foo,dc=foo,dc=bar');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertGroupType');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_convert_a_value_from_ldap_to_a_php_bool()
    {
        $this->setAttribute('typeDistribution');
        // A security type group...
        $this->fromLdap('-2147483646')->shouldBeEqualTo(false);
        // A distribution type group...
        $this->fromLdap('2')->shouldBeEqualTo(true);

        $this->setAttribute('typeSecurity');
        // A security type group...
        $this->fromLdap('-2147483646')->shouldBeEqualTo(true);
        // A distribution type group...
        $this->fromLdap('2')->shouldBeEqualTo(false);

        $this->setAttribute('scopeGlobal');
        $this->fromLdap('2')->shouldBeEqualTo(true);
        $this->fromLdap('-2147483646')->shouldBeEqualTo(true);
        $this->fromLdap('4')->shouldBeEqualTo(false);

        $this->setAttribute('scopeDomainLocal');
        $this->fromLdap('4')->shouldBeEqualTo(true);
        $this->fromLdap('-2147483644')->shouldBeEqualTo(true);
        $this->fromLdap('2')->shouldBeEqualTo(false);

        $this->setAttribute('scopeUniversal');
        $this->fromLdap('8')->shouldBeEqualTo(true);
        $this->fromLdap('-2147483640')->shouldBeEqualTo(true);
        $this->fromLdap('4')->shouldBeEqualTo(false);
    }

    function it_should_not_aggregate_values_on_a_search()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_FROM);
        $this->getShouldAggregateValues()->shouldBeEqualTo(false);
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->getShouldAggregateValues()->shouldBeEqualTo(false);
    }

    function it_should_aggregate_values_when_converting_a_bool_to_ldap_on_modification()
    {
        $this->connection->getLdapType()->willReturn('ad');
        $this->connection->search(...$this->expectedSearch)->willReturn($this->expectedResult);

        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->getShouldAggregateValues()->shouldBeEqualTo(true);
        $this->setAttribute('typeDistribution');
        $this->toLdap(true)->shouldBeEqualTo('2');
        $this->setAttribute('scopeUniversal');
        $this->toLdap(true)->shouldBeEqualTo('8');
        $this->setAttribute('typeSecurity');
        $this->toLdap(true)->shouldBeEqualTo('-2147483640');
    }

    function it_should_aggregate_values_when_converting_a_bool_to_ldap_on_creation()
    {
        $this->connection->getLdapType()->willReturn('ad');

        $this->connection->search(...$this->expectedSearch)->willReturn($this->expectedResult);
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->getShouldAggregateValues()->shouldBeEqualTo(true);
        $this->setAttribute('typeDistribution');
        $this->toLdap(true)->shouldBeEqualTo('2');
        $this->setAttribute('scopeUniversal');
        $this->toLdap(true)->shouldBeEqualTo('8');
        $this->setAttribute('typeSecurity');
        $this->toLdap(true)->shouldBeEqualTo('-2147483640');
    }

    function it_should_not_modify_the_value_if_the_bit_is_already_set()
    {
        $this->connection->getLdapType()->willReturn('ad');
        $result = $this->expectedResult;
        $result[0]['userAccountControl'][0] = ['514'];

        $this->connection->search(...$this->expectedSearch)->willReturn($this->expectedResult);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setAttribute('typeSecurity');
        $this->toLdap(true)->shouldBeEqualTo('-2147483646');
        $this->setAttribute('scopeGlobal');
        $this->toLdap(true)->shouldBeEqualTo('-2147483646');
    }

    function it_should_error_on_modifcation_when_the_existing_LDAP_object_cannot_be_queried()
    {
        $this->connection->getLdapType()->willReturn('ad');
        $this->connection->search(...$this->expectedSearch)->willReturn(['count' => 0]);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setAttribute('typeSecurity');
        $this->shouldThrow(new \RuntimeException("Unable to find LDAP object: cn=foo,dc=foo,dc=bar"))->duringToLdap(true);
    }

    function it_should_error_when_a_dn_is_not_set_and_a_modification_type_is_requested()
    {
        $this->setDn(null);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setAttribute('typeDistribution');
        $this->shouldThrow(new \RuntimeException('Unable to query for the current "groupType" attribute.'))->duringToLdap(true);
    }

    function it_should_be_case_insensitive_to_the_current_attribute_name()
    {
        $this->connection->getLdapType()->willReturn('ad');

        $this->connection->search(...$this->expectedSearch)->willReturn($this->expectedResult);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setAttribute('TypeSecuritY');
        $this->toLdap(true)->shouldBeEqualTo("-2147483646");
    }
}

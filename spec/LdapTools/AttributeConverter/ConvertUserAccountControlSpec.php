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

class ConvertUserAccountControlSpec extends ObjectBehavior
{
    protected $connection;

    protected $expectedSearch = [
        '(&(distinguishedName=\63\6e\3d\66\6f\6f\2c\64\63\3d\66\6f\6f\2c\64\63\3d\62\61\72))',
        ['userAccountControl'],
        null,
        "subtree",
        null,
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

    protected $expectedDisabledResult = [
        'count' => 1,
        0 => [
            'userAccountControl' => [
                'count' => 1,
                0 => "514",
            ],
            'count' => 2,
            'dn' => "CN=foo,DC=foo,DC=bar",
        ],
    ];

    function let(LdapConnectionInterface $connection)
    {
        $this->connection = $connection;
        $options = [
            'uacMap' => [
                'disabled' => '2',
                'passwordNeverExpires' => '65536',
                'smartCardRequired' => '262144',
                'trustedForAllDelegation' => '262144',
                'trustedForAnyAuthDelegation' => '16777216',
                'passwordIsReversible' => '128',
            ],
            'defaultValue' => '512',
        ];
        $this->setOptions($options);
        $this->setLdapConnection($connection);
        $this->setDn('cn=foo,dc=foo,dc=bar');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertUserAccountControl');
    }

    function it_should_implement_AttributeConverterInferface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_convert_a_value_from_ldap_to_a_php_bool()
    {
        $this->setAttribute('disabled');
        // 514 represents a "normal account" with the disabled bit set.
        $this->fromLdap('514')->shouldBeEqualTo(true);
        $this->fromLdap('513')->shouldBeEqualTo(false);

        // 66050 represents a "normal account" with the password never expires and disabled bits set.
        $this->fromLdap('66050')->shouldBeEqualTo(true);
        $this->setAttribute('passwordNeverExpires');
        $this->fromLdap('66050')->shouldBeEqualTo(true);

        // Everything in the map except for 'Password is Reversible'
        $this->fromLdap('328194')->shouldBeEqualTo(true);
        $this->setAttribute('disabled');
        $this->fromLdap('328194')->shouldBeEqualTo(true);
        $this->setAttribute('smartCardRequired');
        $this->fromLdap('328194')->shouldBeEqualTo(true);
        $this->setAttribute('trustedForAllDelegation');
        $this->fromLdap('328194')->shouldBeEqualTo(true);
        $this->setAttribute('passwordIsReversible');
        $this->fromLdap('328194')->shouldBeEqualTo(false);
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
        $this->setAttribute('disabled');
        $this->toLdap(true)->shouldBeEqualTo('514');
        $this->setLastValue('514');
        $this->setAttribute('passwordNeverExpires');
        $this->toLdap(true)->shouldBeEqualTo('66050');
    }

    function it_should_aggregate_values_when_converting_a_bool_to_ldap_on_creation()
    {
        $this->connection->getLdapType()->willReturn('ad');

        $this->connection->search(...$this->expectedSearch)->willReturn($this->expectedResult);
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->getShouldAggregateValues()->shouldBeEqualTo(true);
        $this->setAttribute('disabled');
        $this->toLdap(true)->shouldBeEqualTo('514');
        $this->setLastValue('514');
        $this->setAttribute('passwordNeverExpires');
        $this->toLdap(true)->shouldBeEqualTo('66050');
    }

    function it_should_not_modify_the_value_if_the_bit_is_already_set()
    {
        $this->connection->getLdapType()->willReturn('ad');
        $result = $this->expectedResult;
        $result[0]['userAccountControl'][0] = ['514'];

        $this->connection->search(...$this->expectedSearch)->willReturn($result);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setAttribute('disabled');
        $this->toLdap(true)->shouldBeEqualTo('514');
    }

    function it_should_remove_the_bit_if_requested_and_the_bit_is_already_set()
    {
        $this->connection->getLdapType()->willReturn('ad');
        $result = $this->expectedResult;
        $result[0]['userAccountControl'][0] = ['514'];

        $this->connection->search(...$this->expectedSearch)->willReturn($result);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setAttribute('disabled');
        $this->toLdap(false)->shouldBeEqualTo('512');
    }

    function it_should_error_on_modifcation_when_the_existing_LDAP_object_cannot_be_queried()
    {
        $this->connection->getLdapType()->willReturn('ad');
        $this->connection->search(...$this->expectedSearch)->willReturn(['count' => 0]);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setAttribute('disabled');
        $this->shouldThrow(new \RuntimeException("Unable to find LDAP object: cn=foo,dc=foo,dc=bar"))->duringToLdap(true);
    }

    function it_should_error_when_a_dn_is_not_set_and_a_modification_type_is_requested()
    {
        $this->setDn(null);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setAttribute('disabled');
        $this->shouldThrow(new \RuntimeException('Unable to query for the current userAccountControl attribute.'))->duringToLdap(true);
    }

    function it_should_be_case_insensitive_to_the_current_attribute_name()
    {
        $this->connection->getLdapType()->willReturn('ad');
        $result = $this->expectedResult;
        $result[0]['userAccountControl'][0] = ['514'];

        $this->connection->search(...$this->expectedSearch)->willReturn($result);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setAttribute('DisaBleD');
        $this->toLdap(false)->shouldBeEqualTo('512');
    }
}

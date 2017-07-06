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
use LdapTools\DomainConfiguration;
use LdapTools\Enums\AD\GroupType;
use LdapTools\Exception\AttributeConverterException;
use LdapTools\Operation\QueryOperation;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertGroupTypeSpec extends ObjectBehavior
{
    /**
     * @var QueryOperation
     */
    protected $expectedSearch;

    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @var callable
     */
    protected $expectedOp;

    /**
     * @var array
     */
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

    function let(LdapConnectionInterface $connection)
    {
        $connection->getConfig()->willReturn(new DomainConfiguration('foo.bar'));
        $this->expectedSearch = new QueryOperation('(&(distinguishedName=cn=foo,dc=foo,dc=bar))', ['groupType']);
        $this->setLdapConnection($connection);
        $this->setDn('cn=foo,dc=foo,dc=bar');
        $this->expectedOp = function($operation) {
            return $operation->getFilter() == '(&(objectClass=*))'
                && $operation->getBaseDn() == 'cn=foo,dc=foo,dc=bar';
        };
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
        // A non-security enabled group is represented by a security bit inverse..
        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::SecurityEnabled', 'invert' => true]);
        // A global security type group...
        $this->fromLdap('-2147483646')->shouldBeEqualTo(false);
        // A global group...
        $this->fromLdap(GroupType::GlobalGroup)->shouldBeEqualTo(true);

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::SecurityEnabled', 'invert' => false]);
        // A global security group...
        $this->fromLdap('-2147483646')->shouldBeEqualTo(true);
        // A distribution type group...
        $this->fromLdap('2')->shouldBeEqualTo(false);

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::GlobalGroup']);
        $this->fromLdap('2')->shouldBeEqualTo(true);
        $this->fromLdap('-2147483646')->shouldBeEqualTo(true);
        $this->fromLdap('4')->shouldBeEqualTo(false);

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::DomainLocalGroup']);
        $this->fromLdap('4')->shouldBeEqualTo(true);
        $this->fromLdap('-2147483644')->shouldBeEqualTo(true);
        $this->fromLdap('2')->shouldBeEqualTo(false);

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::UniversalGroup']);
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

    function it_should_aggregate_values_when_converting_a_bool_to_ldap_on_modification($connection)
    {
        $connection->execute(Argument::that($this->expectedOp))->willReturn($this->expectedResult);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->getShouldAggregateValues()->shouldBeEqualTo(true);

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::GlobalGroup']);
        $this->toLdap(true)->shouldBeEqualTo('-2147483646');
        $this->setLastValue('-2147483646');

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::SecurityEnabled', 'invert' => true]);
        $this->toLdap(true)->shouldBeEqualTo('2');
        $this->setLastValue('2');

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::UniversalGroup', 'invert' => false]);
        $this->toLdap(true)->shouldBeEqualTo('8');
        $this->setLastValue('8');

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::SecurityEnabled']);
        $this->toLdap(true)->shouldBeEqualTo('-2147483640');
    }

    function it_should_aggregate_values_when_converting_a_bool_to_ldap_on_creation($connection)
    {
        $connection->execute(Argument::that($this->expectedOp))->willReturn($this->expectedResult);
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->getShouldAggregateValues()->shouldBeEqualTo(true);

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::UniversalGroup', 'invert' => false]);
        $this->toLdap(true)->shouldBeEqualTo('-2147483640');
        $this->setLastValue(['-2147483640']);

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::SecurityEnabled', 'invert' => true]);
        $this->toLdap(true)->shouldBeEqualTo('8');
        $this->setLastValue(['8']);

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::SecurityEnabled', 'invert' => false]);
        $this->toLdap(true)->shouldBeEqualTo('-2147483640');
    }

    function it_should_not_modify_the_value_if_the_bit_is_already_set($connection)
    {
        $result = $this->expectedResult;
        $result[0]['userAccountControl'][0] = ['514'];
        $connection->execute(Argument::that($this->expectedOp))->willReturn($result);
        
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::SecurityEnabled']);
        $this->toLdap(true)->shouldBeEqualTo('-2147483646');
        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::GlobalGroup']);
        $this->toLdap(true)->shouldBeEqualTo('-2147483646');
    }

    function it_should_error_on_modifcation_when_the_existing_LDAP_object_cannot_be_queried($connection)
    {
        $connection->execute(Argument::that($this->expectedOp))->willReturn(['count' => 0]);

        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::SecurityEnabled']);
        $this->shouldThrow(new AttributeConverterException("Unable to find LDAP object: cn=foo,dc=foo,dc=bar"))->duringToLdap(true);
    }

    function it_should_error_when_a_dn_is_not_set_and_a_modification_type_is_requested()
    {
        $this->setDn(null);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setAttribute('typeDistribution');
        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::SecurityEnabled']);
        $this->shouldThrow(new AttributeConverterException('Unable to query for the current "groupType" attribute.'))->duringToLdap(true);
    }

    function it_should_convert_a_bool_value_into_the_bitwise_operator_for_the_returned_value()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::SecurityEnabled']);
        $this->toLdap(true)->toLdapFilter()->shouldEqual('(groupType:1.2.840.113556.1.4.803:=2147483648)');
        $this->toLdap(false)->toLdapFilter()->shouldEqual('(!(groupType:1.2.840.113556.1.4.803:=2147483648))');
        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::SecurityEnabled', 'invert' => true]);
        $this->toLdap(true)->toLdapFilter()->shouldEqual('(!(groupType:1.2.840.113556.1.4.803:=2147483648))');
        $this->toLdap(false)->toLdapFilter()->shouldEqual('(groupType:1.2.840.113556.1.4.803:=2147483648)');
        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::GlobalGroup', 'invert' => false]);
        $this->toLdap(true)->toLdapFilter()->shouldEqual('(groupType:1.2.840.113556.1.4.803:=2)');
        $this->toLdap(false)->toLdapFilter()->shouldEqual('(!(groupType:1.2.840.113556.1.4.803:=2))');
        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::UniversalGroup']);
        $this->toLdap(true)->toLdapFilter()->shouldEqual('(groupType:1.2.840.113556.1.4.803:=8)');
        $this->toLdap(false)->toLdapFilter()->shouldEqual('(!(groupType:1.2.840.113556.1.4.803:=8))');
        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\GroupType::DomainLocalGroup']);
        $this->toLdap(true)->toLdapFilter()->shouldEqual('(groupType:1.2.840.113556.1.4.803:=4)');
        $this->toLdap(false)->toLdapFilter()->shouldEqual('(!(groupType:1.2.840.113556.1.4.803:=4))');
    }
}

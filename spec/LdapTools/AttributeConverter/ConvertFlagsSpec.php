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
use LdapTools\Exception\AttributeConverterException;
use LdapTools\Operation\QueryOperation;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertFlagsSpec extends ObjectBehavior
{
    /**
     * @var QueryOperation
     */
    protected $expectedSearch;

    /**
     * @var array
     */
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
    
    /** 
     * @var callable
     */
    protected $expectedOp;

    function let(LdapConnectionInterface $connection)
    {
        $config = new DomainConfiguration('foo.bar');
        $config->setBaseDn('dc=foo,dc=bar');
        $connection->getConfig()->willReturn($config);
        $this->setOptions([
            'flag_enum' => 'LdapTools\Enums\AD\UserAccountControl::Disabled',
            'default_value' => [
                'LdapTools\Enums\AD\UserAccountControl' => 'NormalAccount',
            ],
            'attribute' => [
                'LdapTools\Enums\AD\UserAccountControl' => 'userAccountControl',
            ],
        ]);
        $this->setLdapConnection($connection);
        $this->setDn('cn=foo,dc=foo,dc=bar');
        
        $this->expectedOp = function($operation) {
            return $operation->getFilter() == '(&(objectClass=*))'
                && $operation->getAttributes() == ['userAccountControl']
                && $operation->getBaseDn() == 'cn=foo,dc=foo,dc=bar';
        };
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertFlags');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_convert_a_value_from_ldap_to_a_php_bool()
    {
        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\UserAccountControl::Disabled']);
        $this->fromLdap('514')->shouldBeEqualTo(true);
        $this->fromLdap('512')->shouldBeEqualTo(false);

        $this->setOptions(['invert' => true]);
        // 514 represents a "normal account" with the disabled bit set.
        $this->fromLdap('514')->shouldBeEqualTo(false);
        $this->fromLdap('513')->shouldBeEqualTo(true);

        // 66050 represents a "normal account" with the password never expires and disabled bits set.
        $this->fromLdap('66050')->shouldBeEqualTo(false);
        $this->setOptions(['invert' => false]);

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\UserAccountControl::PasswordNeverExpires']);
        $this->fromLdap('66050')->shouldBeEqualTo(true);

        // Everything in the map except for 'Password is Reversible'
        $this->fromLdap('328194')->shouldBeEqualTo(true);

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\UserAccountControl::Disabled']);
        $this->fromLdap('328194')->shouldBeEqualTo(true);
        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\UserAccountControl::SmartCardRequired']);
        $this->fromLdap('328194')->shouldBeEqualTo(true);
        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\UserAccountControl::TrustedToAuthForDelegation']);
        $this->fromLdap('328194')->shouldBeEqualTo(false);
        $this->setAttribute(['flag_enum' => 'LdapTools\Enums\AD\UserAccountControl::EncryptedTextPwdAllowed']);
        $this->fromLdap('328194')->shouldBeEqualTo(false);
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

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\UserAccountControl::Disabled']);
        $this->toLdap(true)->shouldBeEqualTo('514');
        $this->setLastValue('514');
        $this->setOptions(['invert' => true]);
        $this->toLdap(true)->shouldBeEqualTo('512');
        $this->setLastValue('512');
        $this->toLdap(false)->shouldBeEqualTo('514');
        $this->setLastValue('514');
        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\UserAccountControl::PasswordNeverExpires', 'invert' => false]);
        $this->toLdap(true)->shouldBeEqualTo('66050');
    }

    function it_should_aggregate_values_when_converting_a_bool_to_ldap_on_creation()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->getShouldAggregateValues()->shouldBeEqualTo(true);

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\UserAccountControl::Disabled', 'invert' => true]);
        $this->toLdap(true)->shouldBeEqualTo('512');
        $this->setOptions(['invert' => false]);
        $this->toLdap(true)->shouldBeEqualTo('514');
        $this->setLastValue('514');
        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\UserAccountControl::PasswordNeverExpires']);
        $this->toLdap(true)->shouldBeEqualTo('66050');
    }

    function it_should_not_modify_the_value_if_the_bit_is_already_set($connection)
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $result = $this->expectedResult;
        $result[0]['userAccountControl'][0] = ['514'];
        $connection->execute(Argument::that($this->expectedOp))->willReturn($result);

        $this->toLdap(true)->shouldBeEqualTo('514');
    }

    function it_should_remove_the_bit_if_requested_and_the_bit_is_already_set($connection)
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $result = $this->expectedResult;
        $result[0]['userAccountControl'][0] = ['514'];
        $connection->execute(Argument::that($this->expectedOp))->willReturn($result);

        $this->toLdap(false)->shouldBeEqualTo('512');
    }

    function it_should_error_on_modifcation_when_the_existing_LDAP_object_cannot_be_queried($connection)
    {
        $connection->execute(Argument::that($this->expectedOp))->willReturn(['count' => 0]);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->shouldThrow(new AttributeConverterException("Unable to find LDAP object: cn=foo,dc=foo,dc=bar"))->duringToLdap(true);
    }

    function it_should_error_when_a_dn_is_not_set_and_a_modification_type_is_requested()
    {
        $this->setDn(null);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->shouldThrow(new AttributeConverterException('Unable to query for the current "userAccountControl" attribute.'))->duringToLdap(true);
    }
    
    function it_should_convert_a_bool_value_into_the_bitwise_operator_for_the_returned_value()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);

        $this->toLdap(true)->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
        $this->toLdap(true)->toLdapFilter()->shouldBeEqualTo('(userAccountControl:1.2.840.113556.1.4.803:=2)');
        $this->toLdap(false)->shouldReturnAnInstanceOf('LdapTools\Query\Operator\bNot');
        $this->toLdap(false)->toLdapFilter()->shouldBeEqualTo('(!(userAccountControl:1.2.840.113556.1.4.803:=2))');

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\UserAccountControl::SmartCardRequired']);
        $this->toLdap(true)->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
        $this->toLdap(true)->toLdapFilter()->shouldBeEqualTo('(userAccountControl:1.2.840.113556.1.4.803:=262144)');
        $this->toLdap(false)->shouldReturnAnInstanceOf('LdapTools\Query\Operator\bNot');
        $this->toLdap(false)->toLdapFilter()->shouldBeEqualTo('(!(userAccountControl:1.2.840.113556.1.4.803:=262144))');

        $this->setOptions(['flag_enum' => 'LdapTools\Enums\AD\UserAccountControl::Disabled', 'invert' => true]);
        $this->toLdap(true)->shouldReturnAnInstanceOf('LdapTools\Query\Operator\bNot');
        $this->toLdap(true)->toLdapFilter()->shouldBeEqualTo('(!(userAccountControl:1.2.840.113556.1.4.803:=2))');
        $this->toLdap(false)->shouldReturnAnInstanceOf('LdapTools\Query\Operator\MatchingRule');
        $this->toLdap(false)->toLdapFilter()->shouldBeEqualTo('(userAccountControl:1.2.840.113556.1.4.803:=2)');
    }
}

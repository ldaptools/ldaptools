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
use LdapTools\Operation\QueryOperation;
use LdapTools\Schema\LdapObjectSchema;
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
     * @var LdapObjectSchema
     */
    protected $schema;

    function let(LdapConnectionInterface $connection, AddOperation $operation)
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap([
            'username' => 'sAMAccountName',
            'emailAddress' => 'mail',
            'disabled' => 'userAccountControl',
            'passwordMustChange' => 'pwdLastSet',
            'passwordNeverExpires' => 'userAccountControl',
            'trustedForAllDelegation' => 'userAccountControl',
            'mrmEnabled' => 'msExchELCMailboxFlags',
            'retentionHoldEnabled' => 'msExchELCMailboxFlags',
            'litigationHoldEnabled' => 'msExchELCMailboxFlags',
            'groups' => 'memberOf',
        ]);
        $schema->setConverterMap([
            'disabled' => 'flags',
            'passwordMustChange' => 'password_must_change',
            'trustedForAllDelegation' => 'flags',
            'passwordNeverExpires' => 'flags',
            'litigationHoldEnabled' => 'flags',
            'retentionHoldEnabled' => 'flags',
            'mrmEnabled' => 'flags',
            'groups' => 'group_membership',
        ]);
        $schema->setConverterOptions([
            'flags' => [
                'userAccountControl' => [
                    'flagMap' => [
                        'disabled' => '2',
                        'passwordNeverExpires' => '65536',
                        'smartCardRequired' => '262144',
                        'trustedForAllDelegation' => '524288',
                        'passwordIsReversible' => '128',
                    ],
                    'defaultValue' => '512',
                    'attribute' => 'userAccountControl',
                    'invert' => ['enabled']
                ],
                'msExchELCMailboxFlags' => [
                    'attribute' => 'msExchELCMailboxFlags',
                    'defaultValue' => '0',
                    'flagMap' => [
                        'retentionHoldEnabled' => '1',
                        'mrmEnabled' => '2',
                        'calendarLoggingDisabled' => '4',
                        'calendarLoggingEnabled' => '4',
                        'litigationHoldEnabled' => '8',
                        'singleItemRecoveryEnabled' => '16',
                        'isArchiveDatabaseValid' => '32',
                    ],
                    'invert' => [ 'calendarLoggingEnabled' ],
                ],
            ],
            'group_membership' => [
                'groups' => [
                    'to_attribute' => 'member',
                    'from_attribute' => 'memberOf',
                    'attribute' => 'sAMAccountName',
                    'filter' => [
                        'objectClass' => 'group',
                    ],
                ],
            ],
        ]);
        $this->schema = $schema;
        $connection->getConfig()->willReturn(new DomainConfiguration('foo.bar'));
        $this->beConstructedThrough('getInstance', [$schema, $this->entryTo, AttributeConverterInterface::TYPE_CREATE]);
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

    function it_should_aggregate_properly_on_modification($connection, $operation)
    {
        $entry = $this->entryTo;
        $entry['disabled'] = true;
        $entry['passwordNeverExpires'] = true;
        $entry['trustedForAllDelegation'] = true;
        $entry['mrmEnabled'] = true;
        $entry['litigationHoldEnabled'] = true;
        unset($entry['groups']);

        $connection->execute(new QueryOperation('(&(distinguishedName=\63\6e\3d\66\6f\6f\2c\64\63\3d\66\6f\6f\2c\64\63\3d\62\61\72)))', ['userAccountControl']))->willReturn($this->expectedResult);
        $this->beConstructedWith($this->schema, $entry, AttributeConverterInterface::TYPE_CREATE);
        $this->setLdapConnection($connection);
        $this->setOperation($operation);
        $this->setDn('cn=foo,dc=foo,dc=bar');

        $this->toLdap()->shouldHaveKeyWithValue('userAccountControl','590338');
        $this->toLdap()->shouldHaveKeyWithValue('msExchELCMailboxFlags','10');
        $this->toLdap()->shouldHaveKeyWithValue('username','chad');
        $this->toLdap()->shouldHaveKeyWithValue('emailAddress','Chad.Sikorra@gmail.com');
        $this->toLdap()->shouldHaveKeyWithValue('passwordMustChange','0');
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
    
    public function getMatchers()
    {
        return [
            'haveKeyWithValue' => function ($subject, $key, $value) {
                return isset($subject[$key]) && ($subject[$key] === $value);
            }
        ];
    }
}

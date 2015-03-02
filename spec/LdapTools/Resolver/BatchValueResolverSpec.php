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
use LdapTools\Object\LdapObject;
use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class BatchValueResolverSpec extends ObjectBehavior
{
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

    /**
     * @var LdapObjectSchema
     */
    protected $schema;

    /**
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @var array
     */
    protected $ldapObjectOpts = [['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user'];

    function let(LdapConnectionInterface $connection)
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap([
            'username' => 'sAMAccountName',
            'emailAddress' => 'mail',
            'disabled' => 'userAccountControl',
            'passwordMustChange' => 'pwdLastSet',
            'passwordNeverExpires' => 'userAccountControl',
            'trustedForAllDelegation' => 'userAccountControl',
        ]);
        $schema->setConverterMap([
            'username' => 'string_to_utf8',
            'emailAddress' => 'string_to_utf8',
            'disabled' => 'user_account_control',
            'passwordMustChange' => 'password_must_change',
            'trustedForAllDelegation' => 'user_account_control',
            'passwordNeverExpires' => 'user_account_control',
        ]);
        $schema->setConverterOptions([
            'user_account_control' => [
                'uacMap' => [
                    'disabled' => '2',
                    'passwordNeverExpires' => '65536',
                    'smartCardRequired' => '262144',
                    'trustedForAllDelegation' => '524288',
                    'passwordIsReversible' => '128',
                ],
                'defaultValue' => '512',
            ]
        ]);
        $this->schema = $schema;
        $this->connection = $connection;
    }

    function it_is_initializable()
    {
        $this->beConstructedWith($this->schema, (new LdapObject(...$this->ldapObjectOpts))->getBatchCollection(), AttributeConverterInterface::TYPE_MODIFY);
        $this->shouldHaveType('LdapTools\Resolver\BatchValueResolver');
    }

    function it_should_convert_values_to_ldap_with_a_batch_modification()
    {
        $ldapObject = new LdapObject(...$this->ldapObjectOpts);
        $ldapObject->set('disabled', true);
        $ldapObject->set('trustedForAllDelegation', true);
        $ldapObject->set('username', 'foo');
        $ldapObject->add('emailAddress', 'chad.sikorra@gmail.com');
        $ldapObject->remove('phoneNumber','555-5555');
        $ldapObject->reset('pager');

        $uacBatch = [
            'attrib' => 'userAccountControl',
            'modtype' => LDAP_MODIFY_BATCH_REPLACE,
            'values' => ["524802"]
        ];
        $usernameBatch = [
            'attrib' => 'username',
            'modtype' => LDAP_MODIFY_BATCH_REPLACE,
            'values' => ["foo"]
        ];
        $emailBatch = [
            'attrib' => 'emailAddress',
            'modtype' => LDAP_MODIFY_BATCH_ADD,
            'values' => ["chad.sikorra@gmail.com"]
        ];
        $phoneBatch = [
            'attrib' => 'phoneNumber',
            'modtype' => LDAP_MODIFY_BATCH_REMOVE,
            'values' => ["555-5555"]
        ];
        $pagerBatch = [
            'attrib' => 'pager',
            'modtype' => LDAP_MODIFY_BATCH_REMOVE_ALL,
        ];
        $batch = $ldapObject->getBatchCollection()->getBatchArray();
        $this->connection->search(...$this->expectedSearch)->willReturn($this->expectedResult);
        $this->connection->getLdapType()->willReturn('ad');
        $this->beConstructedWith($this->schema, $ldapObject->getBatchCollection(), AttributeConverterInterface::TYPE_MODIFY);
        $this->setLdapConnection($this->connection);
        $this->setDn('cn=foo,dc=foo,dc=bar');
        $this->toLdap()->getBatchArray()->shouldHaveCount(5);
        $this->toLdap()->getBatchArray()->shouldContain($uacBatch);
        $this->toLdap()->getBatchArray()->shouldContain($usernameBatch);
        $this->toLdap()->getBatchArray()->shouldContain($emailBatch);
        $this->toLdap()->getBatchArray()->shouldContain($phoneBatch);
        $this->toLdap()->getBatchArray()->shouldContain($pagerBatch);
    }

    public function it_should_error_trying_to_do_a_non_set_method_on_a_single_aggregated_value()
    {
        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $ldapObject->remove('disabled', true);

        $batch = $ldapObject->getBatchCollection();
        $this->connection->search(...$this->expectedSearch)->willReturn($this->expectedResult);
        $this->connection->getLdapType()->willReturn('ad');
        $this->beConstructedWith($this->schema, $batch, AttributeConverterInterface::TYPE_MODIFY);
        $this->setLdapConnection($this->connection);
        $this->setDn('cn=foo,dc=foo,dc=bar');
        $this->shouldThrow(new \LogicException('Unable to modify "disabled". You can only use the "set" method to modify this attribute.'))
            ->duringToLdap();
    }

    public function it_should_error_trying_to_do_a_non_set_method_on_many_aggregated_values()
    {
        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $ldapObject->set('disabled', true);
        $ldapObject->add('trustedForAllDelegation', true);

        $batch = $ldapObject->getBatchCollection();
        $this->connection->search(...$this->expectedSearch)->willReturn($this->expectedResult);
        $this->connection->getLdapType()->willReturn('ad');
        $this->beConstructedWith($this->schema, $batch, AttributeConverterInterface::TYPE_MODIFY);
        $this->setLdapConnection($this->connection);
        $this->setDn('cn=foo,dc=foo,dc=bar');
        $this->shouldThrow(new \LogicException('Unable to modify "trustedForAllDelegation". You can only use the "set" method to modify this attribute.'))
            ->duringToLdap();
    }
}

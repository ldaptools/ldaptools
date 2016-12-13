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
use LdapTools\BatchModify\Batch;
use LdapTools\BatchModify\BatchCollection;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\DomainConfiguration;
use LdapTools\Exception\LogicException;
use LdapTools\Object\LdapObject;
use LdapTools\Operation\BatchModifyOperation;
use LdapTools\Operation\QueryOperation;
use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class BatchValueResolverSpec extends ObjectBehavior
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
                    'attribute' => 'userAccountControl',
                    'invert' => ['enabled'],
                    'defaultValue' => '512',
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
        $this->expectedSearch = new QueryOperation('(&(distinguishedName=cn=foo,dc=foo,dc=bar))', ['userAccountControl']);
        $this->schema = $schema;
        $connection->getConfig()->willReturn(new DomainConfiguration('foo.bar'));
        $connection->getRootDse()->willReturn(new LdapObject(['foo' => 'bar']));
    }

    function it_is_initializable()
    {
        $this->beConstructedThrough('getInstance', [$this->schema, (new LdapObject(...$this->ldapObjectOpts))->getBatchCollection(), AttributeConverterInterface::TYPE_MODIFY]);
        $this->shouldHaveType('LdapTools\Resolver\BatchValueResolver');
    }

    function it_should_convert_values_to_ldap_with_a_batch_modification($connection)
    {
        $ldapObject = new LdapObject(...$this->ldapObjectOpts);
        $ldapObject->set('disabled', true);
        $ldapObject->set('trustedForAllDelegation', true);
        $ldapObject->set('litigationHoldEnabled', true);
        $ldapObject->set('retentionHoldEnabled', true);
        $ldapObject->set('username', 'foo');
        $ldapObject->add('emailAddress', 'chad.sikorra@gmail.com');
        $ldapObject->remove('phoneNumber','555-5555');
        $ldapObject->reset('pager');

        $uacBatch = [
            'attrib' => 'userAccountControl',
            'modtype' => LDAP_MODIFY_BATCH_REPLACE,
            'values' => ["524802"]
        ];
        $elcBatch = [
            'attrib' => 'msExchELCMailboxFlags',
            'modtype' => LDAP_MODIFY_BATCH_REPLACE,
            'values' => ["11"]
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

        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(objectClass=*))'
                && $operation->getBaseDn() == 'cn=foo,dc=foo,dc=bar'
                && $operation->getAttributes() == ['userAccountControl'];
        }))->willReturn($this->expectedResult);

        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(objectClass=*))'
                && $operation->getBaseDn() == 'cn=foo,dc=foo,dc=bar'
                && $operation->getAttributes() == ['msExchELCMailboxFlags'];
        }))->willReturn($this->expectedElcResult);

        $this->beConstructedWith($this->schema, $ldapObject->getBatchCollection(), AttributeConverterInterface::TYPE_MODIFY);
        $this->setLdapConnection($connection);
        $this->setDn('cn=foo,dc=foo,dc=bar');
        $this->toLdap()->getBatchArray()->shouldHaveCount(6);
        $this->toLdap()->getBatchArray()->shouldContain($uacBatch);
        $this->toLdap()->getBatchArray()->shouldContain($usernameBatch);
        $this->toLdap()->getBatchArray()->shouldContain($emailBatch);
        $this->toLdap()->getBatchArray()->shouldContain($phoneBatch);
        $this->toLdap()->getBatchArray()->shouldContain($pagerBatch);
        $this->toLdap()->getBatchArray()->shouldContain($elcBatch);
    }

    public function it_should_error_trying_to_do_a_non_set_method_on_a_single_aggregated_value($connection)
    {
        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $ldapObject->remove('disabled', true);

        $batch = $ldapObject->getBatchCollection();
        $connection->execute($this->expectedSearch)->willReturn($this->expectedResult);
        $this->beConstructedWith($this->schema, $batch, AttributeConverterInterface::TYPE_MODIFY);
        $this->setLdapConnection($connection);
        $this->setDn('cn=foo,dc=foo,dc=bar');
        $this->shouldThrow(new LogicException('Unable to modify "disabled". The "REMOVE" action is not allowed.'))
            ->duringToLdap();
    }

    public function it_should_error_trying_to_do_a_non_set_method_on_many_aggregated_values($connection)
    {
        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $ldapObject->set('disabled', true);
        $ldapObject->add('trustedForAllDelegation', true);

        $batch = $ldapObject->getBatchCollection();
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(objectClass=*))'
                && $operation->getBaseDn() == 'cn=foo,dc=foo,dc=bar'
                && $operation->getAttributes() == ['userAccountControl'];
        }))->willReturn($this->expectedResult);
        $this->beConstructedWith($this->schema, $batch, AttributeConverterInterface::TYPE_MODIFY);
        $this->setLdapConnection($connection);
        $this->setDn('cn=foo,dc=foo,dc=bar');
        $this->shouldThrow(new \LogicException('Unable to modify "trustedForAllDelegation". The "ADD" action is not allowed.'))->duringToLdap();
    }
    
    function it_should_remove_batches_when_specified_by_a_converter_implementing_OperationGeneratorInterface($connection)
    {
        $dn = 'cn=Chad,dc=foo,dc=bar';
        $batch = new BatchCollection($dn);
        $operation = new BatchModifyOperation($dn);
        $batch->add(new Batch(Batch::TYPE['ADD'], 'groups', ['cn=foo,dc=example,dc=local', 'cn=bar,dc=foo,dc=bar']));
        $batch->add(new Batch(Batch::TYPE['REMOVE'], 'groups', ['cn=foo,dc=example,dc=local', 'cn=foobar,dc=foo,dc=bar']));
        
        $this->beConstructedWith($this->schema, $batch, AttributeConverterInterface::TYPE_MODIFY);
        
        $this->setLdapConnection($connection);
        $this->setDn($dn);
        $this->setOperation($operation);
        
        $this->toLdap()->toArray()->shouldHaveCount(0);
    }
}

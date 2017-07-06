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
use LdapTools\BatchModify\Batch;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\DomainConfiguration;
use LdapTools\Object\LdapObject;
use LdapTools\Operation\AddOperation;
use LdapTools\Operation\BatchModifyOperation;
use LdapTools\Operation\LdapOperationInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertGroupMembershipSpec extends ObjectBehavior
{
    protected $expectedResult = [
        'count' => 1,
        0 => [
            "memberOf" => [
                "count" => 3,
                0 => "CN=Foo,DC=bar,DC=foo",
                1 => "CN=Bar,DC=bar,DC=foo",
                2 => "CN=FooBar,DC=bar,DC=foo",
            ],
            0 => "memberOf",
            'count' => 1,
            'dn' => "CN=Chad,OU=Employees,DC=example,DC=com",
        ],
    ];
    
    protected $entry = [
        'count' => 1,
        0 => [
            "distinguishedname" => [
                "count" => 1,
                0 => "CN=Foo,DC=bar,DC=foo",
            ],
            0 => "distinguishedName",
            'count' => 2,
            'dn' => "CN=Foo,DC=bar,DC=foo",
        ],
    ];

    protected $entrySid = [
        'count' => 1,
        0 => [
            "distinguishedname" => [
                "count" => 1,
                0 => "CN=FooBar,DC=bar,DC=foo",
            ],
            0 => "distinguishedName",
            'count' => 2,
            'dn' => "CN=FooBar,DC=bar,DC=foo",
        ],
    ];

    protected $entryGuid = [
        'count' => 1,
        0 => [
            "distinguishedname" => [
                "count" => 1,
                0 => "CN=Bar,DC=bar,DC=foo",
            ],
            0 => "distinguishedName",
            'count' => 2,
            'dn' => "CN=Bar,DC=bar,DC=foo",
        ],
    ];

    function let(LdapConnectionInterface $connection)
    {
        $connection->getConfig()->willReturn(new DomainConfiguration('bar.foo'));
        $this->setLdapConnection($connection);
        $this->setOptions([ 'filter' => ['objectClass' => 'bar']]);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertGroupMembership');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_implement_operation_generator_interface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\OperationGeneratorInterface');
    }
    
    function it_should_act_as_a_multivalued_converter()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->isMultivaluedConverter()->shouldBeEqualTo(true);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->isMultivaluedConverter()->shouldBeEqualTo(true);
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_FROM);
        $this->isMultivaluedConverter()->shouldBeEqualTo(false);
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->isMultivaluedConverter()->shouldBeEqualTo(false);
    }

    function it_should_only_specify_the_original_attribute_to_be_removed_on_modification_or_creation()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->getRemoveOriginalValue()->shouldBeEqualTo(true);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->getRemoveOriginalValue()->shouldBeEqualTo(true);
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_FROM);
        $this->getRemoveOriginalValue()->shouldBeEqualTo(false);
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->getRemoveOriginalValue()->shouldBeEqualTo(false);
    }
    
    function it_should_convert_a_dn_to_a_normal_name()
    {
        $this->fromLdap('cn=Foo,dc=bar,dc=foo')->shouldBeEqualTo('Foo');
    }

    function it_should_convert_a_GUID_back_to_a_dn($connection)
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $guid = 'a1131cd3-902b-44c6-b49a-1f6a567cda25';
        $guidHex = '\d3\1c\13\a1\2b\90\c6\44\b4\9a\1f\6a\56\7c\da\25';

        $connection->execute(Argument::that(function($operation) use ($guidHex, $guid) {
            return $operation->getFilter() == '(&(&(objectClass=bar))(|(objectGuid='.$guidHex.')(sAMAccountName='.$guid.')))';
        }))->willReturn($this->entry);

        $this->toLdap($guid)->shouldBeEqualTo($this->entry[0]['distinguishedname'][0]);
    }

    function it_should_convert_a_SID_back_to_a_dn($connection)
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $sid = 'S-1-5-21-1004336348-1177238915-682003330-512';
        $sidHex = '\01\05\00\00\00\00\00\05\15\00\00\00\dc\f4\dc\3b\83\3d\2b\46\82\8b\a6\28\00\02\00\00';

        $connection->execute(Argument::that(function($operation) use ($sid, $sidHex) {
            return $operation->getFilter() == '(&(&(objectClass=bar))(|(objectSid='.$sidHex.')(sAMAccountName='.$sid.')))';
        }))->willReturn($this->entry);

        $this->toLdap($sid)->shouldBeEqualTo($this->entry[0]['distinguishedname'][0]);
    }

    function it_should_convert_a_LdapObject_back_to_a_dn()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);

        $this->toLdap(new LdapObject(['dn' => 'CN=Chad,OU=Employees,DC=example,DC=com'], 'user'))->shouldBeEqualTo('CN=Chad,OU=Employees,DC=example,DC=com');
    }

    function it_should_error_if_a_LdapObject_is_missing_a_DN()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);

        $this->shouldThrow('\LdapTools\Exception\AttributeConverterException')->duringToLdap(new LdapObject(['cn' => 'foo'], 'user'));
    }

    function it_should_convert_a_dn_back_to_a_dn($connection)
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $connection->execute(Argument::any())->shouldNotBeCalled();

        $this->toLdap("CN=Chad,OU=Employees,DC=example,DC=com")->shouldBeEqualTo("CN=Chad,OU=Employees,DC=example,DC=com");
    }

    function it_should_convert_a_dn_into_its_common_name()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_FROM);

        $this->fromLdap('cn=Foo\,\=bar,dc=foo,dc=bar')->shouldBeEqualTo('Foo,=bar');
    }

    function it_should_display_the_dn_from_ldap_if_specified()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_FROM);
        $this->setOptions([ 'display_dn' => true]);

        $this->fromLdap('cn=Foo,dc=bar,dc=foo')->shouldBeEqualTo('cn=Foo,dc=bar,dc=foo');
    }

    function it_should_allow_an_or_filter_for_an_attribute($connection)
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);

        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(|(objectClass=bar)(objectClass=foo))(sAMAccountName=Foo))';
        }))->willReturn($this->entry);
        $this->setOptions(['or_filter' => true, 'filter' => ['objectClass' => ['bar', 'foo']]]);

        $this->toLdap('Foo')->shouldBeEqualTo($this->entry[0]['distinguishedname'][0]);
    }

    function it_should_generate_add_operations_based_off_a_name_guid_sid_and_LdapObject_on_a_create_operation($connection, AddOperation $operation)
    {
        $sid = 'S-1-5-21-1004336348-1177238915-682003330-512';
        $sidHex = '\01\05\00\00\00\00\00\05\15\00\00\00\dc\f4\dc\3b\83\3d\2b\46\82\8b\a6\28\00\02\00\00';
        $guid = 'a1131cd3-902b-44c6-b49a-1f6a567cda25';
        $guidHex = '\d3\1c\13\a1\2b\90\c6\44\b4\9a\1f\6a\56\7c\da\25';
        $dn = 'cn=foo,dc=example,dc=local';
        $objectDn = 'CN=SomeGroup,OU=Employees,DC=example,DC=com';
        $ldapObject = new LdapObject(['dn' => $objectDn], 'group');

        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(&(objectClass=bar))(sAMAccountName=Foo))';
        }))->willReturn($this->entry);
        $connection->execute(Argument::that(function($operation) use ($guid, $guidHex) {
            return $operation->getFilter() == '(&(&(objectClass=bar))(|(objectGuid='.$guidHex.')(sAMAccountName='.$guid.')))';
        }))->willReturn($this->entryGuid);
        $connection->execute(Argument::that(function($operation) use ($sid, $sidHex) {
            return $operation->getFilter() == '(&(&(objectClass=bar))(|(objectSid='.$sidHex.')(sAMAccountName='.$sid.')))';
        }))->willReturn($this->entrySid);

        $this->setOperation($operation);
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->setDn($dn);
        $operation->getDn()->willReturn($dn);
        
        $nameDn = $this->entry[0]['distinguishedname'][0];
        $guidDn = $this->entryGuid[0]['distinguishedname'][0];
        $sidDn = $this->entrySid[0]['distinguishedname'][0];
        
        foreach ([$nameDn, $sidDn, $guidDn, $objectDn] as $groupDn) {
            $operation->addPostOperation(Argument::that(function($op) use ($dn, $groupDn) {
                return $op instanceof BatchModifyOperation
                    && call_user_func($op->getBatchCollection()->toArray()[0]->getValues()[0]) == $dn
                    && $op->getBatchCollection()->getDn() == $groupDn;
            }))->shouldBeCalled();            
        }
        
        $this->toLdap(['Foo', $guid, $sid, $ldapObject])->shouldBeArray();
    }

    /**
     * This is quite the mess. Not sure how to better spec this.
     */
    function it_should_generate_add_and_remove_operations_on_a_modify_operation($connection, BatchModifyOperation $operation)
    {
        $sid = 'S-1-5-21-1004336348-1177238915-682003330-512';
        $sidHex = '\01\05\00\00\00\00\00\05\15\00\00\00\dc\f4\dc\3b\83\3d\2b\46\82\8b\a6\28\00\02\00\00';
        $guid = 'a1131cd3-902b-44c6-b49a-1f6a567cda25';
        $guidHex = '\d3\1c\13\a1\2b\90\c6\44\b4\9a\1f\6a\56\7c\da\25';
        $dn = 'cn=foo,dc=example,dc=local';
        $objectDn = 'CN=SomeGroup,OU=Employees,DC=example,DC=com';
        $ldapObject = new LdapObject(['dn' => $objectDn], 'group');

        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(&(objectClass=bar))(sAMAccountName=Foo))';
        }))->willReturn($this->entry);
        $connection->execute(Argument::that(function($operation) use ($guid, $guidHex) {
            return $operation->getFilter() == '(&(&(objectClass=bar))(|(objectGuid='.$guidHex.')(sAMAccountName='.$guid.')))';
        }))->willReturn($this->entryGuid);
        $connection->execute(Argument::that(function($operation) use ($sid, $sidHex) {
            return $operation->getFilter() == '(&(&(objectClass=bar))(|(objectSid='.$sidHex.')(sAMAccountName='.$sid.')))';
        }))->willReturn($this->entrySid);

        $this->setOperation($operation);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setDn($dn);

        $nameDn = $this->entry[0]['distinguishedname'][0];
        $guidDn = $this->entryGuid[0]['distinguishedname'][0];
        $sidDn = $this->entrySid[0]['distinguishedname'][0];

        $batchAdd1 = new Batch(Batch::TYPE['ADD'], 'member', 'Foo');
        $batchAdd2 = new Batch(Batch::TYPE['REMOVE'], 'member', $sid);
        $batchRemove = new Batch(Batch::TYPE['ADD'], 'member', [$guid, $ldapObject]);

        // Expected actions for the add batch...
        $this->setBatch($batchAdd1);
        $operation->addPostOperation(Argument::that(function ($op) use ($batchAdd1, $nameDn, $dn) {
            $batches = [new Batch(Batch::TYPE['ADD'], 'member', [$dn])];

            return $op instanceof BatchModifyOperation
                && $op->getBatchCollection()->toArray() == $batches
                && $op->getBatchCollection()->getDn() == $nameDn;
        }))->shouldBeCalled();
        $this->toLdap(['Foo'])->shouldBeArray();

        // Expected actions for the remove batch...
        $this->setBatch($batchRemove);
        $operation->addPostOperation(Argument::that(function ($op) use ($batchRemove, $sidDn, $dn) {
            $batches = [new Batch($batchRemove->getModType(), 'member', [$dn])];

            return $op instanceof BatchModifyOperation
            && $op->getBatchCollection()->toArray() == $batches
            && $op->getBatchCollection()->getDn() == $sidDn;
        }))->shouldBeCalled();
        $this->toLdap([$sid])->shouldBeArray();

        // Expected actions for the multi-add batch...
        $this->setBatch($batchAdd2);
        foreach ([$guidDn, $objectDn] as $value) {
            $operation->addPostOperation(Argument::that(function ($op) use ($batchAdd2, $value, $dn) {
                $batches = [new Batch($batchAdd2->getModType(), 'member', [$dn])];

                return $op instanceof BatchModifyOperation
                    && $op->getBatchCollection()->toArray() == $batches
                    && $op->getBatchCollection()->getDn() == $value;
            }))->shouldBeCalled();
        }
        $this->toLdap([$guid, $ldapObject])->shouldBeArray();
    }

    function it_should_generate_operations_to_remove_all_current_groups_on_a_modify_reset_operation($connection, LdapOperationInterface $operation)
    {
        $batch = new Batch(Batch::TYPE['REMOVE_ALL'], 'groups');
        $dn = 'cn=foo,dc=foo,dc=bar';
        $this->setOperation($operation);

        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setDn($dn);
        $this->setBatch($batch);

        $connection->execute(Argument::that(function($operation) use ($dn) {
            return $operation->getFilter() == "(&(objectClass=*))"
                && $operation->getBaseDn() == $dn
                && $operation->getAttributes() == ['memberOf'];
        }))->willReturn($this->expectedResult);
        foreach ($this->expectedResult[0]['memberOf'] as $groupDn) {
            if (!is_string($groupDn)) {
                continue;
            }
            $operation->addPostOperation(Argument::that(function($op) use ($dn, $groupDn) {
                $batches = [new Batch(Batch::TYPE['REMOVE'], 'member', [$dn])];

                return $op instanceof BatchModifyOperation
                    && $op->getBatchCollection()->toArray() == $batches
                    && $op->getBatchCollection()->getDn() == $groupDn;
            }))->shouldBeCalled();
        }
        
        $this->toLdap([null]);
    }

    function it_should_generate_operations_to_remove_all_current_groups_and_add_new_ones_on_a_modify_set_operation($connection, LdapOperationInterface $operation)
    {
        $group1 = 'cn=foo,dc=example,dc=local';
        $group2 = 'cn=bar,dc=example,dc=local';
        $batch = new Batch(Batch::TYPE['REPLACE'], 'groups', [$group1, $group2]);
        
        $dn = 'cn=foo,dc=foo,dc=bar';

        $this->setOperation($operation);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setDn($dn);
        $this->setBatch($batch);

        $connection->execute(Argument::any())->shouldBeCalled()->willReturn($this->expectedResult);
        foreach ($this->expectedResult[0]['memberOf'] as $groupDn) {
            if (!is_string($groupDn)) {
                continue;
            }
            $operation->addPostOperation(Argument::that(function($op) use ($dn, $groupDn) {
                $batches = [new Batch(Batch::TYPE['REMOVE'], 'member', [$dn])];

                return $op instanceof BatchModifyOperation
                    && $op->getBatchCollection()->toArray() == $batches
                    && $op->getBatchCollection()->getDn() == $groupDn;
            }))->shouldBeCalled();
        }
        foreach ([$group1, $group2] as $groupDn) {
            $operation->addPostOperation(Argument::that(function($op) use ($dn, $groupDn) {
                $batches = [new Batch(Batch::TYPE['ADD'], 'member', [$dn])];

                return $op instanceof BatchModifyOperation
                    && $op->getBatchCollection()->toArray() == $batches
                    && $op->getBatchCollection()->getDn() == $groupDn;
            }))->shouldBeCalled();
        }

        $this->toLdap([$group1, $group2]);
    }
}

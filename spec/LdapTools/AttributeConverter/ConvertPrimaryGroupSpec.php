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
use LdapTools\DomainConfiguration;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertPrimaryGroupSpec extends ObjectBehavior
{
    protected $dn = 'cn=foo,dc=foo,dc=bar';
    protected $groupSidHex = '\01\05\00\00\00\00\00\05\15\00\00\00\dc\f4\dc\3b\83\3d\2b\46\82\8b\a6\28\01\02\00\00';
    protected $userSidHex = '\01\05\00\00\00\00\00\05\15\00\00\00\dc\f4\dc\3b\83\3d\2b\46\82\8b\a6\28\c7\04\00\00';
    protected $groupSid = 'S-1-5-21-1004336348-1177238915-682003330-513';
    protected $userSid = 'S-1-5-21-1004336348-1177238915-682003330-1223';

    function let(\LdapTools\Connection\LdapConnectionInterface $connection)
    {
        $connection->getConfig()->willReturn(new DomainConfiguration('example.local'));
        $this->setLdapConnection($connection);
        $this->setDn('cn=foo,dc=foo,dc=bar');
        $dn = $this->dn;
        $groupSidHex = $this->groupSidHex;
        
        $connection->execute(Argument::that(function($operation) use ($dn) {
            return $operation->getFilter() == '(&(objectClass=*))'
                && $operation->getAttributes() == ['objectSid']
                && $operation->getBaseDn() == $dn;
        }))->willReturn([
            'count' => 1, [
                "objectsid" => [
                    "count" => 1,
                    0 =>  pack('H*', str_replace('\\', '', $this->userSidHex)),
                ],
                0 => "objectsid",
                'count' => 1,
                'dn' => $this->dn,
            ]
        ]);
        $connection->execute(Argument::that(function($operation) use ($groupSidHex) {
            return $operation->getFilter() == '(&(objectSid='.$groupSidHex.'))'
                && $operation->getAttributes() == ['cn'];
        }))->willReturn([
            'count' => 1, [
                "cn" => [
                    "count" => 1,
                    0 =>  'Domain Users',
                ],
                0 => "cn",
                "count" => 1,
                "dn" => "CN=Domain Users,CN=Users,dc=example,dc=local",
            ]
        ]);
        $connection->execute(Argument::that(function($operation) use ($dn) {
            return $operation->getFilter() == '(&(objectClass=group)(cn=Domain Users)(member='.$dn.')(groupType:1.2.840.113556.1.4.803:=2147483648))'
                && $operation->getAttributes() == ['objectSid'];
        }))->willReturn([
            'count' => 1, [
                "objectSid" => [
                    "count" => 1,
                    0 =>  pack('H*', str_replace('\\', '', $this->groupSidHex)),
                ],
                0 => "objectSid",
                "count" => 1,
                "dn" => "CN=Domain Users,CN=Users,dc=example,dc=local",
            ]
        ]);
        $connection->execute(Argument::that(function($operation) use ($dn) {
            return $operation->getFilter() == '(&(objectClass=group)(cn=Domain Users)(member=foo)(groupType:1.2.840.113556.1.4.803:=2147483648))'
                && $operation->getAttributes() == ['objectSid'];
        }))->willReturn([ 'count' => 0]);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertPrimaryGroup');
    }

    function it_should_return_a_group_name_from_ldap()
    {
        $this->fromLdap('513')->shouldBeEqualTo('Domain Users');
    }

    function it_should_return_the_RID_to_LDAP_when_given_the_group_name_on_modification()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->toLdap('Domain Users')->shouldBeEqualTo('513');
    }

    function it_should_throw_an_error_when_the_user_is_not_a_member_of_the_group_or_the_group_cannot_be_found_on_modification()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setDn('foo');
        $this->shouldThrow('\LdapTools\Exception\AttributeConverterException')->duringToLdap('Domain Users');
    }

    function it_should_not_validate_group_membership_when_going_to_ldap_if_the_op_type_is_not_modification($connection)
    {
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(objectClass=group)(cn=Domain Users)(groupType:1.2.840.113556.1.4.803:=2147483648))'
                && $operation->getAttributes() == ['objectSid'];
        }))->willReturn([
            'count' => 1, [
                "objectSid" => [
                    "count" => 1,
                    0 =>  pack('H*', str_replace('\\', '', $this->groupSidHex)),
                ],
                0 => "objectSid",
                "count" => 1,
                "dn" => "CN=Domain Users,CN=Users,dc=example,dc=local",
            ]
        ]);
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->toLdap('Domain Users')->shouldBeEqualTo('513');
    }
}

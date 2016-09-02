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

use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;

class AttributeNameResolverSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Resolver\AttributeNameResolver');
    }

    function it_should_retain_the_selected_attribute_case_when_there_is_no_schema_being_used_from_ldap()
    {
        $entry = ['givenname' => 'Chad', 'sn' => 'Sikorra'];
        $selected = ['GivenNamE', 'SN'];

        $this->fromLdap($entry, $selected)->shouldHaveKey('GivenNamE');
        $this->fromLdap($entry, $selected)->shouldHaveKey('SN');
    }

    function it_should_retain_the_selected_attribute_case_when_there_is_a_schema_from_ldap()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap([ 'firstName' => 'givenName', 'lastName' => 'sn' ]);

        $entry = ['givenname' => 'Chad', 'sn' => 'Sikorra'];
        $selected = ['FirstName', 'LastName'];

        $this->beConstructedWith($schema);
        $this->fromLdap($entry, $selected)->shouldHaveKey('FirstName');
        $this->fromLdap($entry, $selected)->shouldHaveKey('LastName');
    }

    function it_should_rename_schema_names_to_ldap_attribute_names_going_to_ldap()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap([
            'firstName' => 'givenName',
            'lastName' => 'sn',
            'emailAddress' => 'mail',
            'name' => 'cn'
        ]);
        $objectToLdap = [
            'firstName' => 'Egon',
            'lastName' => 'Spengler',
            'mail' => 'espengler@whhhhhy.local',
            'cn' => 'Egon',
        ];

        $this->beConstructedWith($schema);
        $this->toLdap($objectToLdap)->shouldHaveKey('givenName');
        $this->toLdap($objectToLdap)->shouldHaveKey('sn');
        $this->toLdap($objectToLdap)->shouldHaveKey('mail');
        $this->toLdap($objectToLdap)->shouldHaveKey('cn');
    }

    function it_should_return_all_LDAP_attributes_merged_with_the_schema_if_a_wildcard_was_used()
    {
        $map = [
            'firstName' => 'givenName',
            'lastName' => 'sn',
            'emailAddress' => 'mail',
            'name' => 'cn'
        ];
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap($map);
        $fromLdap = [
            'givenName' => 'Egon',
            'sn' => 'Spengler',
            'mail' => 'espengler@whhhhhy.local',
            'cn' => 'Egon',
            'dn' => 'CN=Egon,dc=whhhhhy,dc=local',
        ];
        $keys = array_unique(array_merge(array_keys($fromLdap), array_keys($map)));

        $this->beConstructedWith($schema);
        $this->fromLdap($fromLdap, ['*'])->shouldHaveKeys($keys);
    }

    public function getMatchers()
    {
        return [
            'haveKeys' => function($subject, $keys) {
                return (count(array_intersect_key(array_flip($keys), $subject)) === count($keys));
            },
        ];
    }
}

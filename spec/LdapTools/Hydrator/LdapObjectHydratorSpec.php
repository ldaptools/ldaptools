<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Hydrator;

use LdapTools\Object\LdapObject;
use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;

class LdapObjectHydratorSpec extends ObjectBehavior
{
    protected $ldapEntries = [
        'count' => 2,
        0 => [
            'cn' => [
                'count' => 1,
                0 => "Smith, Archie",
            ],
            0 => "cn",
            'sn' => [
                'count' => 1,
                0 => "Smith",
            ],
            1 => "sn",
            'givenname' => [
                'count' => 1,
                0 => "Archie",
            ],
            2 => "givenname",
            'whencreated' => [
                'count' => 1,
                0 => "19960622123421Z",
            ],
            3 => "whencreated",
            'count' => 3,
            'dn' => "CN=Smith\, Archie,OU=DE,OU=Employees,DC=example,DC=local",
        ],
        1 => [
            'cn' => [
                'count' => 1,
                0 => "Smith, John",
            ],
            0 => "cn",
            'sn' => [
                'count' => 1,
                0 => "Smith",
            ],
            1 => "sn",
            'givenname' => [
                'count' => 1,
                0 => "John",
            ],
            2 => "givenname",
            'whenCreated' => [
                'count' => 1,
                0 => "19920622123421Z",
            ],
            3 => "whenCreated",
            'count' => 3,
            'dn' => "CN=Smith\, John,OU=DE,OU=Employees,DC=example,DC=local",

        ]
    ];

    protected $objectToLdap = [
        'firstName' => 'Egon',
        'lastName' => 'Spengler',
        'emailAddress' => '%firstname%.%lastname%@%_domain_%',
        'name' => '%firstname%',
    ];

    protected $batch = [
        [
            'attrib' => 'givenName',
            'modtype' => LDAP_MODIFY_BATCH_REPLACE,
            'values' => ['Chad'],
        ],
        [
            'attrib' => 'sn',
            'modtype' => LDAP_MODIFY_BATCH_ADD,
            'values' => ['Sikorra'],
        ],
        [
            'attrib' => 'sAMAccountName',
            'modtype' => LDAP_MODIFY_BATCH_REMOVE,
            'values' => ['csikorra'],
        ],
        [
            'attrib' => 'mail',
            'modtype' => LDAP_MODIFY_BATCH_REMOVE_ALL,
        ],
    ];

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Hydrator\LdapObjectHydrator');
    }

    function it_should_extend_the_array_hydrator()
    {
        $this->shouldHaveType('\LdapTools\Hydrator\LdapObjectHydrator');
    }

    function it_should_error_if_attempting_to_hydrate_a_non_LdapObject_to_ldap()
    {
        $this->shouldThrow('\InvalidArgumentException')->duringHydrateToLdap([]);
    }

    function it_should_hydrate_an_entry_from_ldap_to_a_ldap_object()
    {
        $this->hydrateFromLdap($this->ldapEntries[0])->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObject');
    }

    function it_should_hydrate_all_entries_from_ldap_to_a_ldap_object_collection()
    {
        $this->hydrateAllFromLdap($this->ldapEntries)->shouldReturnAnInstanceOf('\LdapTools\Object\LdapObjectCollection');
        $this->hydrateAllFromLdap($this->ldapEntries)->count()->shouldBeEqualTo(2);
    }

    function it_should_hydrate_a_ldap_object_with_batch_modification()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap([ 'firstName' => 'givenName','lastName' => 'sn', 'emailAddress' => 'mail', 'username' => 'sAMAccountName' ]);
        $this->setLdapObjectSchema($schema);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $ldapObject->set('firstName', 'Chad');
        $ldapObject->add('lastName', 'Sikorra');
        $ldapObject->remove('username', 'csikorra');
        $ldapObject->reset('emailAddress');

        $this->hydrateToLdap($ldapObject)->shouldBeEqualTo($this->batch);
        $this->hydrateToLdap($ldapObject)->shouldHaveCount(4);
    }

    function it_should_hydrate_a_ldap_object_wihtout_a_schema_with_batch_modification()
    {
        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', '');
        $ldapObject->set('givenName', 'Chad');
        $ldapObject->add('sn', 'Sikorra');
        $ldapObject->remove('sAMAccountName', 'csikorra');
        $ldapObject->reset('mail');

        $this->hydrateToLdap($ldapObject)->shouldBeEqualTo($this->batch);
        $this->hydrateToLdap($ldapObject)->shouldHaveCount(4);
    }

    public function getMatchers()
    {
        return [
            'haveKeyWithValue' => function($subject, $key, $value) {
                return isset($subject[$key]) && ($subject[$key] === $value);
            }
        ];
    }
}

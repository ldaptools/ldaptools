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
use Prophecy\Argument;

class ArrayHydratorSpec extends ObjectBehavior
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

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Hydrator\ArrayHydrator');
    }

    function it_should_implement_the_HydratorInterface()
    {
        $this->shouldImplement('\LdapTools\Hydrator\HydratorInterface');
    }

    function it_should_set_selected_attributes_when_calling_setSelectedAttributes()
    {
        $attributes = ['foo', 'bar'];
        $this->setSelectedAttributes($attributes);
        $this->getSelectedAttributes()->shouldBeEqualTo($attributes);
    }

    function it_should_set_LdapObjectSchemas_when_calling_setLdapObjectSchemas()
    {
        $schemas = [ new LdapObjectSchema('foo', 'bar') ];
        $this->setLdapObjectSchemas(...$schemas);
        $this->getLdapObjectSchemas()->shouldBeEqualTo($schemas);
    }

    function it_should_return_a_single_array_with_keys_when_calling_hydrateEntryFromLdap()
    {
        $this->hydrateFromLdap($this->ldapEntries[0])->shouldHaveKey('sn');
    }

    function it_should_return_the_correct_number_of_entries_when_hydrating_all_entries()
    {
        $this->hydrateAllFromLdap($this->ldapEntries)->shouldHaveCount(2);
    }

    function it_should_allow_multiple_schema_names_pointing_to_the_same_ldap_attribute()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap(['firstName' => 'givenName', 'lastName' => 'sn', 'created' => 'whenCreated', 'createdInt' => 'whenCreated']);
        $this->setLdapObjectSchemas($schema);

        $this->setSelectedAttributes(['givenName', 'lastName', 'created', 'createdInt']);
        $this->hydrateFromLdap($this->ldapEntries[0])->shouldHaveKeys(['created', 'createdInt']);
    }

    function it_should_not_change_attribute_names_when_the_attribute_was_selected_by_its_original_name()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap(['firstName' => 'givenName', 'lastName' => 'sn']);

        $this->setLdapObjectSchemas($schema);
        $this->setSelectedAttributes(['givenName', 'lastName']);
        $this->hydrateFromLdap($this->ldapEntries[0])->shouldHaveKey('givenName');
    }

    function it_should_respect_the_case_the_attribute_was_selected_by_when_hydrating()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap(['firstName' => 'givenName', 'lastName' => 'sn']);
        $this->setLdapObjectSchemas($schema);

        $this->setSelectedAttributes(['givenName', 'lastName']);
        $this->hydrateFromLdap($this->ldapEntries[0])->shouldHaveKey('givenName');

        $this->setSelectedAttributes(['GivenName', 'lastName']);
        $this->hydrateFromLdap($this->ldapEntries[0])->shouldHaveKey('GivenName');

        $this->setSelectedAttributes(['givenName', 'LastName']);
        $this->hydrateFromLdap($this->ldapEntries[0])->shouldHaveKey('LastName');
    }

    function it_should_convert_values_if_a_converter_was_defined_for_an_attribute()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap(['firstName' => 'givenName', 'lastName' => 'sn', 'created' => 'whenCreated']);
        $schema->setConverterMap(['created' => 'generalized_time']);
        $this->setLdapObjectSchemas($schema);

        $this->setSelectedAttributes(['givenName', 'lastName', 'created']);
        $this->hydrateFromLdap($this->ldapEntries[0])->shouldHaveKeyWithDateTime('created');
    }

    function it_should_allow_multiple_converter_types_for_one_ldap_attribute()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap(['firstName' => 'givenName', 'lastName' => 'sn', 'created' => 'whenCreated', 'createdInt' => 'whenCreated']);
        $schema->setConverterMap(['created' => 'generalized_time', 'createdInt' => 'int']);
        $this->setLdapObjectSchemas($schema);

        $this->setSelectedAttributes(['givenName', 'lastName', 'created', 'createdInt']);
        $this->hydrateFromLdap($this->ldapEntries[0])->shouldHaveKeyWithDateTime('created');
        $this->hydrateFromLdap($this->ldapEntries[0])->shouldHaveKeyWithInt('createdInt');
    }

    function it_should_return_the_dn_attribute_even_if_it_wasnt_selected()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap(['firstName' => 'givenName', 'lastName' => 'sn']);
        $this->setLdapObjectSchemas($schema);

        $this->setSelectedAttributes(['givenName', 'lastName']);
        $this->hydrateFromLdap($this->ldapEntries[0])->shouldHaveKey('dn');
    }

    function it_should_return_an_array_when_hydrating_to_ldap()
    {
        $schema = new LdapObjectSchema('ad', 'user');

        $this->setLdapObjectSchemas($schema);
        $this->hydrateToLdap($this->objectToLdap)->shouldBeArray();
    }

    function it_should_hydrate_to_ldap_even_without_a_schema()
    {
        $this->hydrateToLdap($this->objectToLdap)->shouldBeArray();
    }

    function it_should_error_when_a_required_attribute_is_missing_going_to_ldap()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setRequiredAttributes(['foo']);

        $this->setLdapObjectSchemas($schema);
        $this->shouldThrow('\LogicException')->duringHydrateToLdap($this->objectToLdap);
    }

    function it_should_merge_default_attributes_to_ldap()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setDefaultValues([
            'phoneNumber' => '555-2368',
            'emailAddress' => '%firstname%.lastname%@%whoyougonnacall%'
        ]);

        $this->setLdapObjectSchemas($schema);
        $this->setParameter('whoyougonnacall', 'ghostbusters.local');
        $this->hydrateToLdap($this->objectToLdap)->shouldHaveKeyWithValue('phoneNumber', '555-2368');
        $this->hydrateToLdap($this->objectToLdap)->shouldNotContain('Egon.Spengler@ghostbusters.local');
    }

    function it_should_rename_schema_names_to_ldap_attributeNames_when_hyrdating_to_ldap()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap([
            'firstName' => 'givenName',
            'lastName' => 'sn',
            'emailAddress' => 'mail',
            'name' => 'cn'
        ]);

        $this->setLdapObjectSchemas($schema);
        $this->hydrateToLdap($this->objectToLdap)->shouldHaveKey('givenName');
        $this->hydrateToLdap($this->objectToLdap)->shouldHaveKey('sn');
        $this->hydrateToLdap($this->objectToLdap)->shouldHaveKey('mail');
        $this->hydrateToLdap($this->objectToLdap)->shouldHaveKey('cn');
    }

    function it_should_replace_parameter_values_with_their_actual_values()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap([
            'firstName' => 'givenName',
            'lastName' => 'sn',
            'emailAddress' => 'mail',
            'name' => 'cn'
        ]);

        $this->setLdapObjectSchemas($schema);
        $this->setParameter('_domain_', 'foo.bar');
        $this->hydrateToLdap($this->objectToLdap)->shouldHaveKeyWithValue('givenName', 'Egon');
        $this->hydrateToLdap($this->objectToLdap)->shouldHaveKeyWithValue('sn', 'Spengler');
        $this->hydrateToLdap($this->objectToLdap)->shouldHaveKeyWithValue('mail', 'Egon.Spengler@foo.bar');
        $this->hydrateToLdap($this->objectToLdap)->shouldHaveKeyWithValue('cn', 'Egon');
    }

    function it_should_convert_values_when_hydrating_to_ldap()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap([ 'foo' => 'bar' ]);
        $schema->setConverterMap(['foo' => 'bool']);
        $this->setLdapObjectSchemas($schema);
        $attributes = $this->objectToLdap;
        $attributes['foo'] = true;

        $this->hydrateToLdap($attributes)->shouldContain('TRUE');
    }

    function it_should_hydrate_a_ldap_batch_modify_spec_()
    {
        $batch = [
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

        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap([ 'firstName' => 'givenName','lastName' => 'sn', 'emailAddress' => 'mail', 'username' => 'sAMAccountName' ]);
        $this->setLdapObjectSchemas($schema);

        $ldapObject = new LdapObject(['dn' => 'cn=foo,dc=foo,dc=bar'], [], 'user', 'user');
        $ldapObject->set('firstName', 'Chad');
        $ldapObject->add('lastName', 'Sikorra');
        $ldapObject->remove('username', 'csikorra');
        $ldapObject->reset('emailAddress');

        $this->hydrateBatchToLdap($ldapObject->getBatchModifications())->shouldBeEqualTo($batch);
        $this->hydrateBatchToLdap($ldapObject->getBatchModifications())->shouldHaveCount(4);
    }

    public function getMatchers()
    {
        return [
            'haveKeyWithDateTime' => function($subject, $key) {
                return (isset($subject[$key]) && ($subject[$key] instanceof \DateTime));
            },
            'haveKeyWithInt' => function($subject, $key) {
                return (isset($subject[$key]) && (is_int($subject[$key])));
            },
            'haveKeys' => function($subject, $keys) {
                return (count(array_intersect_key(array_flip($keys), $subject)) === count($keys));
            },
            'haveKeyWithValue' => function($subject, $key, $value) {
                return isset($subject[$key]) && ($subject[$key] === $value);
            }
        ];
    }
}

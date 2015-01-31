<?php

namespace spec\LdapTools\Query\Hydrator;

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


    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Query\Hydrator\ArrayHydrator');
    }

    function it_should_implement_the_HydratorInterface()
    {
        $this->shouldImplement('\LdapTools\Query\Hydrator\HydratorInterface');
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

    function it_should_return_a_single_array_with_keys_when_calling_hydrateEntry()
    {
        $this->hydrateEntry($this->ldapEntries[0])->shouldHaveKey('sn');
    }

    function it_should_return_the_correct_number_of_entries_when_hydrating_all_entries()
    {
        $this->hydrateAll($this->ldapEntries)->shouldHaveCount(2);
    }

    function it_should_allow_multiple_schema_names_pointing_to_the_same_ldap_attribute()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap(['firstName' => 'givenName', 'lastName' => 'sn', 'created' => 'whenCreated', 'createdInt' => 'whenCreated']);
        $this->setLdapObjectSchemas($schema);

        $this->setSelectedAttributes(['givenName', 'lastName', 'created', 'createdInt']);
        $this->hydrateEntry($this->ldapEntries[0])->shouldHaveKeys(['created', 'createdInt']);
    }

    function it_should_not_change_attribute_names_when_the_attribute_was_selected_by_its_original_name()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap(['firstName' => 'givenName', 'lastName' => 'sn']);

        $this->setLdapObjectSchemas($schema);
        $this->setSelectedAttributes(['givenName', 'lastName']);
        $this->hydrateEntry($this->ldapEntries[0])->shouldHaveKey('givenName');
    }

    function it_should_respect_the_case_the_attribute_was_selected_by_when_hydrating()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap(['firstName' => 'givenName', 'lastName' => 'sn']);
        $this->setLdapObjectSchemas($schema);

        $this->setSelectedAttributes(['givenName', 'lastName']);
        $this->hydrateEntry($this->ldapEntries[0])->shouldHaveKey('givenName');

        $this->setSelectedAttributes(['GivenName', 'lastName']);
        $this->hydrateEntry($this->ldapEntries[0])->shouldHaveKey('GivenName');

        $this->setSelectedAttributes(['givenName', 'LastName']);
        $this->hydrateEntry($this->ldapEntries[0])->shouldHaveKey('LastName');
    }

    function it_should_convert_values_if_a_converter_was_defined_for_an_attribute()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap(['firstName' => 'givenName', 'lastName' => 'sn', 'created' => 'whenCreated']);
        $schema->setConverterMap(['created' => 'convert_generalized_time']);
        $this->setLdapObjectSchemas($schema);

        $this->setSelectedAttributes(['givenName', 'lastName', 'created']);
        $this->hydrateEntry($this->ldapEntries[0])->shouldHaveKeyWithDateTime('created');
    }

    function it_should_allow_multiple_converter_types_for_one_ldap_attribute()
    {
        $schema = new LdapObjectSchema('ad', 'user');
        $schema->setAttributeMap(['firstName' => 'givenName', 'lastName' => 'sn', 'created' => 'whenCreated', 'createdInt' => 'whenCreated']);
        $schema->setConverterMap(['created' => 'convert_generalized_time', 'createdInt' => 'convert_int']);
        $this->setLdapObjectSchemas($schema);

        $this->setSelectedAttributes(['givenName', 'lastName', 'created', 'createdInt']);
        $this->hydrateEntry($this->ldapEntries[0])->shouldHaveKeyWithDateTime('created');
        $this->hydrateEntry($this->ldapEntries[0])->shouldHaveKeyWithInt('createdInt');
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
            }
        ];
    }
}

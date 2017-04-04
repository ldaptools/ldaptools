<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Utilities;

use LdapTools\Utilities\LdapUtilities;
use PhpSpec\ObjectBehavior;

class LdapUtilitiesSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Utilities\LdapUtilities');
    }

    function it_should_escape_values_when_calling_escape_values()
    {
        $this::escapeValue('*)(user=*)(')->shouldBeEqualTo('\2a\29\28\75\73\65\72\3d\2a\29\28');
    }

    function it_should_ignore_specified_values_when_escaping()
    {
        $this::escapeValue('*)(user=*)(', '*')->shouldBeEqualTo('*\29\28\75\73\65\72\3d*\29\28');
    }

    function it_should_escape_to_hex_by_default()
    {
        $this::escapeValue("foo=bar\r")->shouldBeEqualTo('\66\6f\6f\3d\62\61\72\0d');
    }

    function it_should_allow_escaping_in_the_context_of_a_search_filter()
    {
        $this::escapeValue("cn=test=,stuff )", null, LDAP_ESCAPE_FILTER)->shouldBeEqualTo('cn=test=,stuff \29');
    }

    function it_should_allow_escaping_a_dn()
    {
        $this::escapeValue(' Joe,= Smith ', null, LDAP_ESCAPE_DN)->shouldBeEqualTo('\20Joe\2c\3d Smith\20');
    }

    function it_should_encode_carriage_returns_when_escaping_a_dn()
    {
        $this->escapeValue("Before\rAfter", null, LDAP_ESCAPE_DN)
            ->shouldBeEqualTo('Before\0dAfter');
        $this->escapeValue("Before\rAfter", null, LDAP_ESCAPE_FILTER)
            ->shouldBeEqualTo("Before\rAfter");
    }

    function it_should_unescape_hex_values_back_to_a_string()
    {
        $this::unescapeValue('\46\6f\6f\3d\42\61\72')->shouldBeEqualTo('Foo=Bar');
    }

    function it_should_explode_a_dn_to_an_array()
    {
        $this::explodeDn('cn=Foo,dc=foo,dc=bar')->shouldHaveCount(3);
        $this::explodeDn('cn=Foo,dc=foo,dc=bar')->shouldBeEqualTo(['Foo','foo', 'bar']);
        $this::explodeDn('cn=Foo,dc=foo,dc=bar', 0)->shouldBeEqualTo(['cn=Foo','dc=foo', 'dc=bar']);
    }

    function when_exploding_a_dn_it_should_unescape_hex_values()
    {
        $this::explodeDn('cn=Foo\,\=bar,dc=foo,dc=bar')->shouldContain('Foo=bar');
        $this::explodeDn('cn=Foo\,\=bar,dc=foo,dc=bar')->shouldHaveCount(3);
        $this::explodeDn('cn=Foo\,\=bar,dc=foo,dc=bar', 0)->shouldContain('cn=Foo=bar');
    }

    function it_should_throw_an_error_on_an_invalid_dn()
    {
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->during('explodeDn', ['foo-bar']);
    }

    function it_should_implode_a_dn_array_to_a_string_dn()
    {
        $this::implodeDn(['CN=Foo','DC=example','DC=local'])->shouldBeEqualTo('CN=Foo,DC=example,DC=local');
    }

    function it_should_escape_special_characters_when_imploding_a_DN_array()
    {
        $this::implodeDn(['CN=Foo,=Bar','DC=example','DC=local'])->shouldBeEqualTo('CN=Foo\2c\3dBar,DC=example,DC=local');
    }

    function it_should_throw_an_error_when_imploding_a_DN_in_invalid_array_form()
    {
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->during('implodeDn', [['foo', 'bar']]);
    }

    function it_should_encode_values_to_the_desired_type()
    {
        $this::encode('foo', 'UTF-8')->shouldBeEqualTo('foo');
        $this::encode('fóó', 'UTF-8')->shouldBeEqualTo('fóó');
    }

    function it_should_check_whether_a_dn_is_a_valid_ldap_object()
    {
        $this::isValidLdapObjectDn('cn=foo,dc=example,dc=com')->shouldBeEqualTo(true);
        $this::isValidLdapObjectDn('dc=example,dc=com')->shouldBeEqualTo(true);
        $this::isValidLdapObjectDn('foo,=bar')->shouldBeEqualTo(false);
    }

    function it_should_check_if_an_attribute_is_a_valid_OID_or_short_name()
    {
        $this->isValidAttributeFormat('1.2.840.113556.1.4.221')->shouldBeEqualTo(true);

        $this->isValidAttributeFormat('l')->shouldBeEqualTo(true);
        $this->isValidAttributeFormat('foo-bar')->shouldBeEqualTo(true);
        $this->isValidAttributeFormat('foo1-bar')->shouldBeEqualTo(true);
        $this->isValidAttributeFormat('fooBar')->shouldBeEqualTo(true);
        $this->isValidAttributeFormat('имя')->shouldBeEqualTo(true);
        $this->isValidAttributeFormat('名字')->shouldBeEqualTo(true);

        $this->isValidAttributeFormat('1foobar')->shouldBeEqualTo(false);
        $this->isValidAttributeFormat('foo_bar')->shouldBeEqualTo(false);
        $this->isValidAttributeFormat(')(&)')->shouldBeEqualTo(false);
        $this->isValidAttributeFormat('Test=*')->shouldBeEqualTo(false);
        $this->isValidAttributeFormat('foo bar')->shouldBeEqualTo(false);
        $this->isValidAttributeFormat('им=я')->shouldBeEqualTo(false);
        $this->isValidAttributeFormat('名(字')->shouldBeEqualTo(false);
    }

    function it_should_check_if_a_value_is_a_valid_SID()
    {
        $SIDs = [
            'S-1-5-21-1004336348-1177238915-682003330-512',
            'S-1-5-32-544',
            'S-1-0-0',
            'S-1-0',
            'S-1-1',
            'S-1-5-10',
            's-1-5-10',
            'S-1-5-21-123-12-123-12-123-12-123-12-123-12-123-123-12-15'
        ];

        foreach ($SIDs as $sid) {
            $this::isValidSid($sid)->shouldBeEqualTo(true);
        }

        $this::isValidSid('S-1')->shouldBeEqualTo(false);
        $this::isValidSid('S')->shouldBeEqualTo(false);
        $this::isValidSid('S--')->shouldBeEqualTo(false);
        $this::isValidSid('S-1-5-23-')->shouldBeEqualTo(false);
        $this::isValidSid('foo')->shouldBeEqualTo(false);
        // A max of 15 sub authorities are allowed
        $this::isValidSid('S-1-5-21-123-12-123-12-123-12-123-12-123-12-123-123-12-15-16')->shouldBeEqualTo(false);
        // Sub authorities are unsigned 32bit integers, max char length of 10
        $this::isValidSid('S-1-5-21-123-12345678910')->shouldBeEqualTo(false);
    }

    function it_should_check_if_a_value_is_a_valid_GUID()
    {
        $this::isValidGuid(LdapUtilities::uuid4())->shouldBeEqualTo(true);
        $this::isValidGuid('bc7d93d1-3d4d-4535-88bb-d61758684700')->shouldBeEqualTo(true);

        $this::isValidGuid('bc7d93d1-3d4d-4535-88bb')->shouldBeEqualTo(false);
        $this::isValidGuid('bc7d93d-3d4-4535-88bb-d6175868470')->shouldBeEqualTo(false);
        $this::isValidGuid('foo')->shouldBeEqualTo(false);
    }

    function it_should_explode_a_legacy_dn()
    {
        $this::explodeExchangeLegacyDn('/o=LdapTools')->shouldBeEqualTo(['LdapTools']);
        $this::explodeExchangeLegacyDn('/o=LdapTools', true)->shouldBeEqualTo(['o=LdapTools']);

        $this::explodeExchangeLegacyDn('/o=LdapTools/ou=Exchange Administrative Group (FYDIBOHF23SPDLT)')->shouldBeEqualTo([
            'LdapTools',
            'Exchange Administrative Group (FYDIBOHF23SPDLT)'
        ]);
        $this::explodeExchangeLegacyDn('/o=LdapTools/ou=Exchange Administrative Group (FYDIBOHF23SPDLT)', true)->shouldBeEqualTo([
            'o=LdapTools',
            'ou=Exchange Administrative Group (FYDIBOHF23SPDLT)'
        ]);
        $this->shouldThrow('LdapTools\Exception\InvalidArgumentException')->during('explodeExchangeLegacyDn', ['dc=foo,dc=bar']);
    }

    function it_should_get_the_rdn_from_a_dn()
    {
        $this::getRdnFromDn('cn=Foo,dc=example,dc=com')->shouldBeEqualTo('cn=Foo');
    }
    
    function it_should_get_the_parent_of_a_dn()
    {
        $this::getParentDn('cn=Foo,dc=example,dc=com')->shouldBeEqualTo('dc=example,dc=com');
    }
    
    function it_should_throw_an_error_when_there_is_no_parent_dn()
    {
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->during('getParentDn', ['o=foo']);
    }
    
    function it_should_mask_passwords_and_binary_in_an_array_of_attributes_and_values()
    {
        $attributes = [
            'username' => 'foo',
            'unicodePwd' => 'correct horse battery staple',
            'userPassword' => '12345',
            'userParameters' => hex2bin('f0ba44'),
        ];
        $masked = $attributes;
        $masked['unicodePwd'] = LdapUtilities::MASK_PASSWORD;
        $masked['userPassword'] = LdapUtilities::MASK_PASSWORD;
        $masked['userParameters'] = LdapUtilities::MASK_BINARY;

        $this::sanitizeAttributeArray($attributes)->shouldBeEqualTo($masked);
    }

    function it_should_mask_passwords_and_binary_data_in_a_ldap_batch_array()
    {
        $batch = [
            [
                "attrib"  => "unicodePwd",
                "modtype" => LDAP_MODIFY_BATCH_REMOVE,
                "values"  => ["password"],
            ],
            [
                "attrib"  => "userPassword",
                "modtype" => LDAP_MODIFY_BATCH_ADD,
                "values"  => ["correct horse battery staple"],
            ],
            [
                "attrib"  => "givenName",
                "modtype" => LDAP_MODIFY_BATCH_REPLACE,
                "values"  => ["Jack"],
            ],
            [
                "attrib"  => "userParameters",
                "modtype" => LDAP_MODIFY_BATCH_REPLACE,
                "values"  => [hex2bin('f0ba44')],
            ],
        ];

        $masked = $batch;
        $masked[0]['values'] = [LdapUtilities::MASK_PASSWORD];
        $masked[1]['values'] = [LdapUtilities::MASK_PASSWORD];
        $masked[3]['values'] = [LdapUtilities::MASK_BINARY];

        $this::sanitizeBatchArray($batch)->shouldBeEqualTo($masked);
    }
    
    function it_should_check_if_a_string_is_actually_binary_data()
    {
        $this::isBinary(hex2bin('f0ba44'))->shouldEqual(true);
        $this::isBinary("\r\nfoo\r\tbar ")->shouldEqual(false);
        $this::isBinary('123')->shouldEqual(false);
        $this->isBinary('UTF-8 Data - fÒbÀr.')->shouldEqual(false);
    }
    
    function it_should_split_a_string_between_its_alias_and_attribute()
    {
        $this::getAliasAndAttribute('foo.bar')->shouldBeEqualTo(['foo', 'bar']);
        $this::getAliasAndAttribute('foobar')->shouldBeEqualTo([null, 'foobar']);
    }

    function it_should_generate_a_UUIDv4_string()
    {
        $this::uuid4()->shouldBeString();
        $this::uuid4()->shouldMatch(LdapUtilities::MATCH_GUID);
    }
}

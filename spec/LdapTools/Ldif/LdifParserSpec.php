<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Ldif;

use LdapTools\Connection\LdapControl;
use LdapTools\Exception\LdifParserException;
use LdapTools\Ldif\Entry\LdifEntryAdd;
use LdapTools\Ldif\Entry\LdifEntryDelete;
use LdapTools\Ldif\Entry\LdifEntryModDn;
use LdapTools\Ldif\Entry\LdifEntryModify;
use LdapTools\Ldif\Entry\LdifEntryModRdn;
use LdapTools\Ldif\UrlLoader\BaseUrlLoader;
use LdapTools\Operation\AddOperation;
use PhpSpec\ObjectBehavior;

class LdifParserSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Ldif\LdifParser');
    }

    function it_should_parse_ldif_data()
    {
        $ldif = file_get_contents(__DIR__.'/../../resources/ldif/sample1.txt');

        $add1 = new LdifEntryAdd('cn=Barbara Jensen, ou=Product Development, dc=airius, dc=com', [
            'objectclass' => ['top', 'person', 'organizationalPerson'],
            'cn' => [ 'Barbara Jensen', 'Barbara J Jensen', 'Babs Jensen'],
            'sn' => 'Jensen',
            'uid' => 'bjensen',
            'telephonenumber' => '+1 408 555 1212',
            'description' => "Babs is a big sailing fan, and travels extensively in search of perfect sailing conditions.",
            'title' => 'Product Manager, Rod and Reel Division',
        ]);
        $add1->addComment('implicit add example');
        $add1->addComment('With a continued comment included');

        $add2 = new LdifEntryAdd('cn=Fiona Jensen, ou=Marketing, dc=airius, dc=com', [
            'objectclass' => ['top', 'person', 'organizationalPerson'],
            'cn' => 'Fiona Jensen',
            'sn' => 'Jensen',
            'uid' => 'fiona',
            'telephonenumber' => '+1 408 555 1212',
            'description' => 'This description will spread across multiple lines.',
        ]);
        $add2->addComment('explicit add example', 'cn=Fiona Jensen, ou=Marketing, dc=airius, dc=com');

        $delete1 = new LdifEntryDelete('cn=Robert Jensen, ou=Marketing, dc=airius, dc=com');
        $delete1->addComment('delete example');

        $modrdn = new LdifEntryModRdn('cn=Paul Jensen, ou=Product Development, dc=airius, dc=com', null, 'cn=Paula Jensen', true);
        $modrdn->addComment('using a modrdn');

        $moddn = new LdifEntryModDn('ou=PD Accountants, ou=Product Development, dc=airius, dc=com', 'ou=Accounting, dc=airius, dc=com', 'ou=Product Development Accountants', false);
        $moddn->addComment('using a moddn');

        $modify1 = new LdifEntryModify('cn=Paula Jensen, ou=Product Development, dc=airius, dc=com');
        $modify1->add('postaladdress', '123 Anystreet $ Sunnyvale, CA $ 94086');
        $modify1->reset('description');
        $modify1->replace('telephonenumber', ['+1 408 555 1234', '+1 408 555 5678']);
        $modify1->delete('facsimiletelephonenumber', '+1 408 555 9876');
        $modify1->addComment('modifying an entry');

        $modify2 = new LdifEntryModify('cn=Ingrid Jensen, ou=Product Support, dc=airius, dc=com');
        $modify2->replace('postaladdress', []);
        $modify2->reset('description');
        $modify2->addComment('a replace and delete');

        $delete2 = new LdifEntryDelete('ou=Product Development, dc=airius, dc=com');
        $delete2->addControl((new LdapControl('1.2.840.113556.1.4.805'))->setCriticality(true));
        $delete2->addComment('Delete with a LDAP control');

        $this->parse($ldif)->getComments()->shouldBeEqualTo(['A sample LDIF with entries from the RFC: https://www.ietf.org/rfc/rfc2849.txt', 'This line should be concatenated as a single comment.']);
        $this->parse($ldif)->getEntries()->shouldHaveCount(8);
        $this->parse($ldif)->getEntries()->shouldHaveIndexWithValue(0, $add1);
        $this->parse($ldif)->getEntries()->shouldHaveIndexWithValue(1, $add2);
        $this->parse($ldif)->getEntries()->shouldHaveIndexWithValue(2, $delete1);
        $this->parse($ldif)->getEntries()->shouldHaveIndexWithValue(3, $modrdn);
        $this->parse($ldif)->getEntries()->shouldHaveIndexWithValue(4, $moddn);
        $this->parse($ldif)->getEntries()->shouldHaveIndexWithValue(5, $modify1);
        $this->parse($ldif)->getEntries()->shouldHaveIndexWithValue(6, $modify2);
        $this->parse($ldif)->getEntries()->shouldHaveIndexWithValue(7, $delete2);
    }

    function it_should_have_a_file_http_and_https_url_loader_by_default()
    {
        $this->hasUrlLoader('file')->shouldBeEqualTo(true);
        $this->hasUrlLoader('http')->shouldBeEqualTo(true);
        $this->hasUrlLoader('https')->shouldBeEqualTo(true);
    }

    function it_should_set_a_url_loader()
    {
        $type = 'foo';

        $this->hasUrlLoader($type)->shouldBeEqualTo(false);
        $this->setUrlLoader($type, new BaseUrlLoader());
        $this->hasUrlLoader($type)->shouldBeEqualTo(true);
    }

    function it_should_remove_a_url_loader()
    {
        $this->hasUrlLoader('http')->shouldBeEqualTo(true);
        $this->removeUrlLoader('http');
        $this->hasUrlLoader('http')->shouldBeEqualTo(false);
    }

    function it_should_throw_an_exception_when_parsing_an_entry_where_the_changetype_is_redefined()
    {
        $ldif = file_get_contents(__DIR__.'/../../resources/ldif/fail_redefined_changetype.txt');
        $e = new LdifParserException('The changetype directive has already been defined on line number 3 near "changetype: add"');

        $this->shouldThrow($e)->duringParse($ldif);
    }

    function it_should_throw_an_exception_when_a_changetype_is_invalid()
    {
        $ldif = file_get_contents(__DIR__.'/../../resources/ldif/fail_invalid_changetype.txt');
        $e = new LdifParserException('The changetype "foo" is invalid on line number 3 near "cn: foobar"');

        $this->shouldThrow($e)->duringParse($ldif);
    }

    function it_should_throw_an_exception_when_the_key_value_format_is_invalid()
    {
        $ldif = file_get_contents(__DIR__.'/../../resources/ldif/fail_invalid_key_value_format.txt');
        $e = new LdifParserException('Expecting a LDIF directive on line number 3 near "cn;; foobar"');

        $this->shouldThrow($e)->duringParse($ldif);
    }

    function it_should_throw_an_exception_when_the_url_loader_type_specified_is_not_recognized()
    {
        $ldif = file_get_contents(__DIR__.'/../../resources/ldif/fail_invalid_url_loader.txt');
        $e = new LdifParserException('Cannot find a URL loader for type "foo" on line number 3 near "cn:< foo://bar"');

        $this->shouldThrow($e)->duringParse($ldif);
    }

    function it_should_throw_an_exception_when_the_OID_in_the_ldap_control_is_not_in_the_correct_format()
    {
        $ldif = file_get_contents(__DIR__.'/../../resources/ldif/fail_invalid_control.txt');
        $e = new LdifParserException('The control directive has an invalid OID format "foo" on line number 3 near "control: foo"');

        $this->shouldThrow($e)->duringParse($ldif);
    }

    function it_should_throw_an_exception_when_the_ldif_version_is_not_recognized()
    {
        $ldif = file_get_contents(__DIR__.'/../../resources/ldif/fail_invalid_version.txt');
        $e = new LdifParserException('LDIF version "2" is not currently supported. on line number 1 near "version: 2"');

        $this->shouldThrow($e)->duringParse($ldif);
    }

    function it_should_throw_an_exception_when_the_directive_for_the_changetype_is_not_valid()
    {
        $ldif = file_get_contents(__DIR__.'/../../resources/ldif/fail_invalid_changetype_directive.txt');
        $e = new LdifParserException('Directive "foo" is not valid for a "moddn" changetype on line number 3 near "foo: bar"');

        $this->shouldThrow($e)->duringParse($ldif);
    }

    function it_should_throw_an_exception_when_parsing_a_modify_type_and_the_keys_following_the_directive_do_not_match_the_attribute()
    {
        $ldif = file_get_contents(__DIR__.'/../../resources/ldif/fail_modify_invalid_attribute.txt');
        $e = new LdifParserException('Attribute "bar" does not match "postaladdress" for adding values. on line number 4 near "foo: bar"');

        $this->shouldThrow($e)->duringParse($ldif);
    }

    function it_should_throw_an_exception_when_parsing_the_criticality_for_a_ldap_control_and_it_is_not_valid()
    {
        $ldif = file_get_contents(__DIR__.'/../../resources/ldif/fail_invalid_control_criticality.txt');
        $e = new LdifParserException('Expected "true" or "false" but got foo on line number 3 near "control: 1.2.840.113556.1.4.805 foo"');

        $this->shouldThrow($e)->duringParse($ldif);
    }

    function it_should_throw_an_exception_when_parsing_whether_the_old_rdn_should_be_deleted_and_it_is_not_valid()
    {
        $ldif = file_get_contents(__DIR__.'/../../resources/ldif/fail_invalid_deleteoldrdn.txt');
        $e = new LdifParserException('Expected "0" or "1" but got: false on line number 4 near "deleteoldrdn: false"');

        $this->shouldThrow($e)->duringParse($ldif);
    }

    function it_should_parse_an_ldif_with_an_empty_value()
    {
        $ldif = file_get_contents(__DIR__.'/../../resources/ldif/sample2.txt');
        $add = new AddOperation('',['objectClass' => ['top', 'OpenLDAProotDSE'], 'structuralObjectClass' => ['OpenLDAProotDSE']]);

        $this->parse($ldif)->toOperations()->shouldBeLike([$add]);
    }

    public function getMatchers()
    {
        return [
            'haveIndexWithValue' => function($subject, $index, $value) {
                if (!isset($subject[$index])) {
                    return false;
                }

                return $subject[$index] == $value;
            }
        ];
    }
}

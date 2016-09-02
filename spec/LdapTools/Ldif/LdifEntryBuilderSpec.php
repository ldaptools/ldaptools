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

use PhpSpec\ObjectBehavior;

class LdifEntryBuilderSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Ldif\LdifEntryBuilder');
    }

    function it_should_build_an_add_entry()
    {
        $dn = 'dc=foo,dc=bar';
        $attributes = ['givenName' => 'foo', 'sn' => 'bar'];

        $this->add($dn, $attributes)->shouldReturnAnInstanceOf('LdapTools\Ldif\Entry\LdifEntryAdd');
        $this->add($dn, $attributes)->getDn()->shouldBeEqualTo($dn);
        $this->add($dn, $attributes)->getAttributes()->shouldBeEqualTo(['givenName' => ['foo'], 'sn' => ['bar']]);
    }

    function it_should_build_a_delete_entry()
    {
        $dn = 'dc=foo,dc=bar';

        $this->delete($dn)->shouldReturnAnInstanceOf('LdapTools\Ldif\Entry\LdifEntryDelete');
        $this->delete($dn)->getDn()->shouldBeEqualTo($dn);
    }

    function it_should_build_a_modify_entry()
    {
        $dn = 'dc=foo,dc=bar';

        $this->modify($dn)->shouldReturnAnInstanceOf('LdapTools\Ldif\Entry\LdifEntryModify');
        $this->modify($dn)->getDn()->shouldBeEqualTo($dn);
    }

    function it_should_build_a_modrdn_entry_when_calling_rename()
    {
        $dn = 'dc=foo,dc=bar';
        $name = 'cn=foobar';

        $this->rename($dn, $name)->shouldReturnAnInstanceOf('LdapTools\Ldif\Entry\LdifEntryModRdn');
        $this->rename($dn, $name)->getDn()->shouldBeEqualTo($dn);
        $this->rename($dn, $name)->getNewRdn()->shouldBeEqualTo($name);

        // What should this really do with the old rdn?
        //$this->rename($dn, $name)->getDeleteOldRdn()->shouldBeEqualTo(true);
    }

    function it_should_build_a_moddn_when_calling_move()
    {
        $dn = 'cn=foobar,dc=foo,dc=bar';
        $ou = 'ou=employees,dc=foo,dc=bar';

        $this->move($dn, $ou)->shouldReturnAnInstanceOf('LdapTools\Ldif\Entry\LdifEntryModDn');
        $this->move($dn, $ou)->getDn()->shouldBeEqualTo($dn);
        $this->move($dn, $ou)->getNewLocation()->shouldBeEqualTo($ou);
        $this->move($dn, $ou)->getDeleteOldRdn()->shouldBeEqualTo(true);
    }

    function it_should_build_a_moddn_entry()
    {
        $dn = 'dc=foo,dc=bar';

        $this->moddn($dn)->shouldReturnAnInstanceOf('LdapTools\Ldif\Entry\LdifEntryModDn');
        $this->moddn($dn)->getDn()->shouldBeEqualTo($dn);
    }
}


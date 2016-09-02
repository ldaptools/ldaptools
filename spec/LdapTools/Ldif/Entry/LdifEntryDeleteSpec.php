<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Ldif\Entry;

use LdapTools\Connection\LdapControl;
use PhpSpec\ObjectBehavior;

class LdifEntryDeleteSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('dc=foo,dc=bar');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Ldif\Entry\LdifEntryDelete');
    }

    function it_should_implement_LdifEntryInterface()
    {
        $this->shouldImplement('\LdapTools\Ldif\Entry\LdifEntryInterface');
    }

    function it_should_set_the_dn()
    {
        $dn = 'foo';
        $this->setDn($dn);
        $this->getDn()->shouldBeEqualTo($dn);
    }

    function it_should_add_a_control()
    {
        $control = new LdapControl('foo');
        $this->addControl($control);

        $this->getControls()->shouldBeEqualTo([$control]);
    }

    function it_should_get_a_delete_operation()
    {
        $this->toOperation()->shouldReturnAnInstanceOf('LdapTools\Operation\DeleteOperation');
        $this->toOperation()->getDn()->shouldBeEqualTo('dc=foo,dc=bar');
    }

    function it_should_get_the_ldif_string_for_the_entry()
    {
        $ldif =
            "# Delete example.\r\n"
            ."dn: dc=foo,dc=bar\r\n"
            ."changetype: delete\r\n";
        $this->addComment('Delete example.');
        $this->toString()->shouldBeEqualTo($ldif);
    }

    function it_should_add_a_comment()
    {
        $this->addComment('test')->shouldReturnAnInstanceOf('LdapTools\Ldif\Entry\LdifEntryDelete');
        $this->getComments()->shouldHaveCount(1);

        $this->addComment('foo', 'bar');
        $this->getComments()->shouldHaveCount(3);

        $this->getComments()->shouldBeEqualTo(['test', 'foo', 'bar']);
    }
}

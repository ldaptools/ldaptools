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

use LdapTools\Ldif\Entry\LdifEntryAdd;
use LdapTools\Ldif\Entry\LdifEntryDelete;
use LdapTools\Ldif\Entry\LdifEntryModify;
use LdapTools\Ldif\Ldif;
use PhpSpec\ObjectBehavior;

class LdifSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Ldif\Ldif');
    }

    function it_should_set_the_ldif_version()
    {
        $this->setVersion(1)->shouldReturnAnInstanceOf('LdapTools\Ldif\Ldif');
    }

    function it_should_get_a_ldif_entry_builder()
    {
        $this->entry()->shouldReturnAnInstanceOf('LdapTools\Ldif\LdifEntryBuilder');
    }

    function it_should_add_a_comment()
    {
        $this->addComment('test')->shouldReturnAnInstanceOf('LdapTools\Ldif\Ldif');
        $this->getComments()->shouldHaveCount(1);

        $this->addComment('foo', 'bar');
        $this->getComments()->shouldHaveCount(3);

        $this->getComments()->shouldBeEqualTo(['test', 'foo', 'bar']);
    }

    function it_should_add_an_entry()
    {
        $delete = new LdifEntryDelete('dc=foo,dc=bar');
        $add = new LdifEntryAdd('dc=foo,dc=bar', ['foo' => 'bar']);
        $modify = new LdifEntryModify('dc=foo,dc=bar');

        $this->addEntry($delete)->shouldReturnAnInstanceOf('LdapTools\Ldif\Ldif');
        $this->getEntries()->shouldHaveCount(1);

        $this->addEntry($add, $modify)->getEntries()->shouldHaveCount(3);
        $this->getEntries()->shouldBeEqualTo([$delete, $add, $modify]);
    }

    function it_should_get_the_ldif_string()
    {
        $delete = new LdifEntryDelete('dc=foo,dc=bar');
        $add = new LdifEntryAdd('dc=foo,dc=bar', ['foo' => 'bar']);
        $this->addEntry($delete, $add);
        $this->addComment('foo');

        $ldif =
              "# foo\r\n"
            . "version: 1\r\n"
            . "\r\n"
            . "dn: dc=foo,dc=bar\r\n"
            . "changetype: delete\r\n"
            . "\r\n"
            . "dn: dc=foo,dc=bar\r\n"
            . "changetype: add\r\n"
            . "foo: bar\r\n";

        $this->toString()->shouldBeEqualTo($ldif);
    }

    function it_should_get_the_operations_for_the_ldif_entries()
    {
        $delete = new LdifEntryDelete('dc=foo,dc=bar');
        $add = new LdifEntryAdd('dc=foo,dc=bar', ['foo' => 'bar']);
        $this->addEntry($delete, $add);

        $this->toOperations()->shouldBeLike([$delete->toOperation(), $add->toOperation()]);
    }

    function it_should_set_the_line_endings_for_the_ldif_string()
    {
        $delete = new LdifEntryDelete('dc=foo,dc=bar');
        $add = new LdifEntryAdd('dc=foo,dc=bar', ['foo' => 'bar']);
        $this->addEntry($delete, $add);
        $this->addComment('foo');
        $this->setLineEnding(Ldif::LINE_ENDING['UNIX']);

        $ldif =
            "# foo\n"
            . "version: 1\n"
            . "\n"
            . "dn: dc=foo,dc=bar\n"
            . "changetype: delete\n"
            . "\n"
            . "dn: dc=foo,dc=bar\n"
            . "changetype: add\n"
            . "foo: bar\n";

        $this->toString()->shouldBeEqualTo($ldif);
    }

    function it_should_throw_an_exception_on_an_invalid_line_ending_type()
    {
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->duringSetLineEnding('foo');
    }

    function it_should_have_a_line_folding_set_to_false_by_default_with_a_length_of_76()
    {
        $this->getLineFolding()->shouldBeEqualTo(false);
        $this->getMaxLineLength(76);
    }

    function it_should_fold_long_lines_when_specified()
    {
        $dn = 'cn=foo,dc=example,dc=local';
        $description = 'This is a long line that will go over the 76 char limit and then be continued on the next line. We dont need no stinking wordwrap.';
        $givenName = 'foo';
        $comment = 'This is an LDIF file with some really long lines just to test some folded lines so they show up on the next.';
        $comment1 = 'An example comment.';
        $comment2 = 'This comment will go past the line length and should also be split onto the next line.';

        $add = new LdifEntryAdd($dn, [
            'description' => $description,
            'givenName' => $givenName,
        ]);
        $add->addComment($comment1, $comment2);
        $this->addEntry($add);
        $this->addComment($comment);

        $ldif =
            "# This is an LDIF file with some really long lines just to test some folded li\r\n"
            . " nes so they show up on the next.\r\n"
            . "version: 1\r\n"
            . "\r\n"
            . "# $comment1\r\n"
            . "# This comment will go past the line length and should also be split onto the \r\n"
            . " next line.\r\n"
            . "dn: cn=foo,dc=example,dc=local\r\n"
            . "changetype: add\r\n"
            . "description: This is a long line that will go over the 76 char limit and then be continue\r\n"
            . " d on the next line. We dont need no stinking wordwrap.\r\n"
            . "givenName: $givenName\r\n";

        $this->setLineFolding(true);
        $this->toString()->shouldBeEqualTo($ldif);
    }
}

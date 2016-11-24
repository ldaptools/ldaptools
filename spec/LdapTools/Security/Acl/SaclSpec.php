<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Security\Acl;

use LdapTools\Security\Acl\Acl;
use LdapTools\Security\Acl\Sacl;
use PhpSpec\ObjectBehavior;

class SaclSpec extends ObjectBehavior
{
    protected $expecteSddl = '(AU;SA;WDWOWP;;;WD)(OU;CIIOIDSA;WP;f30e3bbe-9ff0-11d1-b603-0000f80367c1;bf967aa5-0de6-11d0-a285-00aa003049e2;WD)(OU;CIIOIDSA;WP;f30e3bbf-9ff0-11d1-b603-0000f80367c1;bf967aa5-0de6-11d0-a285-00aa003049e2;WD)';

    function let()
    {
        $sacl = "04008c00030000000240140020000c00010100000000000100000000075a38002000000003000000be3b0ef3f09fd111b6030000f80367c1a57a96bfe60dd011a28500aa003049e2010100000000000100000000075a38002000000003000000bf3b0ef3f09fd111b6030000f80367c1a57a96bfe60dd011a28500aa003049e2010100000000000100000000";

        $this->beConstructedWith(hex2bin($sacl));
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Sacl::class);
    }

    function it_should_parse_and_get_the_ACL_revision()
    {
        $this->getRevision()->shouldBeEqualTo(4);
    }

    function it_should_parse_and_get_the_sbz1_value()
    {
        $this->getSbz1()->shouldBeEqualTo(0);
    }

    function it_should_set_the_sbz1_value()
    {
        $this->setSbz1(1)->getSbz1()->shouldBeEqualTo(1);
    }

    function it_should_parse_and_get_the_sbz2_value()
    {
        $this->getSbz2()->shouldBeEqualTo(0);
    }

    function it_should_set_the_sbz2_value()
    {
        $this->setSbz2(1)->getSbz2()->shouldBeEqualTo(1);
    }

    function it_should_parse_and_get_the_ACEs_for_the_ACL()
    {
        $this->getAces()->shouldBeArray();
        $this->getAces()->shouldHaveCount(3);
    }

    function it_should_get_the_binary_representation()
    {
        $sacl = "04008c00030000000240140020000c00010100000000000100000000075a38002000000003000000be3b0ef3f09fd111b6030000f80367c1a57a96bfe60dd011a28500aa003049e2010100000000000100000000075a38002000000003000000bf3b0ef3f09fd111b6030000f80367c1a57a96bfe60dd011a28500aa003049e2010100000000000100000000";

        $this->toBinary()->shouldBeEqualTo(hex2bin($sacl));
    }

    function it_should_set_the_ACL_revision()
    {
        $this->setRevision(Acl::REVISION['GENERIC'])->getRevision()->shouldBeEqualTo(Acl::REVISION['GENERIC']);
    }

    function it_should_get_the_sddl_representation_when_calling_toSddl()
    {
        $this->toSddl()->shouldBeEqualTo($this->expecteSddl);
    }

    function it_should_have_a_string_representation_that_outputs_the_sddl()
    {
        $this->__toString()->shouldBeEqualTo($this->expecteSddl);
    }
}

<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Security;

use LdapTools\Security\SID;
use PhpSpec\ObjectBehavior;

class SIDSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith('S-1-5-32-544');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(SID::class);
    }

    function it_should_get_the_revision_level()
    {
        $this->getRevisionLevel()->shouldBeEqualTo(1);
    }

    function it_should_get_the_identifier_authority()
    {
        $this->getIdentifierAuthority()->shouldBeEqualTo(5);
    }

    function it_should_get_the_sub_authority_count()
    {
        $this->getSubAuthorityCount()->shouldBeEqualTo(2);
    }

    function it_should_get_the_sub_authorities()
    {
        $this->getSubAuthorities()->shouldBeEqualTo([32, 544]);
    }

    function it_should_get_the_binary_representation()
    {
        $this->toBinary()->shouldBeEqualTo(hex2bin('01020000000000052000000020020000'));
    }

    function it_should_allow_being_constructed_from_binary()
    {
        $hex = '01020000000000052000000020020000';
        $this->beConstructedWith(hex2bin($hex));

        $this->getRevisionLevel()->shouldBeEqualTo(1);
        $this->getIdentifierAuthority()->shouldBeEqualTo(5);
        $this->getSubAuthorityCount()->shouldBeEqualTo(2);
        $this->getSubAuthorities()->shouldBeEqualTo([32, 544]);
        $this->toString()->shouldBeEqualTo('S-1-5-32-544');
        $this->toBinary()->shouldBeEqualTo(hex2bin($hex));
    }

    function it_should_parse_a_normal_domain_SID_from_binary()
    {
        $hex = '010500000000000515000000dcf4dc3b833d2b46828ba62800020000';
        $sid = 'S-1-5-21-1004336348-1177238915-682003330-512';
        $this->beConstructedWith(hex2bin($hex));

        $this->toString()->shouldBeEqualTo($sid);
        $this->toBinary()->shouldBeEqualTo(hex2bin($hex));
    }

    function it_should_parse_a_normal_domain_SID_from_string()
    {
        $hex = '010500000000000515000000dcf4dc3b833d2b46828ba62800020000';
        $sid = 'S-1-5-21-1004336348-1177238915-682003330-512';
        $this->beConstructedWith($sid);

        $this->toString()->shouldBeEqualTo($sid);
        $this->toBinary()->shouldBeEqualTo(hex2bin($hex));
    }

    function it_should_parse_a_builtin_domain_account_SID_from_binary()
    {
        $hex = '01020000000000052000000020020000';
        $sid = 'S-1-5-32-544';
        $this->beConstructedWith(hex2bin($hex));

        $this->toString()->shouldBeEqualTo($sid);
        $this->toBinary()->shouldBeEqualTo(hex2bin($hex));
    }

    function it_should_parse_a_builtin_domain_account_SID_from_string()
    {
        $hex = '01020000000000052000000020020000';
        $sid = 'S-1-5-32-544';
        $this->beConstructedWith($sid);

        $this->toString()->shouldBeEqualTo($sid);
        $this->toBinary()->shouldBeEqualTo(hex2bin($hex));
    }

    function it_should_parse_a_well_known_nobody_SID_from_binary()
    {
        $hex = '010100000000000000000000';
        $sid = 'S-1-0-0';
        $this->beConstructedWith(hex2bin($hex));

        $this->toString()->shouldBeEqualTo($sid);
        $this->toBinary()->shouldBeEqualTo(hex2bin($hex));
    }

    function it_should_parse_a_well_known_nobody_SID_from_string()
    {
        $hex = '010100000000000000000000';
        $sid = 'S-1-0-0';
        $this->beConstructedWith($sid);

        $this->toString()->shouldBeEqualTo($sid);
        $this->toBinary()->shouldBeEqualTo(hex2bin($hex));
    }

    function it_should_parse_a_well_known_null_SID_from_binary()
    {
        $hex = '0100000000000000';
        $sid = 'S-1-0';
        $this->beConstructedWith(hex2bin($hex));

        $this->toString()->shouldBeEqualTo($sid);
        $this->toBinary()->shouldBeEqualTo(hex2bin($hex));
    }

    function it_should_parse_a_well_known_null_SID_from_string()
    {
        $hex = '0100000000000000';
        $sid = 'S-1-0';
        $this->beConstructedWith($sid);

        $this->toString()->shouldBeEqualTo($sid);
        $this->toBinary()->shouldBeEqualTo(hex2bin($hex));
    }

    function it_should_parse_a_well_known_self_SID_from_binary()
    {
        $hex = '01010000000000050a000000';
        $sid = 'S-1-5-10';
        $this->beConstructedWith(hex2bin($hex));

        $this->toString()->shouldBeEqualTo($sid);
        $this->toBinary()->shouldBeEqualTo(hex2bin($hex));
    }

    function it_should_parse_a_well_known_self_SID_from_string()
    {
        $hex = '01010000000000050a000000';
        $sid = 'S-1-5-10';
        $this->beConstructedWith($sid);

        $this->toString()->shouldBeEqualTo($sid);
        $this->toBinary()->shouldBeEqualTo(hex2bin($hex));
    }

    function it_should_have_a_string_representation_of_a_friendly_SID_form()
    {
        $this->__toString()->shouldBeEqualTo('S-1-5-32-544');
    }

    function it_should_throw_an_exception_if_the_SID_cannot_be_decoded()
    {
        $this->shouldThrow('\UnexpectedValueException')->during('__construct', ['foo']);
    }

    function it_should_get_the_SID_short_name()
    {
        $this->beConstructedWith(SID::WELL_KNOWN['PRINCIPAL_SELF']);

        $this->getShortName()->shouldBeEqualTo('PS');
    }

    function it_should_be_constructed_with_the_short_name()
    {
        $this->beConstructedWith('PS');

        $this->toString()->shouldBeEqualTo(SID::WELL_KNOWN['PRINCIPAL_SELF']);
    }

    function it_should_get_the_short_name_for_a_well_known_SID_needing_the_domain_SID()
    {
        $this->beConstructedWith('S-1-5-21-1263317781-1938881490-3107577794-512');

        $this->getShortName()->shouldBeEqualTo('DA');
    }

    function it_should_get_the_short_name_for_a_well_known_SID_needing_the_root_domain_SID()
    {
        $this->beConstructedWith('S-1-5-21-1263317781-1938881490-3107577794-519');

        $this->getShortName()->shouldBeEqualTo('EA');
    }
}

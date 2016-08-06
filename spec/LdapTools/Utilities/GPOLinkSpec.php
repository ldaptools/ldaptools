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

use LdapTools\Utilities\GPOLink;
use PhpSpec\ObjectBehavior;

class GPOLinkSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('foo');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Utilities\GPOLink');
    }

    function it_should_be_constructed_with_the_name_and_a_default_options_flag_of_zero()
    {
        $this->getGpo()->shouldEqual('foo');
        $this->getOptionsFlag()->shouldBeEqualTo(0);
    }

    function it_should_be_constructed_with_an_options_flag_if_specified()
    {
        $this->beConstructedWith('foo', 1);
        $this->getOptionsFlag()->shouldBeEqualTo(1);
    }

    function it_should_only_allow_options_flags_between_zero_and_three()
    {
        $this->shouldNotThrow('\Exception')->duringSetOptionsFlag(0);
        $this->shouldNotThrow('\Exception')->duringSetOptionsFlag(1);
        $this->shouldNotThrow('\Exception')->duringSetOptionsFlag(2);
        $this->shouldNotThrow('\Exception')->duringSetOptionsFlag(3);
        $this->shouldThrow('LdapTools\Exception\InvalidArgumentException')->duringSetOptionsFlag(4);
    }

    function it_should_set_and_get_the_gpo()
    {
        $this->setGpo('foobar')->shouldReturnAnInstanceOf('LdapTools\Utilities\GPOLink');
        $this->getGpo()->shouldEqual('foobar');
    }

    function it_should_set_and_get_the_options_flag()
    {
        $this->setOptionsFlag(2)->shouldReturnAnInstanceOf('LdapTools\Utilities\GPOLink');
        $this->getOptionsFlag()->shouldEqual(2);
    }

    function it_should_set_and_get_whether_the_GPO_is_enabled()
    {
        $this->getIsEnabled()->shouldEqual(true);
        $this->setIsEnabled(false)->shouldReturnAnInstanceOf('LdapTools\Utilities\GPOLink');
        $this->getIsEnabled()->shouldEqual(false);
        $this->getOptionsFlag()->shouldBeEqualTo(GPOLink::FLAGS['IGNORED']);

        $this->setIsEnabled(true);
        $this->getIsEnabled()->shouldEqual(true);
        $this->getOptionsFlag()->shouldNotBeEqualTo(GPOLink::FLAGS['IGNORED']);
    }

    function it_should_set_and_get_whether_the_GPO_is_enforced()
    {
        $this->getIsEnforced()->shouldEqual(false);
        $this->setIsEnforced(true)->shouldReturnAnInstanceOf('LdapTools\Utilities\GPOLink');
        $this->getIsEnforced()->shouldEqual(true);
        $this->getOptionsFlag()->shouldBeEqualTo(GPOLink::FLAGS['ENFORCED']);
    }

    function it_should_allow_a_GPO_to_be_both_not_enabled_and_enforced()
    {
        $this->setIsEnforced(true);
        $this->setIsEnabled(false);

        $this->getIsEnforced()->shouldBe(true);
        $this->getIsEnabled()->shouldBe(false);
        $this->getOptionsFlag()->shouldEqual(GPOLink::FLAGS['IGNORED_ENFORCED']);
    }

    function it_should_not_modify_an_option_that_is_already_set()
    {
        $this->setIsEnabled(true);
        $this->getIsEnabled()->shouldEqual(true);
        $this->getOptionsFlag()->shouldBeEqualTo(0);

        $this->getIsEnforced()->shouldEqual(false);
        $this->setIsEnforced(false);
        $this->getIsEnforced()->shouldEqual(false);

        $this->setIsEnforced(true);
        $this->setIsEnforced(true);
        $this->getIsEnforced()->shouldEqual(true);

    }

    function it_should_have_a_string_representation_using_the_GPO_name()
    {
        $this->__toString()->shouldBeEqualTo('foo');
    }
}

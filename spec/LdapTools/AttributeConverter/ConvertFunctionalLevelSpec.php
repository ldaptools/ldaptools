<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\AttributeConverter;

use LdapTools\Connection\AD\ADFunctionalLevelType;
use PhpSpec\ObjectBehavior;

class ConvertFunctionalLevelSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertFunctionalLevel');
    }

    function it_should_convert_a_integer_to_a_functional_level_from_ldap()
    {
        $this->fromLdap('0')->shouldBeEqualTo(ADFunctionalLevelType::TYPES[0]);
        $this->fromLdap('1')->shouldBeEqualTo(ADFunctionalLevelType::TYPES[1]);
        $this->fromLdap('2')->shouldBeEqualTo(ADFunctionalLevelType::TYPES[2]);
        $this->fromLdap('3')->shouldBeEqualTo(ADFunctionalLevelType::TYPES[3]);
        $this->fromLdap('4')->shouldBeEqualTo(ADFunctionalLevelType::TYPES[4]);
        $this->fromLdap('5')->shouldBeEqualTo(ADFunctionalLevelType::TYPES[5]);
        $this->fromLdap('6')->shouldBeEqualTo(ADFunctionalLevelType::TYPES[6]);
        $this->fromLdap('7')->shouldBeEqualTo(ADFunctionalLevelType::TYPES[7]);
    }

    function it_should_return_unknown_if_the_level_is_not_a_valid_type()
    {
        $this->fromLdap('9001')->shouldBeEqualTo('Unknown');
    }
}

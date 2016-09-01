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

use LdapTools\AttributeConverter\AttributeConverterInterface;
use PhpSpec\ObjectBehavior;

class ConvertPasswordMustChangeSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertPasswordMustChange');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_convert_a_bool_to_the_expected_value_for_ldap()
    {
        $this->toLdap(true)->shouldBeEqualTo('0');
        $this->toLdap(false)->shouldBeEqualTo('-1');
    }

    function it_should_convert_the_ldap_value_to_a_php_bool()
    {
        $this->fromLdap('0')->shouldBeEqualTo(true);
        $this->fromLdap('130660331300000000')->shouldBeEqualTo(false);
    }
    
    function it_should_convert_a_bool_properly_when_searching_LDAP_with_a_filter()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);

        $this->toLdap(true)->shouldBeEqualTo('0');
        $this->toLdap(false)->toLdapFilter()->shouldEqual('(!(pwdLastSet=0))');   
    }
}

<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Object;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LdapObjectTypeSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Object\LdapObjectType');
    }

    function it_should_have_a_user_type_constant()
    {
        $this->shouldHaveConstant('USER');
    }

    function it_should_have_a_group_type_constant()
    {
        $this->shouldHaveConstant('GROUP');
    }

    function it_should_have_a_contact_type_constant()
    {
        $this->shouldHaveConstant('CONTACT');
    }

    function it_should_have_a_computer_type_constant()
    {
        $this->shouldHaveConstant('COMPUTER');
    }

    public function getMatchers()
    {
        return [
            'haveConstant' => function($subject, $constant) {
                return defined('\LdapTools\Object\LdapObjectType::'.$constant);
            }
        ];
    }
}

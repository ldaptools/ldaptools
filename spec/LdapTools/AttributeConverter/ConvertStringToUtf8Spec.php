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

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertStringToUtf8Spec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertStringToUtf8');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_return_a_utf8_string_when_calling_fromLdap()
    {
        $this->fromLdap('foo')->shouldBeEqualTo('foo');
    }

    function it_should_return_the_same_string_passed_when_calling_to_toLdap()
    {
        $this->toLdap('foo')->shouldHaveEncoding('UTF-8');
    }

    public function getMatchers()
    {
        return [
            'haveEncoding' => function($subject, $encoding) {
                return (bool) mb_detect_encoding($subject, $encoding);
            }
        ];
    }
}
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

class EncodeWindowsPasswordSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\EncodeWindowsPassword');
    }

    function it_should_implement_AttributeConverterInferface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_not_return_anything_when_calling_fromLdap()
    {
        $this->fromLdap('foo')->shouldBeNull();
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

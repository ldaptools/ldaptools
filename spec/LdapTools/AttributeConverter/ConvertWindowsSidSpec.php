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

class ConvertWindowsSidSpec extends ObjectBehavior
{
    protected $sidHex = '\01\05\00\00\00\00\00\05\15\00\00\00\dc\f4\dc\3b\83\3d\2b\46\82\8b\a6\28\00\02\00\00';

    protected $sidString = 'S-1-5-21-1004336348-1177238915-682003330-512';

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertWindowsSid');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_return_a_searchable_hex_sid_when_calling_toLdap()
    {
        $this->toLdap($this->sidString)->shouldBeEqualTo($this->sidHex);
    }

    function it_should_return_a_string_sid_when_calling_fromLdap()
    {
        $this->fromLdap(pack('H*', str_replace('\\', '', $this->sidHex)))->shouldBeEqualTo($this->sidString);
    }
}

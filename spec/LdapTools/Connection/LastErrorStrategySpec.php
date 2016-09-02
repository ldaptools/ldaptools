<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Connection;

use PhpSpec\ObjectBehavior;

class LastErrorStrategySpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedThrough('getInstance', ['foo', true]);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\LastErrorStrategy');
    }
    
    function it_should_get_a_diagnostic_message()
    {
        $this->getDiagnosticMessage()->shouldEqual(null);
    }
}

<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Log;

use PhpSpec\ObjectBehavior;

class EchoLdapLoggerSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Log\EchoLdapLogger');
    }

    function it_should_implement_LdapLoggerInterface()
    {
        $this->shouldImplement('\LdapTools\Log\LdapLoggerInterface');
    }
}

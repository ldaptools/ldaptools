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

use PhpSpec\ObjectBehavior;

class DnsSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Utilities\Dns');
    }

    function it_should_get_a_dns_record()
    {
        $this->getRecord('127.0.0.1')->shouldBeArray();
    }
}

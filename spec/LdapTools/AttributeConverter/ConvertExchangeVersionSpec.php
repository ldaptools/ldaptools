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

class ConvertExchangeVersionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertExchangeVersion');
    }
    
    function it_should_convert_an_exchange_version_from_ldap_to_a_friendly_readable_name()
    {
        $this->fromLdap('Version 15.0 (Build 30516.32)')->shouldBeEqualTo('Exchange Server 2013 RTM (Build 15.00.0516.032)');
        $this->fromLdap('Version 15.0 (Build 31178.04)')->shouldBeEqualTo('Exchange Server 2013 CU12 (Build 15.00.1178.004)');
    }
}

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

use LdapTools\Connection\AD\ExchangeRoleTypes;
use PhpSpec\ObjectBehavior;

class ConvertExchangeRolesSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertExchangeRoles');
    }

    function it_should_convert_an_exchange_version_number_to_the_friendly_version_name()
    {
        // Check for single role value returns...
        foreach (ExchangeRoleTypes::ROLES as $role) {
            $this->fromLdap($role)->shouldBeEqualTo([ExchangeRoleTypes::NAMES[$role]]);
        }
        
        $role = 0;
        foreach (ExchangeRoleTypes::ROLES as $value) {
            $role += $value; 
        }
        
        // Check for all role values combined together...
        $this->fromLdap($role)->shouldBeEqualTo(array_values(ExchangeRoleTypes::NAMES));
    }
}

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

use LdapTools\DomainConfiguration;
use PhpSpec\ObjectBehavior;

class ADBindUserStrategySpec extends ObjectBehavior
{
    function let()
    {
        $config = new DomainConfiguration('example.local');
        $this->beConstructedThrough('getInstance', [ $config ]);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\AD\ADBindUserStrategy');
    }

    function it_should_return_a_username_in_UPN_format_by_default()
    {
        $this->getUsername('foo')->shouldBeEqualTo('foo@example.local');
    }

    function it_should_ignore_a_GUID_and_enclose_it_with_curly_braces()
    {
        $guid = '8227ab9b-b307-45eb-a50c-6f6cb3946318';
        $this->getUsername($guid)->shouldBeEqualTo('{'.$guid.'}');
    }

    function it_should_not_modify_a_SID_username()
    {
        $sid = 'S-1-5-21-1004336348-1177238915-682003330-512';
        $this->getUsername($sid)->shouldBeEqualTo($sid);
    }

    function it_should_not_modify_a_distinguished_name_username()
    {
        $dn = 'CN=Foo,DC=example,DC=com';
        $this->getUsername($dn)->shouldBeEqualTo($dn);
    }

    function it_should_not_modify_a_username_in_UPN_form()
    {
        $user = 'foo@bar.com';
        $this->getUsername($user)->shouldBeEqualTo($user);
    }

    function it_should_use_a_custom_format_definition()
    {
        $config = new DomainConfiguration('example.local');
        $config->setBindFormat('%username%');
        $this->beConstructedThrough('getInstance', [ $config ]);

        $this->getUsername('foo')->shouldBeEqualTo('foo');
    }
}

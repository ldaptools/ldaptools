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

use LdapTools\Connection\LdapConnection;
use LdapTools\DomainConfiguration;
use PhpSpec\ObjectBehavior;

class BindUserStrategySpec extends ObjectBehavior
{
    function let()
    {
        $config = new DomainConfiguration('example.local');
        $config->setLdapType(LdapConnection::TYPE_OPENLDAP);
        $this->beConstructedThrough('getInstance', [ $config ]);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Connection\BindUserStrategy');
    }

    function it_should_return_the_username_by_default()
    {
        $this->getUsername('foo')->shouldBeEqualTo('foo');
    }

    function it_should_use_a_custom_format_definition()
    {
        $config = new DomainConfiguration('example.local');
        $config->setLdapType(LdapConnection::TYPE_OPENLDAP);
        $config->setBindFormat('CN=%username%,DC=foo,DC=bar');
        $this->beConstructedThrough('getInstance', [ $config ]);

        $this->getUsername('foo')->shouldBeEqualTo('CN=foo,DC=foo,DC=bar');
    }
}

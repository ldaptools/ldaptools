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

use LdapTools\Connection\LdapConnectionInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertNameToDnSpec extends ObjectBehavior
{
    protected $entry = [
        'count' => 1,
        0 => [
            'count' => 2,
            'dn' => "CN=Foo,DC=bar,DC=foo",
        ],
    ];

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertNameToDn');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_convert_a_dn_to_a_normal_name()
    {
        $this->fromLdap('cn=Foo,dc=bar,dc=foo')->shouldBeEqualTo('Foo');
    }

    function it_should_convert_a_name_back_to_a_dn(LdapConnectionInterface $connection)
    {
        $connection->getLdapType()->willReturn('ad');
        $connection->search('(&(objectClass=\62\61\72)(cn=\46\6f\6f))', ['distinguishedName'], null, 'subtree', null)->willReturn($this->entry);
        $this->setOptions(['foo' => [
            'attribute' => 'cn',
            'filter' => [
                'objectClass' => 'bar'
            ],
        ]]);
        $this->setAttribute('foo');
        $this->setLdapConnection($connection);
        $this->toLdap('Foo');
    }

    function it_should_convert_a_dn_into_its_common_name()
    {
        $this->fromLdap('cn=Foo\,\=bar,dc=foo,dc=bar')->shouldBeEqualTo('Foo,=bar');
    }
}

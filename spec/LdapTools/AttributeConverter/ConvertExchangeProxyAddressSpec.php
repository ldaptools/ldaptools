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

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\BatchModify\Batch;
use LdapTools\DomainConfiguration;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertExchangeProxyAddressSpec extends ObjectBehavior
{
    protected $expectedResult = [
        'count' => 1,
        0 => [
            'proxyAddresses' => [
                'count' => 3,
                0 => "smtp:foo@foo.bar",
                1 => "SMTP:Foo.Bar@foo.bar",
                2 => "x400:foo",
            ],
            'count' => 2,
            'dn' => "CN=foo,DC=foo,DC=bar",
        ],
    ];

    function let(\LdapTools\Connection\LdapConnectionInterface $connection)
    {
        $options = [
            'addressType' => [
                'exchangeSmtpAddress' => 'smtp',
                'exchangeDefaultSmtpAddress' => 'smtp',
            ],
            'default' => [
                'exchangeDefaultSmtpAddress'
            ],
        ];
        $connection->getConfig()->willReturn(new DomainConfiguration('foo.bar'));
        $this->setOptions($options);
        $this->setLdapConnection($connection);
        $this->setDn('cn=foo,dc=foo,dc=bar');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertExchangeProxyAddress');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_convert_an_array_of_addresses_to_an_array_of_specific_address_types()
    {
        $smtp = ['foo@bar.com','foo.bar@foo.com'];
        $this->setAttribute('exchangeSmtpAddress');
        // 514 represents a "normal account" with the disabled bit set.
        $this->fromLdap(['smtp:foo@bar.com','SMTP:foo.bar@foo.com','x400:foo'])->shouldBeEqualTo($smtp);
        $this->setAttribute('exchangeDefaultSmtpAddress');
        $this->fromLdap(['smtp:foo@bar.com','SMTP:foo.bar@foo.com','x400:foo'])->shouldBeEqualTo(['foo.bar@foo.com']);
    }

    function it_should_return_the_default_address_for_a_specific_type_of_address_if_requested()
    {
        $this->setAttribute('exchangeDefaultSmtpAddress');
        $this->fromLdap(['smtp:foo@bar.com','SMTP:foo.bar@foo.com','x400:foo'])->shouldBeEqualTo(['foo.bar@foo.com']);
    }

    function it_should_return_an_empty_string_for_the_default_address_if_it_cannot_be_found()
    {
        $this->setAttribute('exchangeDefaultSmtpAddress');
        $this->fromLdap(['x400:foo'])->shouldBeEqualTo('');
    }

    function it_should_aggregate_values_when_converting_an_array_of_addresses_to_ldap_on_creation()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->setAttribute('exchangeSmtpAddress');
        $this->toLdap(['foo@bar.com','foo.bar@foo.com'])->shouldBeEqualTo(['smtp:foo@bar.com', 'smtp:foo.bar@foo.com']);
        $this->setAttribute('exchangeDefaultSmtpAddress');
        $this->toLdap(['foo@bar.com'])->shouldBeEqualTo(['SMTP:foo@bar.com', 'smtp:foo.bar@foo.com']);
        $this->toLdap(['foo2@bar.com'])->shouldBeEqualTo(['smtp:foo@bar.com', 'smtp:foo.bar@foo.com','SMTP:foo2@bar.com']);
    }

    function it_should_aggregate_values_when_converting_an_array_of_addresses_to_ldap_on_modification($connection)
    {
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(objectClass=*))'
                && $operation->getAttributes() == ['proxyAddresses']
                && $operation->getBaseDn() == 'cn=foo,dc=foo,dc=bar';
        }))->willReturn($this->expectedResult);
        $addresses = [
            "smtp:foo@foo.bar",
            "SMTP:Foo.Bar@foo.bar",
            "x400:foo",
            "smtp:chad@sikorra.com",
        ];

        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setAttribute('exchangeSmtpAddress');
        $this->setBatch(new Batch(Batch::TYPE['ADD'],'exchangeSmtpAddress',['chad@sikorra.com']));
        $this->toLdap(['chad@sikorra.com'])->shouldBeEqualTo($addresses);
        $this->getBatch()->getModType()->shouldBeEqualTo(Batch::TYPE['REPLACE']);
        unset($addresses[0]);
        $this->setBatch(new Batch(Batch::TYPE['REMOVE'],'exchangeSmtpAddress',['foo@foo.bar']));
        $this->toLdap(['foo@foo.bar'])->shouldBeEqualTo($addresses);
        $this->setAttribute('exchangeDefaultSmtpAddress');
        $this->setBatch(new Batch(Batch::TYPE['ADD'],'exchangeDefaultSmtpAddress',['foo@foo.bar']));
        $this->toLdap(['FooBar@foo'])->shouldBeLike([
            1 => "smtp:Foo.Bar@foo.bar",
            2 => "x400:foo",
            3 => "smtp:chad@sikorra.com",
            4 => "SMTP:FooBar@foo",
        ]);
    }

    function it_should_not_aggregate_values_on_a_search()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_FROM);
        $this->getShouldAggregateValues()->shouldBeEqualTo(false);
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->getShouldAggregateValues()->shouldBeEqualTo(false);
    }
}

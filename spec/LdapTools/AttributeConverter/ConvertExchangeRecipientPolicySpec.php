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
use LdapTools\AttributeConverter\ConvertExchangeRecipientPolicy;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\DomainConfiguration;
use LdapTools\Object\LdapObject;
use LdapTools\Security\GUID;
use LdapTools\Utilities\LdapUtilities;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertExchangeRecipientPolicySpec extends ObjectBehavior
{
    function let(LdapConnectionInterface $connection)
    {
        $this->setLdapConnection($connection);
        $this->setAttribute('recipientPolicies');
        $this->setOptions([
            'recipientPolicies' => [
                'base_dn' => '"%_configurationnamingcontext_%',
                'attribute' => 'cn',
                'select' => 'objectGuid',
                'filter' => [
                    'objectClass' => 'msExchRecipientPolicy'
                ]
            ]
        ]);
        $connection->getConfig()->willReturn(new DomainConfiguration('foo.bar'));
        $connection->getRootDse()->willReturn(new LdapObject(['configurationNamingContext' => 'cn=config,dc=foo,dc=bar']));
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(ConvertExchangeRecipientPolicy::class);
    }

    function it_should_convert_recipient_policies_going_to_ldap_on_creation($connection)
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $guid = new GUID(LdapUtilities::uuid4());

        $connection->execute(Argument::any())->willReturn([
            'count' => 1,
            0 => [
                'objectGuid' => [
                    'count' => 1,
                    0 => $guid->toBinary(),
                ],
                'count' => 1,
                'dn' => "CN=foo,DC=foo,DC=bar",
            ],
        ]);

        $this->toLdap(['Default Policy'])->shouldBeEqualTo([ConvertExchangeRecipientPolicy::AUTO_UPDATE, $guid->toString()]);
    }

    function it_should_convert_recipient_policies_going_to_ldap_on_modification_or_searching($connection)
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $guid = new GUID(LdapUtilities::uuid4());

        $connection->execute(Argument::any())->willReturn([
            'count' => 1,
            0 => [
                'objectGuid' => [
                    'count' => 1,
                    0 => $guid->toBinary(),
                ],
                'count' => 1,
                'dn' => "CN=foo,DC=foo,DC=bar",
            ],
        ]);

        $this->toLdap(['Foo'])->shouldBeEqualTo([$guid->toString()]);
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->toLdap(['Foo'])->shouldBeEqualTo([$guid->toString()]);
    }

    function it_should_convert_recipient_policies_to_names_when_querying_from_ldap($connection)
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_FROM);
        $guid = new GUID(LdapUtilities::uuid4());

        $connection->execute(Argument::any())->willReturn([
            'count' => 1,
            0 => [
                'cn' => [
                    'count' => 1,
                    0 => 'Default Policy',
                ],
                'count' => 1,
                'dn' => "CN=foo,DC=foo,DC=bar",
            ],
        ]);

        $this->fromLdap([ConvertExchangeRecipientPolicy::AUTO_UPDATE, $guid->toString()])->shouldBeEqualTo(['Default Policy']);
    }
}

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
use LdapTools\Object\LdapObject;
use LdapTools\Security\GUID;
use LdapTools\Utilities\GPOLink;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertGPLinkSpec extends ObjectBehavior
{
    protected $expectedDNResult = [
        'count' => 2,
        0 => [
            'distinguishedname' => [
                'count' => 1,
                0 => "cn={8E1F85EB-4882-4920-88A5-CF52F31D8D31},cn=policies,cn=system,DC=example,DC=local",
            ],
            'count' => 2,
            'dn' => "cn={8E1F85EB-4882-4920-88A5-CF52F31D8D31},cn=policies,cn=system,DC=example,DC=local",
        ],
        1 => [
            'distinguishedname' => [
                'count' => 1,
                0 => "cn={B261DB28-5EA3-4D69-B79D-5C22E8018183},cn=policies,cn=system,DC=example,DC=local",
            ],
            'count' => 2,
            'dn' => "cn={B261DB28-5EA3-4D69-B79D-5C22E8018183},cn=policies,cn=system,DC=example,DC=local",
        ],
    ];

    protected $expectedDisplayResult = [
        'count' => 2,
        0 => [
            'displayname' => [
                'count' => 1,
                0 => "Foo",
            ],
            'objectguid' => [
                'count' => 1,
                0 => "",
            ],
            'count' => 2,
            'dn' => "cn={8E1F85EB-4882-4920-88A5-CF52F31D8D31},cn=policies,cn=system,DC=example,DC=local",
        ],
        1 => [
            'displayname' => [
                'count' => 1,
                0 => "Bar",
            ],
            'objectguid' => [
                'count' => 1,
                0 => "",
            ],
            'count' => 2,
            'dn' => "cn={B261DB28-5EA3-4D69-B79D-5C22E8018183},cn=policies,cn=system,DC=example,DC=local",
        ],
    ];

    protected $expectedSingleDisplayResult = [
        'count' => 1,
        0 => [
            'displayname' => [
                'count' => 1,
                0 => "Foo",
            ],
            'objectguid' => [
                'count' => 1,
                0 => "",
            ],
            'count' => 2,
            'dn' => "cn={8E1F85EB-4882-4920-88A5-CF52F31D8D31},cn=policies,cn=system,DC=example,DC=local",
        ],
    ];

    protected $expectedCurrentValueResult = [
        'count' => 1,
        0 => [
            'gplink' => [
                'count' => 1,
                0 => "",
            ],
            'count' => 2,
            'dn' => "ou=foo,dc=foo,dc=bar",
        ],
    ];

    protected $gPLinks = ['[LDAP://cn={8E1F85EB-4882-4920-88A5-CF52F31D8D31},cn=policies,cn=system,DC=example,DC=local;0]','[LDAP://cn={B261DB28-5EA3-4D69-B79D-5C22E8018183},cn=policies,cn=system,DC=example,DC=local;2]'];

    protected $expectedGPOLinks = [];

    function let(\LdapTools\Connection\LdapConnectionInterface $connection)
    {
        $this->expectedCurrentValueResult[0]['gplink'][0] = implode('', $this->gPLinks);
        $this->expectedDisplayResult[0]['objectguid'][0] = (new GUID('8E1F85EB-4882-4920-88A5-CF52F31D8D31'))->toBinary();
        $this->expectedDisplayResult[1]['objectguid'][0] = (new GUID('B261DB28-5EA3-4D69-B79D-5C22E8018183'))->toBinary();
        $this->expectedSingleDisplayResult[0]['objectguid'][0] = (new GUID('8E1F85EB-4882-4920-88A5-CF52F31D8D31'))->toBinary();

        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(|(distinguishedName=cn={B261DB28-5EA3-4D69-B79D-5C22E8018183},cn=policies,cn=system,DC=example,DC=local)(distinguishedName=cn={8E1F85EB-4882-4920-88A5-CF52F31D8D31},cn=policies,cn=system,DC=example,DC=local)))';
        }))->willReturn($this->expectedDisplayResult);
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(|(distinguishedName=cn={8E1F85EB-4882-4920-88A5-CF52F31D8D31},cn=policies,cn=system,DC=example,DC=local)))';
        }))->willReturn($this->expectedSingleDisplayResult);
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(|(displayName=Foo)(displayName=Bar)))';
        }))->willReturn($this->expectedDNResult);
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(objectClass=*))'
                && $operation->getBaseDn() == 'ou=foo,dc=foo,dc=bar';
        }))->willReturn($this->expectedCurrentValueResult);

        $connection->getConfig()->willReturn(new DomainConfiguration('foo.bar'));
        $this->setLdapConnection($connection);
        $this->setDn('ou=foo,dc=foo,dc=bar');

        $this->expectedGPOLinks = [
            new GPOLink(
                new LdapObject([
                    'dn' => 'cn={B261DB28-5EA3-4D69-B79D-5C22E8018183},cn=policies,cn=system,DC=example,DC=local',
                    'guid' => 'b261db28-5ea3-4d69-b79d-5c22e8018183',
                    'name' => 'Bar',
                ]),
                2
            ),
            new GPOLink(
                new LdapObject([
                    'dn' => 'cn={8E1F85EB-4882-4920-88A5-CF52F31D8D31},cn=policies,cn=system,DC=example,DC=local',
                    'guid' => '8e1f85eb-4882-4920-88a5-cf52f31d8d31',
                    'name' => 'Foo',
                ]),
                0
            ),
        ];
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertGPLink');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_convert_a_gPLink_string_to_an_array_of_GPOLink_objects()
    {
        $this->fromLdap([implode('', $this->gPLinks)])->shouldBeLike($this->expectedGPOLinks);
        $this->fromLdap($this->gPLinks[0])->shouldBeLike([$this->expectedGPOLinks[1]]);
    }

    function it_should_convert_an_empty_or_unparsable_gPLink_string_to_an_empty_array()
    {
        $this->fromLdap([''])->shouldBeEqualTo([]);
    }

    function it_should_convert_an_array_of_GPOLinks_to_a_bracket_encased_dn_string_form_for_ldap()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->toLdap($this->expectedGPOLinks)->shouldBeEqualTo(implode('', $this->gPLinks));
    }

    function it_should_aggregate_values_when_converting_an_array_of_GPOLinks_to_ldap_on_modification($connection)
    {
        // This is starting to get really pug fugly...
        $fooBarGPLink = '[LDAP://cn={8E1F85EB-4882-4920-88A5-CF52F31D8D32},cn=policies,cn=system,DC=example,DC=local;0]';
        $fooBarResult = [
            0 => [
                'distinguishedname' => [
                    'count' => 1,
                    0 => "cn={8E1F85EB-4882-4920-88A5-CF52F31D8D32},cn=policies,cn=system,DC=example,DC=local",
                ],
                'dn' => "cn={8E1F85EB-4882-4920-88A5-CF52F31D8D32},cn=policies,cn=system,DC=example,DC=local",
            ],
            'count' => 1,
        ];
        $anotherResult = $this->expectedDNResult;
        $anotherResult = array_reverse($anotherResult);
        $anotherResult[1] = [
            'distinguishedname' => [
                'count' => 1,
                0 => "cn={8E1F85EB-4882-4920-88A5-CF52F31D8D32},cn=policies,cn=system,DC=example,DC=local",
            ],
            'count' => 1,
            'dn' => "cn={8E1F85EB-4882-4920-88A5-CF52F31D8D32},cn=policies,cn=system,DC=example,DC=local",
        ];

        // The search for the DN info on 'FooBar'
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(&(objectClass=groupPolicyContainer))(displayName=FooBar))';
        }))->willReturn($fooBarResult);

        // We should add the GPO to the end, which in the case of the string result ends up at the front...
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setBatch(new Batch(Batch::TYPE['ADD'],'gpoLinks',[new GPOLink('FooBar')]));
        $this->toLdap([new GPOLink('FooBar')])->shouldBeEqualTo($fooBarGPLink.implode('', $this->gPLinks));

        // The search for the DN info on 'Foo'
        $fooResult = $this->expectedDNResult;
        unset($fooResult[1]);
        $fooResult['count'] = 1;
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(&(objectClass=groupPolicyContainer))(displayName=Foo))';
        }))->willReturn($fooResult);

        // This should remove the 'Foo' GPO link...
        $this->setBatch(new Batch(Batch::TYPE['REMOVE'],'gpoLinks',[new GPOLink('Foo')]));
        $this->toLdap([new GPOLink('Foo')])->shouldBeEqualTo($fooBarGPLink.$this->gPLinks[1]);

        // The search for the DN info on 'Bar'
        $barResult = $this->expectedDNResult;
        $barResult[0] = $barResult[1];
        $barResult['count'] = 1;
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(&(objectClass=groupPolicyContainer))(displayName=Bar))';
        }))->willReturn($barResult);

        // This should replace all GPO links, reversing the order of the last two...
        $this->setBatch(new Batch(Batch::TYPE['REPLACE'],'gpoLinks', [new GPOLink('Foo'), new GPOLink('Bar', 2), new GPOLink('FooBar')]));
        $this->toLdap([new GPOLink('Foo'), new GPOLink('Bar', 2), new GPOLink('FooBar')])->shouldBeEqualTo($fooBarGPLink.implode('', array_reverse($this->gPLinks)));
    }

    function it_should_not_aggregate_values_on_a_search()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_FROM);
        $this->getShouldAggregateValues()->shouldBeEqualTo(false);
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->getShouldAggregateValues()->shouldBeEqualTo(false);
    }

    function it_should_return_a_single_space_if_all_GPO_links_are_removed_on_modification()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setBatch(new Batch(Batch::TYPE['REMOVE'],'gpoLinks', $this->expectedGPOLinks));
        $this->toLdap($this->expectedGPOLinks)->shouldBeEqualTo(' ');
    }

    function it_should_early_return_an_empty_string_on_a_reset_and_not_modify_the_batch_type()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setBatch(new Batch(Batch::TYPE['REMOVE_ALL'],'gpoLinks'));
        $this->toLdap($this->expectedGPOLinks)->shouldBeEqualTo('');
        $this->getBatch()->isTypeRemoveAll()->shouldEqual(true);
    }
}

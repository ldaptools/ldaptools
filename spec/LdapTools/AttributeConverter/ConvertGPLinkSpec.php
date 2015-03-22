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
use LdapTools\Connection\LdapConnectionInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertGPLinkSpec extends ObjectBehavior
{
    const FOO_DN_HEX = '\63\6e\3d\7b\38\45\31\46\38\35\45\42\2d\34\38\38\32\2d\34\39\32\30\2d\38\38\41\35\2d\43\46\35\32\46\33\31\44\38\44\33\31\7d\2c\63\6e\3d\70\6f\6c\69\63\69\65\73\2c\63\6e\3d\73\79\73\74\65\6d\2c\44\43\3d\65\78\61\6d\70\6c\65\2c\44\43\3d\6c\6f\63\61\6c';
    const BAR_DN_HEX = '\63\6e\3d\7b\42\32\36\31\44\42\32\38\2d\35\45\41\33\2d\34\44\36\39\2d\42\37\39\44\2d\35\43\32\32\45\38\30\31\38\31\38\33\7d\2c\63\6e\3d\70\6f\6c\69\63\69\65\73\2c\63\6e\3d\73\79\73\74\65\6d\2c\44\43\3d\65\78\61\6d\70\6c\65\2c\44\43\3d\6c\6f\63\61\6c';
    const FOOBAR_DN_HEX = '\63\6e\3d\7b\38\45\31\46\38\35\45\42\2d\34\38\38\32\2d\34\39\32\30\2d\38\38\41\35\2d\43\46\35\32\46\33\31\44\38\44\33\32\7d\2c\63\6e\3d\70\6f\6c\69\63\69\65\73\2c\63\6e\3d\73\79\73\74\65\6d\2c\44\43\3d\65\78\61\6d\70\6c\65\2c\44\43\3d\6c\6f\63\61\6c';

    protected $connection;

    protected $expectedCurrentValueSearch = [
        '(&(distinguishedName=\6f\75\3d\66\6f\6f\2c\64\63\3d\66\6f\6f\2c\64\63\3d\62\61\72))',
        ['gPLink'],
        null,
        "subtree",
        null,
    ];

    protected $expectedDNSearch = [
        '(&(|(displayName=\46\6f\6f)(displayName=\42\61\72)))',
        ['distinguishedname'],
        null,
        "subtree",
        null,
    ];

    protected $expectedDisplaySearch = [
        '(&(|(distinguishedName='.self::FOO_DN_HEX.')(distinguishedName='.self::BAR_DN_HEX.')))',
        ['displayname'],
        null,
        "subtree",
        null,
    ];

    protected $expectedSingleDisplaySearch = [
        '(&(|(distinguishedName='.self::FOO_DN_HEX.')))',
        ['displayname'],
        null,
        "subtree",
        null,
    ];

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
            'count' => 2,
            'dn' => "cn={8E1F85EB-4882-4920-88A5-CF52F31D8D31},cn=policies,cn=system,DC=example,DC=local",
        ],
        1 => [
            'displayname' => [
                'count' => 1,
                0 => "Bar",
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

    protected $gPLinks = ['[LDAP://cn={8E1F85EB-4882-4920-88A5-CF52F31D8D31},cn=policies,cn=system,DC=example,DC=local;0]','[LDAP://cn={B261DB28-5EA3-4D69-B79D-5C22E8018183},cn=policies,cn=system,DC=example,DC=local;0]'];
    protected $foobarGPLink = '[LDAP://cn={8E1F85EB-4882-4920-88A5-CF52F31D8D32},cn=policies,cn=system,DC=example,DC=local;0]';

    function let(LdapConnectionInterface $connection)
    {
        $this->expectedCurrentValueResult[0]['gplink'][0] = implode('', $this->gPLinks);
        $this->connection = $connection;
        $this->setLdapConnection($connection);
        $this->setDn('ou=foo,dc=foo,dc=bar');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\AttributeConverter\ConvertGPLink');
    }

    function it_should_implement_AttributeConverterInterface()
    {
        $this->shouldImplement('\LdapTools\AttributeConverter\AttributeConverterInterface');
    }

    function it_should_convert_a_gPLink_string_to_an_array_of_GPO_names()
    {
        $this->connection->getLdapType()->willReturn('ad');

        $this->connection->search(...$this->expectedDisplaySearch)->willReturn($this->expectedDisplayResult);
        $this->fromLdap([implode('', $this->gPLinks)])->shouldBeEqualTo(['Foo','Bar']);

        $this->connection->search(...$this->expectedSingleDisplaySearch)->willReturn($this->expectedSingleDisplayResult);
        $this->fromLdap($this->gPLinks[0])->shouldBeEqualTo(['Foo']);
    }

    function it_should_convert_an_array_of_GPO_names_to_a_bracket_encased_dn_string_form_for_ldap()
    {
        $this->connection->getLdapType()->willReturn('ad');
        $this->connection->search(...$this->expectedDNSearch)->willReturn($this->expectedDNResult);

        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->toLdap(['Foo','Bar'])->shouldBeEqualTo(implode('', $this->gPLinks));
    }

    function it_should_aggregate_values_when_converting_an_array_of_GPO_names_to_ldap_on_modification()
    {
        // This is starting to get really pug fugly...
        $this->connection->getLdapType()->willReturn('ad');
        $this->connection->search(...$this->expectedCurrentValueSearch)->willReturn($this->expectedCurrentValueResult);
        $this->connection->search(...$this->expectedDNSearch)->willReturn($this->expectedDNResult);
        $this->connection->search(...$this->expectedDisplaySearch)->willReturn($this->expectedDisplayResult);
        $search = $this->expectedDNSearch;
        $search[0] = '(&(|(displayName=\46\6f\6f)(displayName=\42\61\72)(displayName=\46\6f\6f\42\61\72)))';
        $anotherSearch = $this->expectedDNSearch;
        $anotherSearch[0] = '(&(|(displayName=\42\61\72)(displayName=\46\6f\6f\42\61\72)))';
        $result = $this->expectedDNResult;
        $result['count'] = '3';
        $result[] = [
            'distinguishedname' => [
                'count' => 1,
                0 => "cn={8E1F85EB-4882-4920-88A5-CF52F31D8D32},cn=policies,cn=system,DC=example,DC=local",
            ],
            'count' => 2,
            'dn' => "cn={8E1F85EB-4882-4920-88A5-CF52F31D8D32},cn=policies,cn=system,DC=example,DC=local",
        ];
        $anotherResult = $this->expectedDNResult;
        $anotherResult = array_reverse($anotherResult);
        $anotherResult[1] = [
            'distinguishedname' => [
                'count' => 1,
                0 => "cn={8E1F85EB-4882-4920-88A5-CF52F31D8D32},cn=policies,cn=system,DC=example,DC=local",
            ],
            'count' => 2,
            'dn' => "cn={8E1F85EB-4882-4920-88A5-CF52F31D8D32},cn=policies,cn=system,DC=example,DC=local",
        ];
        $this->connection->search(...$search)->willReturn($result);
        $this->connection->search(...$anotherSearch)->willReturn($anotherResult);

        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setBatch(new Batch(Batch::TYPE['ADD'],'gpoLinks',['FooBar']));
        $this->toLdap(['FooBar'])->shouldBeEqualTo(implode('', $this->gPLinks).$this->foobarGPLink);
        $this->getBatch()->getModType()->shouldBeEqualTo(Batch::TYPE['REPLACE']);
        $this->setBatch(new Batch(Batch::TYPE['REMOVE'],'gpoLinks',['Foo']));
        $this->toLdap(['Foo'])->shouldBeEqualTo($this->gPLinks[1].$this->foobarGPLink);
        $this->setBatch(new Batch(Batch::TYPE['REPLACE'],'gpoLinks',['Foo', 'Bar', 'FooBar']));
        $this->toLdap(['Foo', 'Bar', 'FooBar'])->shouldBeEqualTo(implode('', $this->gPLinks).$this->foobarGPLink);
    }

    function it_should_not_aggregate_values_on_a_search()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_FROM);
        $this->getShouldAggregateValues()->shouldBeEqualTo(false);
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->getShouldAggregateValues()->shouldBeEqualTo(false);
    }
}

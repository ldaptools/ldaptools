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
use LdapTools\Operation\QueryOperation;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ConvertGPLinkSpec extends ObjectBehavior
{
    protected $connection;

    /**
     * @var QueryOperation
     */
    protected $expectedCurrentValueSearch;

    /**
     * @var QueryOperation
     */
    protected $expectedDNSearch;

    /**
     * @var QueryOperation
     */
    protected $expectedDisplaySearch;

    /**
     * @var QueryOperation
     */
    protected $expectedSingleDisplaySearch;

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

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function let($connection)
    {
        $this->expectedCurrentValueResult[0]['gplink'][0] = implode('', $this->gPLinks);
        
        $connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(|(distinguishedName=cn={8E1F85EB-4882-4920-88A5-CF52F31D8D31},cn=policies,cn=system,DC=example,DC=local)(distinguishedName=cn={B261DB28-5EA3-4D69-B79D-5C22E8018183},cn=policies,cn=system,DC=example,DC=local)))';
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
        $this->fromLdap([implode('', $this->gPLinks)])->shouldBeEqualTo(['Foo','Bar']);
        $this->fromLdap($this->gPLinks[0])->shouldBeEqualTo(['Foo']);
    }

    function it_should_convert_an_array_of_GPO_names_to_a_bracket_encased_dn_string_form_for_ldap()
    {
        $this->connection->execute($this->expectedDNSearch)->willReturn($this->expectedDNResult);

        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->toLdap(['Foo','Bar'])->shouldBeEqualTo(implode('', $this->gPLinks));
    }

    function it_should_aggregate_values_when_converting_an_array_of_GPO_names_to_ldap_on_modification()
    {
        // This is starting to get really pug fugly...
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
        $this->connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(|(displayName=Foo)(displayName=Bar)(displayName=FooBar)))';
        }))->willReturn($result);
        $this->connection->execute(Argument::that(function($operation) {
            return $operation->getFilter() == '(&(|(displayName=Bar)(displayName=FooBar)))';
        }))->willReturn($anotherResult);

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

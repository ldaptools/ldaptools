<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Resolver;

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\Configuration;
use LdapTools\Query\Builder\FilterBuilder;
use LdapTools\Query\Operator\Comparison;
use LdapTools\Query\OperatorCollection;
use LdapTools\Schema\LdapObjectSchema;
use LdapTools\Schema\Parser\SchemaYamlParser;
use PhpSpec\ObjectBehavior;

class OperatorValueResolverSpec extends ObjectBehavior
{
    /**
     * @var LdapObjectSchema
     */
    protected $schema;

    /**
     * @var OperatorCollection
     */
    protected $collection;

    /**
     * @var FilterBuilder
     */
    protected $filter;
    
    function let()
    {
        $this->filter = new FilterBuilder();
        $config = new Configuration();
        $parser = new SchemaYamlParser($config->getSchemaFolder());
        $this->schema = $parser->parse('ad', 'user');
        $this->collection = new OperatorCollection();
        $this->collection->addLdapObjectSchema($this->schema);
        $this->collection->addLdapObjectSchema($parser->parse('ad', 'ou'));
        
        $this->beConstructedThrough('getInstance', [$this->schema, $this->collection, AttributeConverterInterface::TYPE_SEARCH_TO]);    
    }
    
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Resolver\OperatorValueResolver');
    }

    function it_should_convert_attributes_and_values()
    {
        $this->collection->add($this->filter->eq('exchangeHideFromGAL', false));
        $this->collection->add($this->filter->eq('foo', 'bar'));

        $this->toLdap()->toLdapFilter('user')->shouldBeEqualTo('(&(&(objectCategory=person)(objectClass=user))(msExchHideFromAddressLists=FALSE)(foo=bar))');
    }

    function it_should_convert_attributes_and_values_when_the_operators_contain_other_operators()
    {
        $this->collection->add($this->filter->eq('username', 'foo'));
        $this->collection->add($this->filter->bOr($this->filter->eq('created', new \DateTime('2016-01-01', new \DateTimeZone('America/New_York')))));
        
        $this->toLdap()->toLdapFilter('user')->shouldBeEqualTo('(&(&(objectCategory=person)(objectClass=user))(|(whenCreated=20160101000000.0-0500))(sAMAccountName=foo))');
    }

    function it_should_convert_values_for_multiple_aliases()
    {
        $this->collection->add($this->filter->eq('name', 'foo'));
        $this->collection->add($this->filter->eq('user.firstName', 'bar'));
        $this->collection->add($this->filter->eq('ou.description', 'foobar'));
        
        $this->toLdap()->toLdapFilter('user')->shouldEqual('(&(&(objectCategory=person)(objectClass=user))(cn=foo)(givenName=bar))');
        $this->toLdap()->toLdapFilter('ou')->shouldEqual('(&(objectClass=organizationalUnit)(ou=foo)(description=foobar))');
        $this->toLdap()->toLdapFilter()->shouldEqual('(|(&(&(objectCategory=person)(objectClass=user))(cn=foo)(givenName=bar))(&(objectClass=organizationalUnit)(ou=foo)(description=foobar)))');
    }

    function it_should_convert_the_filter_for_a_schema_if_it_uses_mapped_attribute_names_with_converters()
    {
        $this->schema->setFilter(new Comparison('exchangeHideFromGAL','=', true));

        $this->toLdap()->toLdapFilter()->shouldEqual('(|(msExchHideFromAddressLists=TRUE)(objectClass=organizationalUnit))');
    }

    function it_should_convert_values_with_operators_to_filters()
    {
        $this->collection->add($this->filter->eq('user.disabled', false));
        $this->toLdap()->toLdapFilter()->shouldEqual('(|(&(&(objectCategory=person)(objectClass=user))(!(userAccountControl:1.2.840.113556.1.4.803:=2)))(&(objectClass=organizationalUnit)))');
    }

    function it_should_properly_convert_values_when_using_a_multivalued_converter()
    {
        $this->collection->add($this->filter->contains('user.logonWorkstations', ['foo', 'bar']));
        $this->toLdap()->toLdapFilter()->shouldEqual('(|(&(&(objectCategory=person)(objectClass=user))(userWorkstations=*foo,bar*))(&(objectClass=organizationalUnit)))');
    }
}

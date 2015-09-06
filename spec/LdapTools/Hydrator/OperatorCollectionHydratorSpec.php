<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Hydrator;

use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Query\Builder\FilterBuilder;
use LdapTools\Query\OperatorCollection;
use LdapTools\Schema\LdapObjectSchema;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OperatorCollectionHydratorSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Hydrator\OperatorCollectionHydrator');
    }

    function it_should_hydrate_a_operator_collection_to_ldap_without_a_schema_or_connection()
    {
        $filter = new FilterBuilder();

        $collection = new OperatorCollection();
        $collection->add($filter->eq('foo','bar'));
        $collection->add($filter->eq('bar','foo'));

        $this->toLdapFilter($collection)->shouldBeEqualTo('(&(foo=\62\61\72)(bar=\66\6f\6f))');
    }

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     */
    function it_should_hydrate_a_operator_collection_to_ldap_without_a_schema($connection)
    {
        $filter = new FilterBuilder();

        $collection = new OperatorCollection($connection);
        $collection->add($filter->eq('foo','bar'));
        $collection->add($filter->eq('bar','foo'));

        $this->toLdapFilter($collection)->shouldBeEqualTo('(&(foo=\62\61\72)(bar=\66\6f\6f))');
    }

    function it_should_only_wrap_the_filter_in_an_and_when_the_collection_has_more_than_one_object()
    {
        $filter = new FilterBuilder();

        $collection = new OperatorCollection();
        $collection->add($filter->eq('foo','bar'));

        $this->toLdapFilter($collection)->shouldBeEqualTo('(foo=\62\61\72)');
    }

    function it_should_convert_attributes_and_values()
    {
        $filter = new FilterBuilder();
        $schema = new LdapObjectSchema('foo','bar');
        $schema->setAttributeMap(['foo' => 'bar']);
        $schema->setConverterMap(['foo' => 'bool']);

        $collection = new OperatorCollection();
        $collection->add($filter->eq('foo',true));
        $collection->addLdapObjectSchema($schema);

        $this->toLdapFilter($collection)->shouldBeEqualTo('(bar=\54\52\55\45)');
    }

    function it_should_convert_attributes_and_values_when_the_operators_contain_other_operators()
    {
        $filter = new FilterBuilder();
        $schema = new LdapObjectSchema('foo','bar');
        $schema->setAttributeMap(['foo' => 'bar', 'bar' => 'foo']);
        $schema->setConverterMap(['foo' => 'bool', 'bar' => 'bool']);

        $collection = new OperatorCollection();
        $collection->add($filter->eq('foo', true));
        $collection->add($filter->bOr($filter->eq('bar', false)));
        $collection->addLdapObjectSchema($schema);

        // Something might not be right here...hex for FALSE seems off? I cannot find any issues though.
        $this->toLdapFilter($collection)->shouldBeEqualTo('(&(|(foo=\46\41\4c\53\45))(bar=\54\52\55\45))');
    }
}

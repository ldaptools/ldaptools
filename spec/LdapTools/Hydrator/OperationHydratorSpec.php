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

use LdapTools\AttributeConverter\AttributeConverterInterface;
use LdapTools\AttributeConverter\EncodeWindowsPassword;
use LdapTools\BatchModify\Batch;
use LdapTools\BatchModify\BatchCollection;
use LdapTools\Configuration;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Connection\LdapControl;
use LdapTools\DomainConfiguration;
use LdapTools\Object\LdapObject;
use LdapTools\Operation\AddOperation;
use LdapTools\Operation\BatchModifyOperation;
use LdapTools\Operation\QueryOperation;
use LdapTools\Operation\RenameOperation;
use LdapTools\Query\Builder\FilterBuilder;
use LdapTools\Query\LdapQuery;
use LdapTools\Query\Operator\Comparison;
use LdapTools\Query\OperatorCollection;
use LdapTools\Schema\LdapObjectSchema;
use LdapTools\Schema\Parser\SchemaYamlParser;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OperationHydratorSpec extends ObjectBehavior
{
    /**
     * @var LdapObjectSchema
     */
    protected $schema;

    /**
     * @var SchemaYamlParser
     */
    protected $parser;

    function let(LdapConnectionInterface $connection, LdapObject $rootdse)
    {
        $domain = new DomainConfiguration('example.local');
        $domain->setUseTls(true);
        $connection->getConfig()->willReturn($domain);
        $connection->getRootDse()->willReturn($rootdse);

        $config = new Configuration();
        $this->parser = new SchemaYamlParser($config->getSchemaFolder());
        $this->schema = $this->parser->parse('ad', 'user');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Hydrator\OperationHydrator');
    }

    function it_should_hydrate_an_add_operation_to_ldap($connection)
    {
        $this->setLdapObjectSchema($this->schema);
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->setLdapConnection($connection);

        $operation = new AddOperation();
        $operation->setAttributes([
            'username' => 'John',
            'password' => '12345',
            'groups' => 'cn=foo,dc=example,dc=local',
        ]);
        $operation->setLocation('ou=employees,dc=example,dc=local');

        $expected = [
            'cn' => "John",
            'displayname' => "John",
            'givenName' => "John",
            'userPrincipalName' => "John@example.local",
            'objectclass' => [
                0 => "top",
                1 => "person",
                2 => "organizationalPerson",
                3 => "user",
            ],
            'sAMAccountName' => "John",
            'unicodePwd' => (new EncodeWindowsPassword())->toLdap('12345'),
            'userAccountControl' => "512",
        ];
        $original1 = clone $operation;
        $original2 = clone $operation;

        $this->hydrateToLdap($operation)->getAttributes()->shouldBeEqualTo($expected);
        $this->hydrateToLdap($original2)->getPostOperations()->shouldHaveCount(1);
        $this->hydrateToLdap($original1)->getDn()->shouldBeEqualTo('cn=John,ou=employees,dc=example,dc=local');
    }

    function it_should_properly_construct_a_multivalued_RDN_for_an_add_operation($connection)
    {
        $this->setLdapObjectSchema((new LdapObjectSchema('foo', 'bar'))->setRdn(['cn', 'mail']));
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->setLdapConnection($connection);
        $operation = (new AddOperation(null, ['cn' => 'foo', 'mail' => 'foo@bar.local']))->setLocation('dc=foo,dc=bar');

        $this->hydrateToLdap($operation)->getDn()->shouldBeEqualTo('cn=foo+mail=foo@bar.local,dc=foo,dc=bar');
    }

    function it_should_throw_an_exception_hydrating_an_add_operation_when_the_RDN_attribute_is_not_specified($connection)
    {
        $this->setLdapObjectSchema(new LdapObjectSchema('foo', 'bar'));
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->setLdapConnection($connection);
        $operation = (new AddOperation(null, ['foo' => 'bar']))->setLocation('dc=foo,dc=bar');

        $this->shouldThrow('LdapTools\Exception\LogicException')->duringHydrateToLdap($operation);
    }

    function it_should_throw_an_exception_hydrating_an_add_operation_when_the_location_is_not_specified($connection)
    {
        $this->setLdapObjectSchema(new LdapObjectSchema('foo', 'bar'));
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->setLdapConnection($connection);
        $operation = (new AddOperation(null, ['name' => 'foo']));

        $this->shouldThrow('LdapTools\Exception\LogicException')->duringHydrateToLdap($operation);
    }

    function it_should_hydrate_a_modify_operation_to_ldap($connection)
    {
        $this->setLdapObjectSchema($this->schema);
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->setLdapConnection($connection);

        $dn = 'cn=foo,dc=example,dc=local';
        $batches = new BatchCollection($dn);
        $batches->add(new Batch(Batch::TYPE['REPLACE'], 'username', 'foobar'));
        $batches->add(new Batch(Batch::TYPE['REMOVE'], 'password', 'bar'));

        $expected = [
            [
                'attrib' => "sAMAccountName",
                'modtype' => 3,
                'values' => [
                    0 => "foobar",
                ],
            ],
            [
                'attrib' => "unicodePwd",
                'modtype' => 2,
                'values' => [
                    0 => (new EncodeWindowsPassword())->toLdap('bar'),
                ],
            ],

        ];
        $operation = new BatchModifyOperation($dn, $batches);
        $this->hydrateToLdap($operation)->getBatchCollection()->getBatchArray()->shouldBeEqualTo($expected);
    }
    
    function it_should_hydrate_a_query_operation_to_ldap_without_a_schema_or_connection()
    {
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $filter = new FilterBuilder();
        $collection = new OperatorCollection();
        $collection->add($filter->eq('foo','bar'));
        $collection->add($filter->eq('bar','foo'));
        $operation = new QueryOperation($collection, ['foo']);

        $this->hydrateToLdap($operation)->getFilter()->shouldBeEqualTo('(&(foo=bar)(bar=foo))');
        $this->hydrateToLdap($operation)->getBaseDn()->shouldBeNull();
    }

    function it_should_hydrate_a_query_operation_to_ldap_with_a_schema($connection)
    {
        $this->schema->setBaseDn('dc=foo,dc=bar');
        $this->setLdapObjectSchema($this->schema);
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->setLdapConnection($connection);
        
        $filter = new FilterBuilder();
        $collection = new OperatorCollection();
        $collection->add($filter->eq('firstName','foo'));
        $collection->add($filter->eq('lastName','bar'));
        $collection->add($filter->eq('exchangeHideFromGAL',false));
        $collection->addLdapObjectSchema($this->schema);
        $operation = new QueryOperation($collection, ['foo']);

        $this->hydrateToLdap($operation)->getFilter()->shouldBeEqualTo(
            '(&(&(objectCategory=person)(objectClass=user))(givenName=foo)(sn=bar)(msExchHideFromAddressLists=FALSE))'
        );
        $this->hydrateToLdap($operation)->getBaseDn()->shouldBeEqualTo('dc=foo,dc=bar');
    }

    function it_should_not_attempt_to_resolve_parameters_for_a_base_dn_for_the_RootDSE(QueryOperation $operation, $connection)
    {
        $operation->getBaseDn()->willReturn('');
        $operation->getAttributes()->willReturn(['foo']);
        $operation->setAttributes(['foo'])->shouldBeCalled();
        $operation->getFilter()->willReturn('(objectClass=*)');

        $connection->getRootDse()->shouldNotBeCalled();
        $operation->setBaseDn(Argument::any())->shouldNotBeCalled();
        $this->hydrateToLdap($operation);
    }
    
    function it_should_attempt_to_resolve_parameters_for_the_base_dn($connection, $rootdse)
    {
        $this->setLdapObjectSchema($this->schema);
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->setLdapConnection($connection);

        $rootDseAttr = [
            'defaultNamingContext' => 'dc=foo,dc=bar', 
            'configurationNamingContext' => 'cn=config,dc=foo,dc=bar'
        ];
        foreach ($rootDseAttr as $name => $value) {
            $rootdse->has($name)->willReturn(true);
            $rootdse->get($name)->willReturn($value);
        }
        
        $operation = new QueryOperation('(foo=bar)');
        $operation->setBaseDn('%_defaultNamingContext_%');

        $this->hydrateToLdap($operation)->getBaseDn()->shouldBeEqualTo($rootDseAttr['defaultNamingContext']);
    }
    
    function it_should_add_controls_on_an_operation_going_to_ldap($connection)
    {
        $operation = new QueryOperation('(foo=bar');

        $this->setLdapObjectSchema($this->schema);
        $this->setLdapConnection($connection);
        
        $this->hydrateToLdap($operation)->getControls()->shouldBeEqualTo([]);

        $control = new LdapControl('foo');
        $this->schema->setControls($control);

        $this->hydrateToLdap($operation)->getControls()->shouldBeEqualTo([$control]);
    }
    
    function it_should_set_whether_paging_is_used_based_off_the_schema($connection)
    {
        $operation = new QueryOperation('(foo=bar');
        
        $this->setLdapObjectSchema($this->schema);
        $this->setLdapConnection($connection);

        $this->hydrateToLdap($operation)->getUsePaging()->shouldBeEqualTo(null);
        
        $this->schema->setUsePaging(true);

        $this->hydrateToLdap($operation)->getUsePaging()->shouldBeEqualTo(true);
    }

    function it_should_set_the_scope_based_off_the_schema($connection)
    {
        $operation = new QueryOperation('(foo=bar)');

        $this->setLdapObjectSchema($this->schema);
        $this->setLdapConnection($connection);

        $this->hydrateToLdap($operation)->getScope()->shouldBeEqualTo('subtree');

        $this->schema->setScope(QueryOperation::SCOPE['ONELEVEL']);

        $this->hydrateToLdap($operation)->getScope()->shouldBeEqualTo('onelevel');
    }
    
    function it_should_select_get_the_correct_attributes_to_select_based_off_the_alias_in_use($connection)
    {
        $this->setLdapConnection($connection);
        $gSchema = $this->parser->parse('ad','group');

        $operators = new OperatorCollection();
        $operators->addLdapObjectSchema($this->schema, 'u');
        $operators->addLdapObjectSchema($gSchema, 'g');
        
        $operationSelect = new QueryOperation($operators);
        $operationDefault = clone $operationSelect;
        $operationSelect->setAttributes(['u.firstName', 'u.lastName', 'name', 'g.description', 'g.members']);
        $operationDefault->setAttributes(['g.name', 'g.description']);
        
        // Only the specifically selected attributes for the alias, in addition to the generic name attribute.
        $this->setLdapObjectSchema($this->schema);
        $this->setAlias('u');
        $this->hydrateToLdap(clone $operationSelect)->getAttributes()->shouldBeEqualTo([
            'givenName', 
            'sn', 
            'cn'
        ]);

        // Only the specifically selected attributes, in addition to the generic name attribute.
        $this->setLdapObjectSchema($gSchema);
        $this->setAlias('g');
        $this->hydrateToLdap(clone $operationSelect)->getAttributes()->shouldBeEqualTo([
            'cn',
            'description',
            'member'
        ]);

        // Check that defaults for a given alias are selected if non specifically are for that alias.
        $this->setLdapObjectSchema($this->schema);
        $this->setAlias('u');
        $this->hydrateToLdap(clone $operationDefault)->getAttributes()->shouldBeEqualTo([
            'cn',
            'givenName',
            'sn',
            'sAMAccountName',
            'mail',
            'distinguishedName',
            'objectGuid',
        ]);
    }
    
    function it_should_correctly_add_attributes_to_select_based_off_aliases_in_the_order_by_selection($connection)
    {
        $this->setLdapConnection($connection);
        $gSchema = $this->parser->parse('ad','group');

        $operators = new OperatorCollection();
        $operators->addLdapObjectSchema($this->schema, 'u');
        $operators->addLdapObjectSchema($gSchema, 'g');

        $operationSelect = new QueryOperation($operators);
        $operationDefault = clone $operationSelect;
        $operationSelect->setAttributes(['u.firstName', 'u.lastName', 'name', 'g.description', 'g.members']);
        $operationDefault->setAttributes(['g.name', 'g.description']);

        $this->setOrderBy([
            'g.sid' => LdapQuery::ORDER['DESC'],
            'u.department' => LdapQuery::ORDER['ASC'],
            'guid' => LdapQuery::ORDER['ASC'],
            'u.lastName' => LdapQuery::ORDER['DESC'],
        ]);
        
        // Any specifically selected attributes + specifically aliased attributes in the order by + generic in the order by.
        // Should also avoid adding duplicates.
        $this->setLdapObjectSchema($this->schema);
        $this->setAlias('u');
        $this->hydrateToLdap(clone $operationSelect)->getAttributes()->shouldBeEqualTo([
            'givenName',
            'sn',
            'cn',
            'department',
            'objectGuid',
        ]);        
    }
    
    function it_should_hydrate_the_ldap_filter_for_a_query_operation_based_off_the_current_alias($connection)
    {
        $this->setLdapConnection($connection);
        $gSchema = $this->parser->parse('ad','group');

        $operators = new OperatorCollection();
        $operators->addLdapObjectSchema($this->schema, 'u');
        $operators->addLdapObjectSchema($gSchema, 'g');
        $operators->add(new Comparison('g.foo', '=', 'bar'));
        $operators->add(new Comparison('u.bar', '=', 'foo'));
        $operation = new QueryOperation($operators);
        
        $this->setAlias('g');
        $this->hydrateToLdap($operation)->getFilter()->shouldBeEqualTo('(&(objectClass=group)(foo=bar))');
    }
    
    function it_should_only_support_an_operation_going_to_ldap()
    {
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->duringHydrateToLdap('foo');
    }

    function it_should_add_rename_operations_when_hydrating_a_batch_operation_with_rdn_changes($connection)
    {
        $this->setLdapObjectSchema($this->schema);
        $this->setOperationType(AttributeConverterInterface::TYPE_MODIFY);
        $this->setLdapConnection($connection);

        $dn = 'cn=foo,dc=example,dc=local';
        $batches = new BatchCollection($dn);
        $batches->add(new Batch(Batch::TYPE['REPLACE'], 'name', 'bar'));
        $batches->add(new Batch(Batch::TYPE['REMOVE'], 'office', 'foo'));
        $operation = (new BatchModifyOperation($dn, $batches));

        $this->hydrateToLdap($operation)->getPostOperations()->shouldBeLike([new RenameOperation($dn, 'cn=bar')]);
    }
}

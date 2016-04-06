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
use LdapTools\Query\Builder\FilterBuilder;
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
     * @var LdapConnectionInterface
     */
    protected $connection;

    /**
     * @var LdapObject
     */
    protected $rootDse;

    /**
     * @param \LdapTools\Connection\LdapConnectionInterface $connection
     * @param \LdapTools\Object\LdapObject $rootdse
     */
    function let($connection, $rootdse)
    {
        $domain = new DomainConfiguration('example.local');
        $domain->setUseTls(true);
        $connection->getConfig()->willReturn($domain);
        $connection->getRootDse()->willReturn($rootdse);
        $this->connection = $connection;
        $this->rootDse = $rootdse;

        $config = new Configuration();
        $parser = new SchemaYamlParser($config->getSchemaFolder());
        $this->schema = $parser->parse('ad', 'user');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Hydrator\OperationHydrator');
    }

    function it_should_hydrate_an_add_operation_to_ldap()
    {
        $this->setLdapObjectSchema($this->schema);
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->setLdapConnection($this->connection);

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

    function it_should_hydrate_a_modify_operation_to_ldap()
    {
        $this->setLdapObjectSchema($this->schema);
        $this->setOperationType(AttributeConverterInterface::TYPE_CREATE);
        $this->setLdapConnection($this->connection);

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

        $this->hydrateToLdap($operation)->getFilter()->toLdapFilter()->shouldBeEqualTo('(&(foo=bar)(bar=foo))');
        $this->hydrateToLdap($operation)->getBaseDn()->shouldBeNull();
    }

    function it_should_hydrate_a_query_operation_to_ldap_with_a_schema()
    {
        $this->schema->setBaseDn('dc=foo,dc=bar');
        $this->setLdapObjectSchema($this->schema);
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->setLdapConnection($this->connection);
        
        $filter = new FilterBuilder();
        $collection = new OperatorCollection();
        $collection->add($filter->eq('firstName','foo'));
        $collection->add($filter->eq('lastName','bar'));
        $collection->add($filter->eq('exchangeHideFromGAL',false));
        $collection->addLdapObjectSchema($this->schema);
        $operation = new QueryOperation($collection, ['foo']);

        $this->hydrateToLdap($operation)->getFilter()->toLdapFilter()->shouldBeEqualTo('(&(givenName=foo)(sn=bar)(msExchHideFromAddressLists=FALSE))');
        $this->hydrateToLdap($operation)->getBaseDn()->shouldBeEqualTo('dc=foo,dc=bar');
    }

    /**
     * @param \LdapTools\Operation\QueryOperation $operation
     */
    function it_should_not_attempt_to_resolve_parameters_for_a_base_dn_for_the_RootDSE($operation)
    {
        $operation->getBaseDn()->willReturn('');
        $operation->setBaseDn(Argument::any())->shouldNotBeCalled();
        
        $this->hydrateToLdap($operation);
    }
    
    function it_should_attempt_to_resolve_parameters_for_the_base_dn()
    {
        $this->setLdapObjectSchema($this->schema);
        $this->setOperationType(AttributeConverterInterface::TYPE_SEARCH_TO);
        $this->setLdapConnection($this->connection);

        $rootDse = [
            'defaultNamingContext' => 'dc=foo,dc=bar', 
            'configurationNamingContext' => 'cn=config,dc=foo,dc=bar'
        ];
        foreach ($rootDse as $name => $value) {
            $this->rootDse->has($name)->willReturn(true);
            $this->rootDse->get($name)->willReturn($value);
        }
        
        $operation = new QueryOperation('(foo=bar)');
        $operation->setBaseDn('%_defaultNamingContext_%');
        
        $this->hydrateToLdap($operation)->getBaseDn()->shouldBeEqualTo($rootDse['defaultNamingContext']);
    }
    
    function it_should_add_controls_on_an_operation_going_to_ldap()
    {
        $operation = new QueryOperation('(foo=bar');

        $this->setLdapObjectSchema($this->schema);
        $this->setLdapConnection($this->connection);
        
        $this->hydrateToLdap($operation)->getControls()->shouldBeEqualTo([]);

        $control = new LdapControl('foo');
        $this->schema->setControls($control);

        $this->hydrateToLdap($operation)->getControls()->shouldBeEqualTo([$control]);
    }
    
    function it_should_set_whether_paging_is_used_based_off_the_schema()
    {
        $operation = new QueryOperation('(foo=bar');
        
        $this->setLdapObjectSchema($this->schema);
        $this->setLdapConnection($this->connection);

        $this->hydrateToLdap($operation)->getUsePaging()->shouldBeEqualTo(null);
        
        $this->schema->setUsePaging(true);

        $this->hydrateToLdap($operation)->getUsePaging()->shouldBeEqualTo(true);
    }

    function it_should_set_the_scope_based_off_the_schema()
    {
        $operation = new QueryOperation('(foo=bar');

        $this->setLdapObjectSchema($this->schema);
        $this->setLdapConnection($this->connection);

        $this->hydrateToLdap($operation)->getScope()->shouldBeEqualTo('subtree');

        $this->schema->setScope(QueryOperation::SCOPE['ONELEVEL']);

        $this->hydrateToLdap($operation)->getScope()->shouldBeEqualTo('onelevel');
    }
    
    function it_should_only_support_an_operation_going_to_ldap()
    {
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->duringHydrateToLdap('foo');
    }
}

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
use LdapTools\DomainConfiguration;
use LdapTools\Operation\AddOperation;
use LdapTools\Operation\BatchModifyOperation;
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
        $original = clone $operation;

        $this->hydrateToLdap($operation)->getAttributes()->shouldBeEqualTo($expected);
        $this->hydrateToLdap($original)->getDn()->shouldBeEqualTo('cn=John,ou=employees,dc=example,dc=local');
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

    function it_should_only_support_an_operation_going_to_ldap()
    {
        $this->shouldThrow('\LdapTools\Exception\InvalidArgumentException')->duringHydrateToLdap('foo');
    }
}

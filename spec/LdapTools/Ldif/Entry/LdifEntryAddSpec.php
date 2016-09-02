<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Ldif\Entry;

use LdapTools\Configuration;
use LdapTools\Connection\LdapConnectionInterface;
use LdapTools\Connection\LdapControl;
use LdapTools\DomainConfiguration;
use LdapTools\Object\LdapObject;
use LdapTools\Schema\Parser\SchemaYamlParser;
use PhpSpec\ObjectBehavior;

class LdifEntryAddSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('dc=foo,dc=bar');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Ldif\Entry\LdifEntryAdd');
    }

    function it_should_implement_LdifEntryInterface()
    {
        $this->shouldImplement('\LdapTools\Ldif\Entry\LdifEntryInterface');
    }

    function it_should_implement_SchemaAwareInterface()
    {
        $this->shouldImplement('\LdapTools\Schema\SchemaAwareInterface');
    }

    function it_should_implement_LdapAwareInterface()
    {
        $this->shouldImplement('\LdapTools\Connection\LdapAwareInterface');
    }

    function it_should_be_able_to_be_contructed_with_attributes()
    {
        $attributes = ['foo' => ['bar'], 'bar' => ['foo']];
        $this->beConstructedWith('dc=foo,dc=bar', $attributes);

        $this->getAttributes()->shouldBeEqualTo($attributes);
    }

    function it_should_set_the_dn()
    {
        $dn = 'foo';
        $this->setDn($dn);
        $this->getDn()->shouldBeEqualTo($dn);
    }

    function it_should_add_a_control()
    {
        $control = new LdapControl('foo');
        $this->addControl($control);

        $this->getControls()->shouldBeEqualTo([$control]);
    }

    function it_should_add_an_attribute()
    {
        $this->addAttribute('name', 'foo');
        $this->getAttributes()->shouldBeEqualTo(['name' => ['foo']]);
    }

    function it_should_set_the_attributes()
    {
        $this->setAttributes(['name' => 'foo']);
        $this->getAttributes()->shouldBeEqualTo(['name' => ['foo']]);
    }

    function it_should_get_an_add_operation()
    {
        $attributes = [
            'givenName' => 'foo',
            'sn' => 'bar',
        ];
        $this->setAttributes($attributes);
        $this->toOperation()->shouldReturnAnInstanceOf('LdapTools\Operation\AddOperation');
        $this->toOperation()->getDn()->shouldBeEqualTo('dc=foo,dc=bar');
        $this->toOperation()->getAttributes()->shouldBeEqualTo(['givenName' => ['foo'],'sn' => ['bar']]);
    }

    function it_should_get_the_ldif_string_representation()
    {
        $attributes = [
            'givenName' => 'foo',
            'sn' => 'bar',
            'description' => ' space',
        ];
        $this->setAttributes($attributes);
        $this->addComment('Add example.');

        $ldif =
             "# Add example.\r\n"
            ."dn: dc=foo,dc=bar\r\n"
            ."changetype: add\r\n"
            ."givenName: foo\r\n"
            ."sn: bar\r\n"
            ."description:: IHNwYWNl\r\n";
        $this->toString()->shouldBeEqualTo($ldif);
    }

    function it_should_get_the_ldif_representation_in_the_context_of_a_type_and_schema(LdapConnectionInterface $connection, LdapObject $rootdse)
    {
        $domain = new DomainConfiguration('example.local');
        $domain->setUseTls(true);
        $connection->getConfig()->willReturn($domain);
        $connection->getRootDse()->willReturn($rootdse);

        $config = new Configuration();
        $parser = new SchemaYamlParser($config->getSchemaFolder());
        $schema = $parser->parse('ad', 'user');

        $this->beConstructedWith(null);
        $this->setLdapObjectSchema($schema);
        $this->setLdapConnection($connection);
        $this->setAttributes(['username' => 'John', 'password' => '12345']);
        $this->setLocation('ou=employees,dc=example,dc=local');

        $ldif =
             "dn: cn=John,ou=employees,dc=example,dc=local\r\n"
            ."changetype: add\r\n"
            ."cn: John\r\n"
            ."displayname: John\r\n"
            ."givenName: John\r\n"
            ."userPrincipalName: John@example.local\r\n"
            ."objectclass: top\r\n"
            ."objectclass: person\r\n"
            ."objectclass: organizationalPerson\r\n"
            ."objectclass: user\r\n"
            ."sAMAccountName: John\r\n"
            ."unicodePwd: IgAxADIAMwA0ADUAIgA=\r\n"
            ."userAccountControl: 512\r\n";

        $this->toString()->shouldBeEqualTo($ldif);
    }

    function it_should_add_a_comment()
    {
        $this->addComment('test')->shouldReturnAnInstanceOf('LdapTools\Ldif\Entry\LdifEntryAdd');
        $this->getComments()->shouldHaveCount(1);

        $this->addComment('foo', 'bar');
        $this->getComments()->shouldHaveCount(3);

        $this->getComments()->shouldBeEqualTo(['test', 'foo', 'bar']);
    }
}

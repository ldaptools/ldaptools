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

class LdifEntryModifySpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('dc=foo,dc=bar');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Ldif\Entry\LdifEntryModify');
    }

    function it_should_implement_LdifEntryInterface()
    {
        $this->shouldImplement('\LdapTools\Ldif\Entry\LdifEntryInterface');
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

    function it_should_get_the_batch_collection()
    {
        $this->getBatchCollection()->shouldReturnAnInstanceOf('LdapTools\BatchModify\BatchCollection');
    }

    function it_should_add_an_attribute_value()
    {
        $this->add('foo', 'bar');
        $this->getBatchCollection()->get(0)->isTypeAdd()->shouldBeEqualTo(true);
    }

    function it_should_delete_an_attribute_value()
    {
        $this->delete('foo', 'bar');
        $this->getBatchCollection()->get(0)->isTypeRemove()->shouldBeEqualTo(true);
    }

    function it_should_replace_an_attribute_value()
    {
        $this->replace('foo', 'bar');
        $this->getBatchCollection()->get(0)->isTypeReplace()->shouldBeEqualTo(true);
    }

    function it_should_reset_an_attribute_value()
    {
        $this->reset('foo');
        $this->getBatchCollection()->get(0)->isTypeRemoveAll()->shouldBeEqualTo(true);
    }

    function it_should_get_an_add_operation()
    {
        $this->toOperation()->shouldReturnAnInstanceOf('LdapTools\Operation\BatchModifyOperation');
        $this->toOperation()->getDn()->shouldBeEqualTo('dc=foo,dc=bar');
    }

    function it_should_get_the_ldif_string_representation()
    {
        $this->addComment("Modify entry example.");
        $this->add('phone', '555-5555');
        $this->reset('sn');
        $this->replace('givenName','foo');
        $this->delete('fax', '123-4567');
        $this->add('address', ['123 fake st', '456 real st']);

        $ldif =
             "# Modify entry example.\r\n"
            . "dn: dc=foo,dc=bar\r\n"
            . "changetype: modify\r\n"
            . "add: phone\r\n"
            . "phone: 555-5555\r\n"
            . "-\r\n"
            . "delete: sn\r\n"
            . "-\r\n"
            . "replace: givenName\r\n"
            . "givenName: foo\r\n"
            . "-\r\n"
            . "delete: fax\r\n"
            . "fax: 123-4567\r\n"
            . "-\r\n"
            . "add: address\r\n"
            . "address: 123 fake st\r\n"
            . "address: 456 real st\r\n"
            . "-\r\n";

        $this->toString()->shouldBeEqualTo($ldif);
    }

    function it_should_get_the_ldif_string_representation_in_the_context_of_a_type_and_a_schema(LdapConnectionInterface $connection, LdapObject $rootdse)
    {
        $domain = new DomainConfiguration('example.local');
        $domain->setUseTls(true);
        $connection->getConfig()->willReturn($domain);
        $connection->getRootDse()->willReturn($rootdse);

        $config = new Configuration();
        $parser = new SchemaYamlParser($config->getSchemaFolder());
        $schema = $parser->parse('ad', 'user');

        $dn = 'cn=foo,dc=foo,dc=bar';
        $this->beConstructedWith($dn);
        $this->setLdapObjectSchema($schema);
        $this->setLdapConnection($connection);

        $this->add('phoneNumber', '555-5555');
        $this->reset('lastName');
        $this->replace('firstName','bar');
        $this->delete('password', 'foo');
        $this->add('password', 'bar');

        $ldif = "dn: $dn\r\n"
            . "changetype: modify\r\n"
            . "add: telephoneNumber\r\n"
            . "telephoneNumber: 555-5555\r\n"
            . "-\r\n"
            . "delete: sn\r\n"
            . "-\r\n"
            . "replace: givenName\r\n"
            . "givenName: bar\r\n"
            . "-\r\n"
            . "delete: unicodePwd\r\n"
            . "unicodePwd: IgBmAG8AbwAiAA==\r\n"
            . "-\r\n"
            . "add: unicodePwd\r\n"
            . "unicodePwd: IgBiAGEAcgAiAA==\r\n"
            . "-\r\n";

        $this->toString()->shouldBeEqualTo($ldif);
    }

    function it_should_add_a_comment()
    {
        $this->addComment('test')->shouldReturnAnInstanceOf('LdapTools\Ldif\Entry\LdifEntryModify');
        $this->getComments()->shouldHaveCount(1);

        $this->addComment('foo', 'bar');
        $this->getComments()->shouldHaveCount(3);

        $this->getComments()->shouldBeEqualTo(['test', 'foo', 'bar']);
    }
}


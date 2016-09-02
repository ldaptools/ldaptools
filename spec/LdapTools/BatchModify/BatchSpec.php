<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\BatchModify;

use LdapTools\Exception\InvalidArgumentException;
use PhpSpec\ObjectBehavior;
use LdapTools\BatchModify\Batch;

class BatchSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith(LDAP_MODIFY_BATCH_REPLACE, 'foo', ['bar']);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\BatchModify\Batch');
    }

    function it_should_have_the_attribute_set_correctly()
    {
        $this->getAttribute()->shouldBeEqualTo('foo');
    }

    function it_should_have_the_values_set_correctly()
    {
        $this->getValues()->shouldBeEqualTo(['bar']);
    }

    function it_should_have_the_correct_mod_type_set()
    {
        $this->getModType()->shouldBeEqualTo(LDAP_MODIFY_BATCH_REPLACE);
    }

    function it_should_set_the_value_correctly()
    {
        $this->setValues(['foo','bar']);
        $this->getValues()->shouldBeEqualTo(['foo','bar']);
        $this->setValues('bar');
        $this->getValues()->shouldBeEqualTo(['bar']);
    }

    function it_should_set_the_mod_type_correctly()
    {
        $this->setModType(LDAP_MODIFY_BATCH_REMOVE_ALL);
        $this->getModType()->shouldBeEqualTo(LDAP_MODIFY_BATCH_REMOVE_ALL);
    }

    function it_should_convert_to_an_array_form()
    {
        $this->toArray()->shouldBeEqualTo([
            'attrib' => 'foo',
            'modtype' => LDAP_MODIFY_BATCH_REPLACE,
            'values' => ['bar']
        ]);
    }

    function it_should_convert_a_remove_all_batch_to_its_correct_array_form()
    {
        $this->setModType(LDAP_MODIFY_BATCH_REMOVE_ALL);
        $this->toArray()->shouldBeEqualTo([
            'attrib' => 'foo',
            'modtype' => LDAP_MODIFY_BATCH_REMOVE_ALL
        ]);
    }

    function it_should_error_when_trying_to_use_an_invalid_batch_mod_type()
    {
        $exception = new InvalidArgumentException("Invalid batch action type: 9001");
        $this->shouldThrow($exception)->duringSetModType(9001);
        $this->shouldThrow($exception)->during('__construct',[9001,'foo','bar']);

    }

    function it_should_be_able_to_check_what_mod_type_it_is()
    {
        $this->isTypeReplace()->shouldBeEqualTo(true);
        $this->isTypeRemove()->shouldNotBeEqualTo(true);
        $this->setModType(Batch::TYPE['REMOVE']);
        $this->isTypeRemove()->shouldBeEqualTo(true);
        $this->setModType(Batch::TYPE['REMOVE_ALL']);
        $this->isTypeRemoveAll()->shouldBeEqualTo(true);
        $this->setModType(Batch::TYPE['ADD']);
        $this->isTypeAdd()->shouldBeEqualTo(true);
    }

    function it_should_let_a_value_be_determined_by_a_closure()
    {
        $foo = function() { return 'foo'; };
        $this->setValues($foo);
        $this->toArray()->shouldBeEqualTo([
            'attrib' => 'foo',
            'modtype' => LDAP_MODIFY_BATCH_REPLACE,
            'values' => ['foo']
        ]);
    }
}

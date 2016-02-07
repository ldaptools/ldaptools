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

use LdapTools\BatchModify\Batch;
use LdapTools\BatchModify\BatchCollection;
use LdapTools\Exception\InvalidArgumentException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class BatchCollectionSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\BatchModify\BatchCollection');
    }

    function it_is_initializable_with_a_dn()
    {
        $dn = 'cn=foo,dc=foo,dc=bar';
        $this->beConstructedWith($dn);
        $this->getDn()->shouldBeEqualTo($dn);
    }

    function it_should_add_a_batch_to_the_collection()
    {
        $this->add(new Batch(LDAP_MODIFY_BATCH_REPLACE, 'foo', ['bar']));
        $this->toArray()->shouldHaveCount(1);
    }

    function it_should_provide_an_array_of_batches_when_calling_to_array()
    {
        $batch = new Batch(LDAP_MODIFY_BATCH_REPLACE, 'foo', ['bar']);
        $this->add($batch);
        $this->toArray()->shouldContain($batch);
    }

    function it_should_provide_an_array_of_batch_arrays_when_calling_get_batch_array()
    {
        $batch = new Batch(LDAP_MODIFY_BATCH_REPLACE, 'foo', ['bar']);
        $this->add($batch);
        $this->getBatchArray()->shouldBeEqualTo([[
            'attrib' => 'foo',
            'modtype' => LDAP_MODIFY_BATCH_REPLACE,
            'values' => ['bar'],
        ]]);
    }

    function it_should_properly_check_if_it_has_a_batch_by_index()
    {
        $batch = new Batch(LDAP_MODIFY_BATCH_REPLACE, 'foo', ['bar']);
        $this->add($batch);
        $this->has(0)->shouldBeEqualTo(true);
        $this->has(1)->shouldBeEqualTo(false);
    }

    function it_should_get_a_batch_by_its_index_value()
    {
        $batch = new Batch(LDAP_MODIFY_BATCH_REPLACE, 'foo', ['bar']);
        $this->add($batch);
        $this->get(0)->shouldBeEqualTo($batch);
    }

    function it_should_remove_a_batch_by_its_index_value()
    {
        $batch = new Batch(LDAP_MODIFY_BATCH_REPLACE, 'foo', ['bar']);
        $this->add($batch);
        $this->get(0)->shouldBeEqualTo($batch);
        $this->remove(0);
        $this->has(0)->shouldBeEqualTo(false);
    }

    function it_should_error_removing_or_getting_an_index_that_does_not_exist()
    {
        $exception = new InvalidArgumentException('Batch index "0" does not exist.');
        $this->shouldThrow($exception)->duringGet(0);
        $this->shouldThrow($exception)->duringRemove(0);
    }

    function it_should_have_a_null_dn_by_default()
    {
        $this->getDn()->shouldBeNull();
    }

    function it_should_properly_set_the_dn()
    {
        $dn = 'cn=foo,dc=foo,dc=bar';
        $this->setDn($dn);
        $this->getDn()->shouldBeEqualTo($dn);
    }

    function it_should_clone_the_batch_objects_when_cloning_the_collection()
    {
        $batch = new Batch(Batch::TYPE['ADD'], 'foo');
        $batches = new BatchCollection();
        $batches->add($batch);

        $new = clone $batches;
        $batch->setAttribute('foobar');

        $this->add($new->get(0));
        $this->get(0)->getAttribute()->shouldBeEqualTo('foo');
    }
}

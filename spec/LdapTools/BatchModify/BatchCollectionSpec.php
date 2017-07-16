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

    function it_is_initializable_with_a_dn_and_batches()
    {
        $dn = 'cn=foo,dc=foo,dc=bar';
        $batch = new Batch(LDAP_MODIFY_BATCH_REPLACE, 'foo', ['bar']);
        $this->beConstructedWith($dn, $batch);

        $this->getDn()->shouldBeEqualTo($dn);
        $this->toArray()->shouldBeEqualTo([$batch]);
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
        $this->toArray()->shouldBeEqualTo([$batch]);
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

    function it_should_properly_check_if_it_has_a_batch()
    {
        $batch = new Batch(LDAP_MODIFY_BATCH_REPLACE, 'foo', ['bar']);
        $this->add($batch);
        $this->has($batch)->shouldBeEqualTo(true);
        $this->has(new Batch(LDAP_MODIFY_BATCH_REMOVE_ALL, 'foo'))->shouldBeEqualTo(false);
    }

    function it_should_remove_a_batch()
    {
        $batch = new Batch(LDAP_MODIFY_BATCH_REPLACE, 'foo', ['bar']);

        $this->add($batch)->remove($batch)->has($batch)->shouldBeEqualTo(false);
    }

    function it_should_set_the_batch()
    {
        $batch =  new Batch(LDAP_MODIFY_BATCH_REPLACE, 'foo', ['bar']);

        $this->set($batch);
        $this->toArray()->shouldBeEqualTo([$batch]);
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

        $this->add(...$new->toArray());
        $this->getBatchArray()->shouldContain([
            'attrib' => "foo",
            'modtype' => 1,
            'values' => [],
        ]);
    }
}

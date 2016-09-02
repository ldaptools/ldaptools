<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Operation;

use LdapTools\BatchModify\Batch;
use LdapTools\BatchModify\BatchCollection;
use LdapTools\Connection\LdapControl;
use LdapTools\Operation\AddOperation;
use LdapTools\Operation\BatchModifyOperation;
use LdapTools\Operation\DeleteOperation;
use LdapTools\Operation\RenameOperation;
use PhpSpec\ObjectBehavior;

class BatchModifyOperationSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('foo');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Operation\BatchModifyOperation');
    }

    function it_should_implement_LdapOperationInterface()
    {
        $this->shouldImplement('\LdapTools\Operation\LdapOperationInterface');
    }

    function it_should_set_the_batch_collection_for_the_batch_operation()
    {
        $batch = new BatchCollection();
        $batch->add(new Batch(Batch::TYPE['ADD'], 'telephoneNumber',"+1 555 555 1717"));

        $this->setBatchCollection($batch);
        $this->getBatchCollection()->shouldBeEqualTo($batch);
    }

    function it_should_set_the_DN_for_the_add_operation()
    {
        $dn = 'cn=foo,dc=example,dc=local';
        $this->setDn($dn);
        $this->getDn()->shouldBeEqualTo($dn);
    }

    function it_should_chain_the_setters()
    {
        $this->setDn('foo')->shouldReturnAnInstanceOf('\LdapTools\Operation\BatchModifyOperation');
        $this->setBatchCollection(new BatchCollection())->shouldReturnAnInstanceOf('\LdapTools\Operation\BatchModifyOperation');
    }

    function it_should_get_the_name_of_the_operation()
    {
        $this->getName()->shouldBeEqualTo('Batch Modify');
    }

    function it_should_get_the_correct_ldap_function()
    {
        $this->getLdapFunction()->shouldBeEqualTo('ldap_modify_batch');
    }

    function it_should_return_the_arguments_for_the_ldap_function_in_the_correct_order()
    {
        $batch = new BatchCollection();
        $batch->add(new Batch(Batch::TYPE['ADD'], 'foo', 'bar'));
        $args = [
            'cn=foo,dc=example,dc=local',
            $batch->getBatchArray(),
        ];
        $this->setDn($args[0]);
        $this->setBatchCollection($batch);
        $this->getArguments()->shouldBeEqualTo($args);
    }

    function it_should_get_a_log_formatted_array()
    {
        $this->getLogArray()->shouldBeArray();
        $this->getLogArray()->shouldHaveKey('DN');
        $this->getLogArray()->shouldHaveKey('Batch');
        $this->getLogArray()->shouldHaveKey('Server');
        $this->getLogArray()->shouldHaveKey('Controls');
    }

    function it_should_mask_password_values_in_the_log_formatted_array()
    {
        $batch = new BatchCollection();
        $batch->add(new Batch(Batch::TYPE['REMOVE'], 'unicodePwd', 'password'));
        $batch->add(new Batch(Batch::TYPE['ADD'], 'userPassword', 'correct horse battery staple'));
        $batch->add(new Batch(Batch::TYPE['REPLACE'], 'givenName', 'Jack'));

        $this->setBatchCollection($batch);
        $logArray = $batch->getBatchArray();
        $logArray[0]['values'] = ['******'];
        $logArray[1]['values'] = ['******'];

        $this->getLogArray()->shouldContain(print_r($logArray, true));
    }

    function it_should_add_pre_operations()
    {
        $operation1 = new AddOperation('cn=foo,dc=bar,dc=foo');
        $operation2 = new DeleteOperation('cn=foo,dc=bar,dc=foo');
        $operation3 = new RenameOperation('cn=foo,dc=bar,dc=foo');

        $this->addPreOperation($operation1);
        $this->addPreOperation($operation2, $operation3);
        $this->getPreOperations()->shouldBeEqualTo([$operation1, $operation2, $operation3]);
    }

    function it_should_add_post_operations()
    {
        $operation1 = new AddOperation('cn=foo,dc=bar,dc=foo');
        $operation2 = new DeleteOperation('cn=foo,dc=bar,dc=foo');
        $operation3 = new RenameOperation('cn=foo,dc=bar,dc=foo');

        $this->addPostOperation($operation1);
        $this->addPostOperation($operation2, $operation3);
        $this->getPostOperations()->shouldBeEqualTo([$operation1, $operation2, $operation3]);
    }

    function it_should_add_ldap_controls()
    {
        $control1 = new LdapControl('foo', true);
        $control2 = new LdapControl('bar');

        $this->addControl($control1, $control2);
        $this->getControls()->shouldBeEqualTo([$control1, $control2]);
    }

    function it_should_clone_the_batch_collection()
    {
        $batch = new Batch(Batch::TYPE['ADD'], 'foo', 'bar');
        $batches = new BatchCollection();
        $batches->add($batch);
        $operation = new BatchModifyOperation('foo', $batches);
        $new = clone $operation;
        $batch->setAttribute('foobar');

        $this->setBatchCollection($new->getBatchCollection());
        $this->getBatchCollection()->get(0)->getAttribute()->shouldNotBeEqualTo('foobar');
    }
}

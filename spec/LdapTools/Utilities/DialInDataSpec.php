<?php
/**
 * This file is part of the LdapTools package.
 *
 * (c) Chad Sikorra <Chad.Sikorra@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\LdapTools\Utilities;

use LdapTools\Utilities\DialInData;
use PhpSpec\ObjectBehavior;

class DialInDataSpec extends ObjectBehavior
{
    protected $testData = '6409376978373720202020202020202020202020202020202020';
    
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Utilities\DialInData');
    }

    function it_should_be_able_to_be_constructed_from_a_binary_string()
    {
        $this->beConstructedWith(hex2bin($this->testData));
        $this->getUserPrivilege()->shouldEqual(9);
    }
    
    function it_should_have_a_default_UserPrivilege_of_1_when_newly_constructed()
    {
        $this->getUserPrivilege()->shouldEqual(1);
    }
    
    function it_should_convert_back_to_binary()
    {
        $this->beConstructedWith(hex2bin($this->testData));
        $this->toBinary()->shouldEqual(hex2bin($this->testData));
    }
    
    function it_should_set_an_empty_callback_phone_number_if_it_is_not_supplied()
    {
        $binary = (new DialInData())->setUserPrivilege(9)->toBinary();
        $this->beConstructedWith($binary);
        
        $this->getCallbackPhoneNumber()->shouldEqual('202020202020202020202020202020202020202020202020');
    }

    function it_should_set_the_user_privilege_and_callback_number_when_converting_back_to_binary()
    {
        $cbn = str_pad('02', 48, '20', STR_PAD_RIGHT);
        $this->setUserPrivilege(9);
        $this->setCallbackPhoneNumber($cbn);
        
        $this->toBinary()->shouldEqual(hex2bin(substr_replace($this->testData, $cbn, 4, 48)));
    }
    
    function it_should_get_the_signature()
    {
        $this->beConstructedWith(hex2bin($this->testData));
        $this->getSignature()->shouldEqual(DialInData::VALID_SIGNATURE);
        $this->isSignatureValid()->shouldEqual(true);
    }
    
    function it_should_check_if_the_signature_is_valid()
    {
        $this->beConstructedWith(hex2bin(substr_replace($this->testData, '61', 0, 2)));
        $this->isSignatureValid()->shouldEqual(false);
    }
}

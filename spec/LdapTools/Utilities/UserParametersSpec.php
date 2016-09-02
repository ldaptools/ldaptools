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
use LdapTools\Utilities\TSPropertyArray;
use LdapTools\Utilities\UserParameters;
use PhpSpec\ObjectBehavior;

class UserParametersSpec extends ObjectBehavior
{
    protected $defaultHex = '6d747843666750726573656e742020202020202020206409376978373720202020202020202020202020202020'.
        '20202050101a080143747843666750726573656e74e394b5e694b1e688b0e381a2200201437478574650726f66696c6550617468e380b0'.
        '220201437478574650726f66696c655061746857e380b01802014374785746486f6d65446972e380b01a02014374785746486f6d654469'.
        '7257e380b02202014374785746486f6d654469724472697665e380b02402014374785746486f6d65446972447269766557e380b0120801'.
        '437478536861646f77e384b0e380b0e380b0e380b02e08014374784d6178446973636f6e6e656374696f6e54696d65e380b0e380b0e380'.
        'b0e380b02808014374784d6178436f6e6e656374696f6e54696d65e380b0e380b0e380b0e380b01c08014374784d617849646c6554696d'.
        '65e380b0e380b0e380b0e380b0200201437478576f726b4469726563746f7279e380b0220201437478576f726b4469726563746f727957'.
        'e380b0180801437478436667466c61677331e380b0e381a6e380b2e380b9220201437478496e697469616c50726f6772616de380b02402'.
        '01437478496e697469616c50726f6772616d57e380b0';
    
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Utilities\UserParameters');
    }
    
    function it_should_decode_binary_dialin_and_TSPropertyArray_data_on_construction()
    {
        $this->beConstructedWith(hex2bin($this->defaultHex));
        
        $this->getDialInData()->shouldReturnAnInstanceOf('LdapTools\Utilities\DialInData');
        $this->getTSPropertyArray()->shouldReturnAnInstanceOf('LdapTools\Utilities\TSPropertyArray');
        $this->toBinary()->shouldEqual(hex2bin($this->defaultHex));
    }
    
    function it_should_not_construct_the_TSPropertyArray_if_it_doesnt_exist()
    {
        $this->beConstructedWith(hex2bin(substr($this->defaultHex, 0, 96)));
        $this->getDialInData()->shouldReturnAnInstanceOf('LdapTools\Utilities\DialInData');
        $this->getTSPropertyArray()->shouldBeNull();
        
        $this->toBinary()->shouldEqual(hex2bin(substr_replace(
            substr($this->defaultHex, 0, 96),
            '6d3a2020202020202020202020202020202020202020',
            0,
            44
        )));
    }
    
    function it_should_not_construct_the_dialin_data_if_it_is_completely_empty()
    {
        $expected = substr_replace($this->defaultHex, str_pad('', 52, '20'), 44, 52);
        $expected = substr_replace(
            $expected, 
            str_pad(unpack('H*', UserParameters::RESERVED_DATA_VALUE['RDS'])[1], 44, '20', STR_PAD_RIGHT),
            0,
            44
        );
        $this->beConstructedWith(hex2bin(substr_replace($this->defaultHex, str_pad('', 52, '20'), 44, 52)));
        
        $this->getDialInData()->shouldBeNull();
        $this->getTSPropertyArray()->shouldReturnAnInstanceOf('LdapTools\Utilities\TSPropertyArray');
        $this->toBinary()->shouldEqual(hex2bin($expected));
    }
    
    function it_should_be_constructed_with_a_TSPropertyArray_object()
    {
        $tsPropArray = new TSPropertyArray();
        $tsPropArray->set('CtxWFProfilePath', 'foobar');
        
        $this->beConstructedWith($tsPropArray);
        $this->getTSPropertyArray()->shouldEqual($tsPropArray);
        $this->getDialInData()->shouldBeNull();
    }
    
    function it_should_be_constructed_with_a_DialInData_object()
    {
        $did = new DialInData();
        $did->setUserPrivilege(9);
        
        $this->beConstructedWith($did);
        $this->getDialInData()->shouldEqual($did);
        $this->getTSPropertyArray()->shouldBeNull();
    }

    function it_should_be_able_to_set_TSPropertyArray()
    {
        $tsPropArray = (new TSPropertyArray())->set('CtxShadow', 4);
        $this->setTSPropertyArray($tsPropArray);
        $this->getTSPropertyArray()->shouldEqual($tsPropArray);
    }

    function it_should_be_able_to_set_DialInData()
    {
        $did = (new DialInData())->setUserPrivilege(9);
        
        $this->setDialInData($did);
        $this->getDialInData()->shouldEqual($did);
    }

    function it_should_preserve_any_data_that_occurs_after_the_TSPropertyArray_data()
    {
        $foobar = '666f6f626172';
        $hex = $this->defaultHex;
        $hex .= $foobar;
        $this->beConstructedWith(hex2bin($hex));
        
        $this->getPostBinary()->shouldEqual(hex2bin($foobar));
        $this->toBinary()->shouldEqual(hex2bin($hex));
    }
    
    function it_should_get_the_reserved_data_string_from_a_binary_constructed_object()
    {
        $this->beConstructedWith(hex2bin($this->defaultHex));
        
        $this->getReservedDataString()->shouldEqual(UserParameters::RESERVED_DATA_VALUE['NPS_RDS']);
    }
    
    function it_should_get_empty_binary_data_when_nothing_is_set()
    {
        $this->toBinary()->shouldEqual(hex2bin(str_pad('', 96, '20')));
    }
}

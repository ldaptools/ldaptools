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

use LdapTools\Utilities\TSProperty;
use LdapTools\Utilities\TSPropertyArray;
use PhpSpec\ObjectBehavior;

class TSPropertyArraySpec extends ObjectBehavior
{
    protected $defaultHex = '43747843666750726573656e742020202020202020202020202020202020202020202020202020202020202020'.
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
        $this->shouldHaveType('LdapTools\Utilities\TSPropertyArray');
    }
    
    function it_should_get_the_default_binary_value_when_newly_constructed()
    {
        $this->toBinary()->shouldHaveHex(substr($this->defaultHex, 96));
    }
    
    function it_should_support_being_constructed_with_a_binary_value()
    {
        $tsPropArray = new TSPropertyArray();
        $tsPropArray->set('CtxWFProfilePath', 'foo');
        $bin = $tsPropArray->toBinary();

        $this->beConstructedWith($bin);
        $this->get('CtxWFProfilePath')->shouldEqual('foo');
    }
    
    function it_should_support_being_constructed_from_an_array_with_property_values()
    {
        $props = TSPropertyArray::DEFAULTS;
        $props['CtxWFProfilePath'] = 'foobar';

        $this->beConstructedWith($props);
        $this->get('CtxWFProfilePath')->shouldEqual('foobar');
    }
    
    function it_should_get_a_specific_TSProperty_object()
    {
        $this->get('CtxWFProfilePath')->shouldBeString();
    }
    
    function it_should_return_whether_a_specific_TSProperty_exists()
    {
        $this->has('foo')->shouldEqual(false);
        $this->has('CtxWFProfilePath')->shouldEqual(true);
    }

    function it_should_add_a_specific_TSProperty()
    {
        $tsProp = new TSProperty();
        $tsProp->setName('foo');
        $tsProp->setValue('bar');
        
        $this->has('foo')->shouldEqual(false);
        $this->add($tsProp);
        $this->has('foo')->shouldEqual(true);
    }
    
    function it_should_remove_a_specific_TSProperty()
    {
        $tsProp = new TSProperty();
        $tsProp->setName('foo');
        $tsProp->setValue('bar');
        
        $this->add($tsProp);
        $this->has('foo')->shouldEqual(true);
        $this->remove('foo');
        $this->has('foo')->shouldEqual(false);
    }

    function it_should_set_a_specific_TSProperty_by_name()
    {
        $this->set('CtxWFProfilePath', 'foo');
        $this->get('CtxWFProfilePath')->shouldEqual('foo');
        $this->set('CTXWFPROFILEPATH', 'bar');
        $this->get('CtxWFProfilePath')->shouldEqual('bar');
    }
    
    function it_should_throw_an_invalid_argument_exception_when_the_property_doesnt_exist()
    {
        $this->shouldThrow('LdapTools\Exception\InvalidArgumentException')->duringGet('foo');
        $this->shouldThrow('LdapTools\Exception\InvalidArgumentException')->duringSet('foo', 'bar');
    }
    
    function it_should_return_all_the_TSProperty_objects_as_a_key_value_array()
    {
        $this->toArray()->shouldEqual([
            'CtxCfgPresent' => 2953518677,
            'CtxWFProfilePath' => "",
            'CtxWFProfilePathW' => "",
            'CtxWFHomeDir' => "",
            'CtxWFHomeDirW' => "",
            'CtxWFHomeDirDrive' => "",
            'CtxWFHomeDirDriveW' => "",
            'CtxShadow' => 1,
            'CtxMaxDisconnectionTime' => 0,
            'CtxMaxConnectionTime' => 0,
            'CtxMaxIdleTime' => 0,
            'CtxWorkDirectory' => "",
            'CtxWorkDirectoryW' => "",
            'CtxCfgFlags1' => 2418077696,
            'CtxInitialProgram' => "",
            'CtxInitialProgramW' => "",
        ]);
    }

    function it_should_respect_the_binary_data_stored_after_the_TSPropertyArray_data_when_constructed()
    {
        $postBinary = '666f6f626172';
        $hex = bin2hex((new TSPropertyArray())->set('CtxWFProfilePath', 'foo')->toBinary());
        $hexAdd = $hex.$postBinary;
        $this->beConstructedWith(hex2bin($hexAdd));

        $this->set('CtxWFProfilePath', 'foo');
        $this->toBinary()->shouldHaveHex($hex);
        $this->getPostBinary()->shouldEqual(hex2bin($postBinary));
    }

    function it_should_get_the_signature_for_the_data()
    {
        $this->beConstructedWith((new TSPropertyArray())->toBinary());
        $this->getSignature()->shouldEqual(TSPropertyArray::VALID_SIGNATURE);
        $this->isSignatureValid()->shouldEqual(true);
    }
    
    function it_should_be_able_to_check_whether_the_signature_is_valid()
    {
        $hex = substr($this->defaultHex, 96);
        
        $this->beConstructedWith(hex2bin(substr_replace($hex, '61', 0, 2)));
        $this->isSignatureValid()->shouldEqual(false);
    }

    public function getMatchers()
    {
        return [
            'haveHex' => function($subject, $value) {
                return bin2hex($subject) == $value;
            },
        ];
    }
}

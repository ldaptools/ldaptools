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

use PhpSpec\ObjectBehavior;

class TSPropertySpec extends ObjectBehavior
{
    protected $profilePathHex = '200e01437478574650726f66696c6550617468e398b6e698b6e698b6e388b6e384b6e388b7e380b0';
    
    protected $connectionTimeHex = '2808014374784d6178436f6e6e656374696f6e54696d65e381a3e39cb2e3a4b0e380b0';
    
    protected $shadowHex = '120801437478536861646f77e384b0e380b0e380b0e380b0';
    
    protected $cfgFlagsHex = '180801437478436667466c61677331e380b0e381a6e380b2e380b9';
    
    function it_is_initializable()
    {
        $this->shouldHaveType('LdapTools\Utilities\TSProperty');
    }
    
    function it_should_reconstruct_the_binary_value_for_a_string_value()
    {
        $this->beConstructedWith(hex2bin($this->profilePathHex));

        $this->getName()->shouldEqual('CtxWFProfilePath');
        $this->getValue()->shouldEqual('foobar');
        $this->toBinary()->shouldHaveHex($this->profilePathHex);
    }

    function it_should_reconstruct_the_binary_value_for_a_time_value()
    {
        $this->beConstructedWith(hex2bin($this->connectionTimeHex));

        $this->getName()->shouldEqual('CtxMaxConnectionTime');
        $this->getValue()->shouldEqual(10);
        $this->toBinary()->shouldHaveHex($this->connectionTimeHex);
    }
    
    function it_should_reconstruct_the_binary_value_for_an_integer_value()
    {
        $this->beConstructedWith(hex2bin($this->shadowHex));

        $this->getName()->shouldEqual('CtxShadow');
        $this->getValue()->shouldEqual(1);
        $this->toBinary()->shouldHaveHex($this->shadowHex);
    }
    
    function it_should_reconstruct_the_binary_value_for_a_bitmask_int_value()
    {
        $this->beConstructedWith(hex2bin($this->cfgFlagsHex));

        $this->getName()->shouldEqual('CtxCfgFlags1');
        $this->getValue()->shouldEqual(2418077696);
        $this->toBinary()->shouldHaveHex($this->cfgFlagsHex);
    }
    
    function it_should_encode_a_newly_built_TSProperty_properly()
    {
        $this->setName('CtxShadow');
        $this->setValue(1);
        $this->toBinary()->shouldHaveHex($this->shadowHex);
    }
    
    function it_should_change_a_name_and_value_when_reencoding_it_after_being_constructed_from_binary()
    {
        $this->beConstructedWith(hex2bin($this->shadowHex));
        
        $this->setName('CtxCfgFlags1');
        $this->setValue(2418077696);
        $this->toBinary()->shouldHaveHex($this->cfgFlagsHex);
    }

    function it_should_properly_encode_and_decode_values_with_UTF8_characters()
    {
        $hex = '200e01437478574650726f66696c6550617468e398b6e38da6e38da6e388b6e385a5e388b7e380b0';
        $this->beConstructedWith(hex2bin($hex));
        
        $this->getName()->shouldEqual('CtxWFProfilePath');
        $this->getValue()->shouldEqual('fóóbár');
        $this->toBinary()->shouldHaveHex($hex);
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

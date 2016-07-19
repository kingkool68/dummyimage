<?php
/**
* Class for converting colortypes
*
* The class includes the following colors formats and types:
* 
*  - CMYK
*  - RGB
*  - Pantone (seperate include file: pantone.color.class.php)
*  - HEX Codes for HTML
* 
* @author    Sven Wagener <wagener_at_indot_dot_de>
* @copyright Sven Wagener
* @include 	 Funktion:_include_
* @url       http://phpclasses.ehd.com.br/browse/package/804.html
*/
class color {
	
    /**
    * @var	array $rgb
    * @access private
    * @desc	array for RGB colors
    */	
	var $rgb=array('r'=>0,'g'=>0,'b'=>0);

    /**
    * @var	string $hex
    * @access private
    * @desc	variable for HTML HEX color
    */	
	var $hex='';

    /**
    * @var	array $cmyk
    * @access private
    * @desc	array for cmyk colors
    */	
	var $cmyk=array('c'=>0,'m'=>0,'y'=>0,'b'=>0);

	/**
	* Sets the RGB values	
	* @param int $red number from 0-255 for blue color value
	* @param int $green number from 0-255 for green color value
	* @param int $blue number from 0-255 for blue color value
    * @access public	
	* @desc Sets the RGB values
	*/
	function set_rgb($red,$green,$blue){
		
		$this->rgb['r']=$red;
		$this->rgb['g']=$green;
		$this->rgb['b']=$blue;
		
		$this->convert_rgb_to_cmyk();
		$this->convert_rgb_to_hex();
	}

	/**
	* Sets the HEX HTML color value
	* @param string $hex 6,3,2, or 1 characters long.
    * @access public	
	* @desc Sets the HEX HTML color value like ffff00. It will convert shorthand to a 6 digit hex.
	*/
	function set_hex($hex){
		//$hex = settype($hex, 'string');
		$hex = strtolower($hex);
		$hex = preg_replace('/#/', '', $hex); //Strips out the # character
		$hexlength = strlen($hex);
		$input = $hex;
		switch($hexlength) {
			case 1:
				$hex = $input.$input.$input.$input.$input.$input;
			break;
			case 2:
				$hex = $input[0].$input[1].$input[0].$input[1].$input[0].$input[1];
			break;
			case 3:
				$hex = $input[0].$input[0].$input[1].$input[1].$input[2].$input[2];
			break;
		}
		$this->hex=$hex;
		
		$this->convert_hex_to_rgb();
		$this->convert_rgb_to_cmyk();
	}
	
	/**
	* Sets the HTML color name, converting it to a 6 digit hex code.
	* @param string $name The name of the color.
    * @access public	
	* @desc Sets the HTML color name, converting it to a 6 digit hex code.
	*/
	function set_name($name){
		$this->hex = $this->convert_name_to_hex($name);
		
		$this->convert_hex_to_rgb();
		$this->convert_rgb_to_cmyk();
	}
	
	/**
	* Sets the CMYK color values
	* @param int $c number from 0-100 for c color value
	* @param int $m number from 0-100 for m color value
	* @param int $y number from 0-100 for y color value
	* @param int $b number from 0-100 for b color value
    * @access public
	* @desc Sets the CMYK color values
	*/	
	function set_cmyk($c,$m,$y,$b){
		$this->cmyk['c']=$c;
		$this->cmyk['m']=$m;
		$this->cmyk['y']=$y;
		$this->cmyk['b']=$b;
		
		$this->convert_cmyk_to_rgb();
		$this->convert_rgb_to_hex();
	}

	/**
	* Sets the pantone color value
	* @param string $pantone_name name of the pantone color
    * @access public	
	* @desc Sets the pantone color value
	*/	
	function set_pantone($pantone_name){
		$this->pantone=$pantone_name;
		$this->cmyk['c']=$this->pantone_pallete[$pantone_name]['c'];
		$this->cmyk['m']=$this->pantone_pallete[$pantone_name]['m'];
		$this->cmyk['y']=$this->pantone_pallete[$pantone_name]['y'];
		$this->cmyk['b']=$this->pantone_pallete[$pantone_name]['b'];
		
		$this->convert_cmyk_to_rgb();
		$this->convert_rgb_to_hex();		
	}
	
	/**
	* Sets the pantone pc color value
	* @param string $pantone_name_pc name of the pantone pc color
    * @access public	
	* @desc Sets the pantone pc color value
	*/		
	function set_pantone_pc($pantone_name){
		$this->pantone_pc=$pantone_name;
		$this->cmyk['c']=$this->pantone_pallete_pc[$pantone_name]['c'];
		$this->cmyk['m']=$this->pantone_pallete_pc[$pantone_name]['m'];
		$this->cmyk['y']=$this->pantone_pallete_pc[$pantone_name]['y'];
		$this->cmyk['b']=$this->pantone_pallete_pc[$pantone_name]['b'];
		
		$this->convert_cmyk_to_rgb();
		$this->convert_rgb_to_hex();			
	}
	
	//include("pantone.color.class.php");

	/**
	* Returns the RGB values of a set color
	* @return array $rgb color values of red ($rgb['r']), green ($rgb['green') and blue ($rgb['b'])
    * @access public
	* @desc Returns the RGB values of a set color
	*/	
	function get_rgb($val){
		if($val) {
			return $this->rgb[$val];
		} else {
			return $this->rgb;	
		}
	}

	/**
	* Returns the HEX HTML color value of a set color
	* @return string $hex HEX HTML color value
    * @access public
	* @desc Returns the HEX HTML color value of a set color
	*/	
	function get_hex(){
		return $this->hex;
	}
	
	/**
	* Returns the CMYK values of a set color
	* @return array $cmyk color values of c ($cmyk['c']), m ($cmyk['m'), y ($cmyk['blue']) and b ($cmyk['b'])
    * @access public	
	* @desc Returns the CMYK values of a set color
	*/	
	function get_cmyk(){
		return $this->cmyk;
	}

	/**
	* Converts the RGB colors to HEX HTML colors
    * @access private
	* @desc Converts the RGB colors to HEX HTML colors
	*/	
	function convert_rgb_to_hex(){
		$this->hex=$this->hex_trip[$this->rgb['r']].$this->hex_trip[$this->rgb['g']].$this->hex_trip[$this->rgb['b']];
	}
	
	/**
	* Converts the RGB colors to CMYK colors
    * @access private
	* @desc Converts the RGB colors to CMYK colors
	*/		
	function convert_rgb_to_cmyk(){
		$c = (255-$this->rgb['r'] )/255.0*100;
		$m = (255-$this->rgb['g'] )/255.0*100;
		$y = (255-$this->rgb['b'] )/255.0*100;
		
		$b = min(array($c,$m,$y));
		$c=$c-$b;
		$m=$m-$b;
		$y=$y-$b;
		
		$this->cmyk = array( 'c' => $c, 'm' => $m, 'y' => $y, 'b' => $b);
	}
	
	/**
	* Converts the CMYK colors to RGB colors
    * @access private
	* @desc Converts the CMYK colors to RGB colors
	*/		
	function convert_cmyk_to_rgb(){
		$red=$this->cmyk['c']+$this->cmyk['b'];
		$green=$this->cmyk['m']+$this->cmyk['b'];
		$blue=$this->cmyk['y']+$this->cmyk['b'];
		
		$red=($red-100)*(-1);
		$green=($green-100)*(-1);
		$blue=($blue-100)*(-1);
		
		$red=round($red/100*255,0);
		$green=round($green/100*255,0);
		$blue=round($blue/100*255,0);
		
		$this->rgb['r']=$red;
		$this->rgb['g']=$green;
		$this->rgb['b']=$blue;
	}
	
	/**
	* Converts the HTML HEX colors to RGB colors
    * @access private
	* @desc Converts the HTML HEX colors to RGB colors
	* @url http://css-tricks.com/snippets/php/convert-hex-to-rgb/
	*/		
	function convert_hex_to_rgb(){
		$red = substr($this->hex,0,2);
		$green = substr($this->hex,2,2);
		$blue = substr($this->hex,4,2);
        $this->rgb['r'] = hexdec( $red );
        $this->rgb['g']  = hexdec( $green );
        $this->rgb['b'] = hexdec( $blue );
	}
	
	/**
	* Converts HTML color name to 6 digit HEX value.
    * @access private
	* @param string $name One of the offical HTML color names.
	* @desc Converts HTML color name to 6 digit HEX value.
	* @url http://en.wikipedia.org/wiki/HTML_color_names
	*/		
	function convert_name_to_hex($name){
		$color_names = array(
			'aqua' => '00ffff',
			'cyan' => '00ffff',
			'gray' => '808080',
			'grey' => '808080',
			'navy' => '000080',
			'silver' => 'C0C0C0',
			'black' => '000000',
			'green' => '008000',
			'olive' => '808000',
			'teal' => '008080',
			'blue' => '0000FF',
			'lime' => '00FF00',
			'purple' => '800080',
			'white' => 'ffffff',
			'fuchsia' => 'FF00FF',
			'magenta' => 'FF00FF',
			'maroon' => '800000',
			'red' => 'FF0000',
			'yellow' => 'FFFF00'
		);
		if (array_key_exists($name, $color_names)) {
			return $color_names[$name];
		}
		else {
			//error
		}
	}
}
?>
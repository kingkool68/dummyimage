<?php
/*
Dynamic Dummy Image Generator - DummyImage.com
Copyright (c) 2011 Russell Heimlich

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.

*/

if ( isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
	// If the browser has a cached version of this image, send 304
	header( 'Last-Modified: ' . $_SERVER['HTTP_IF_MODIFIED_SINCE'], true, 304 );
	exit;
}

/**
 * Monkey patch $_GET parameters
 *
 * When I first wrote this and didn't know what I was doing I passed the entire URL path
 * of the request to the script as ?x=<stuff> via a URL rewrite. To make the text parameter
 * work you needed to construct the URL like "&text=whatever" which would populate $_GET['text'].
 *
 * If you construct the URL like "?text=whatever", the text parameter will be ignored.
 * This is intedned to fix this while maintaining backwards compatibility.
 */
$url_parts = parse_url( $_SERVER['REQUEST_URI'] );
if ( ! empty( $url_parts['query'] ) ) {
	parse_str( $url_parts['query'], $query_arr );

	// Make sure we don't overwrite $_GET['x'] populated by a URL rewrite at the server level
	unset( $query_arr['x'] );
	$_GET = array_merge( $query_arr, $_GET );
}

/**
 * Extract the text parameter from the URL
 */
if ( empty( $_GET['text'] ) || ! isset( $_GET['text'] ) ) {
	preg_match( '/&text=(.+)/i', $_GET['x'], $matches );
	if ( isset( $matches[1] ) ) {
		$_GET['text'] = urldecode( $matches[1] );
	}
}

// Ruquay K Calloway http://ruquay.com/sandbox/imagettf/ made a better function to find the coordinates of the text bounding box so I used it.
function imagettfbbox_t( $size, $text_angle, $fontfile, $text ) {
	// Compute size with a zero angle
	$coords = imagettfbbox( $size, 0, $fontfile, $text );

	// Convert angle to radians
	$a = deg2rad( $text_angle );

	// Compute some usefull values
	$ca  = cos( $a );
	$sa  = sin( $a );
	$ret = array();

	// Perform transformations
	for ( $i = 0; $i < 7; $i += 2 ) {
		$ret[ $i ]     = round( $coords[ $i ] * $ca + $coords[ $i + 1 ] * $sa );
		$ret[ $i + 1 ] = round( $coords[ $i + 1 ] * $ca - $coords[ $i ] * $sa );
	}
	return $ret;
}

/**
 * Convert a hex string to red, green, blue, and alpha values between 0 - 255.
 *
 * Accepts a 1, 2, 3, 4, 6, and 8 digit hex code.
 *
 * The alpha transparency can be set with :50 at the end of the hex string.
 *
 *  #ccc:50 would be R = 204, G = 204, B = 204, Alpha = 128 (or 50%)
 *  Same thing for #cccccc80
 *
 * @param  string $hex The hex string to convert
 */
function hex2rgba( $hex = '' ) {
    $hex        = strtolower( $hex );
    $hex        = str_replace( '#', '', $hex );
    $hex_parts  = explode( ':', $hex );
    $hex        = $hex_parts[0];
    $hex_length = strlen( $hex );
    $input      = $hex;
    switch ($hex_length) {
        case 1:
            $hex = $input . $input . $input . $input . $input . $input;
            break;
        case 2:
            $hex = $input[0] . $input[1] . $input[0] . $input[1] . $input[0] . $input[1];
            break;
        case 3:
            $hex = $input[0] . $input[0] . $input[1] . $input[1] . $input[2] . $input[2];
            break;
        case 4:
            $hex = $input[0] . $input[0] . $input[1] . $input[1] . $input[2] . $input[2] . $input[3] . $input[3];
            break;
    }
    // Set the alpha level to 100% for 6 character hex codes
    if ( strlen( $hex ) === 6 ) {
        $hex .= 'ff';
    }
    $parts = str_split( $hex, 2 );
    $alpha = $parts[3];
    $alpha = hexdec( $alpha );
    $alpha_percent = round( $alpha / 255, 2 );
    if ( isset( $hex_parts[1] ) ) {
        $user_supplied_alpha = $hex_parts[1];
        $user_supplied_alpha = abs( (int) $user_supplied_alpha );
        $user_supplied_alpha = min( $user_supplied_alpha, 100 );
        $alpha_percent = $user_supplied_alpha / 100;
        $alpha = round( $alpha_percent * 255 );
    }

    return (object) array(
        'r'             => hexdec( $parts[0] ),
        'g'             => hexdec( $parts[1] ),
        'b'             => hexdec( $parts[2] ),
        'alpha'         => (int) $alpha,
        'alpha_percent' => $alpha_percent,
    );
}

// Get the query string from the URL. x would = 600x400 if the url was https://dummyimage.com/600x400
$x = strtolower( $_GET['x'] );
// Strip / if it's the first character
$x        = ltrim( $x, '/' );
$x_pieces = explode( '/', $x );

/**
 * Find the dimensions
 */
// Translate image size keywords to dimensions
$keywords = array(
	// IAB Standard ad sizes
	'mediumrectangle'   => '300x250',
	'medrect'           => '300x250',
	'squarepopup'       => '250x250',
	'sqrpop'            => '250x250',
	'verticalrectangle' => '240x400',
	'vertrec'           => '240x400',
	'largerectangle'    => '336x280',
	'lrgrec'            => '336x280',
	'rectangle'         => '180x150',
	'rec'               => '180x150',
	'popunder'          => '720x300',
	'pop'               => '720x300',
	'fullbanner'        => '468x60',
	'fullban'           => '468x60',
	'halfbanner'        => '234x60',
	'halfban'           => '234x60',
	'microbar'          => '88x31',
	'mibar'             => '88x31',
	'button1'           => '120x90',
	'but1'              => '120x90',
	'button2'           => '120x60',
	'but2'              => '120x60',
	'verticalbanner'    => '120x240',
	'vertban'           => '120x240',
	'squarebutton'      => '125x125',
	'sqrbut'            => '125x125',
	'leaderboard'       => '728x90',
	'leadbrd'           => '728x90',
	'wideskyscraper'    => '160x600',
	'wiskyscrpr'        => '160x600',
	'skyscraper'        => '120x600',
	'skyscrpr'          => '120x600',
	'halfpage'          => '300x600',
	'hpge'              => '300x600',

	// Computer display standards via https://en.wikipedia.org/wiki/Computer_display_standard
	'cga'   => '320x200',
	'qvga'  => '320x240',
	'vga'   => '640x480',
	'wvga'  => '800x480',
	'svga'  => '800x600',
	'wsvga' => '1024x600',
	'xga'   => '1024x768',
	'wxga'  => '1280x800',
	'sxga'  => '1280x1024',
	'wsxga' => '1440x900',
	'uxga'  => '1600x1200',
	'wuxga' => '1920x1200',
	'qxga'  => '2048x1536',
	'wqxga' => '2560x1600',
	'qsxga' => '2560x2048',
	'wqsxga' => '3200x2048',
	'quxga'  => '3200x2400',
	'wquxga' => '3840x2400',

	// Video Standards
	'ntsc'   => '720x480',
	'pal'    => '768x576',
	'hd720'  => '1280x720',
	'720p'   => '1280x720',
	'hd1080' => '1920x1080',
	'1080p'  => '1920x1080',
	'2k'     => '2560x1440',
	'4k'     => '3840x2160',
);
$image_size_keyword = '';
if ( ! empty( $keywords[ $x_pieces[0] ] ) ) {
	$image_size_keyword = $x_pieces[0];
	$x_pieces[0] = $keywords[ $x_pieces[0] ];
}

// Dimensions are always the first paramter in the URL
$dimensions = explode( 'x', $x_pieces[0] );

// Filter out any characters that are not numbers, colons or decimal points
$width  = preg_replace( '/[^\d:\.]/i', '', $dimensions[0] );
$height = $width;
if ( ! empty( $dimensions[1] ) ) {
	$height = preg_replace( '/[^\d:\.]/i', '', $dimensions[1] );
}

// Sanity check that there is only 1 colon in the dimensions to perform a ratio calculation
if ( substr_count( $x_pieces[0], ':' ) > 1 ) {
	die( $x_pieces[0] . ' has too many colons in the dimension paramter! There should be 1 at most.' );
}

// Can't calculate a ratio without a height
if ( strstr( $x_pieces[0], ':' ) && ! strstr( $x_pieces[0], 'x' ) ) {
	die( 'To calculate a ratio a height is needed.' );
}

// If one of the dimensions has a colon in it, we can calculate the aspect ratio.
// Chances are the height will contain a ratio, so we'll check that first.
if ( preg_match( '/:/', $height ) ) {

	$ratio = explode( ':', $height );

	// If we only have one ratio value set the other value to the same value of the first making it a ratio of 1
	if ( empty( $ratio[1] ) ) {
		$ratio[1] = $ratio[0];
	}

	if ( empty( $ratio[0] ) ) {
		$ratio[0] = $ratio[1];
	}

	// Ensure we're dealing with numbers
	$width    = abs( floatval( $width ) );
	$ratio[0] = abs( floatval( $ratio[0] ) );
	$ratio[1] = abs( floatval( $ratio[1] ) );

	$height = ( $width * $ratio[1] ) / $ratio[0];

} elseif ( preg_match( '/:/', $width ) ) {

	$ratio = explode( ':', $width );
	// If we only have one ratio value, set the other value to the same value of the first making it a ratio of 1
	if ( empty( $ratio[1] ) ) {
		$ratio[1] = $ratio[0];
	}

	if ( empty( $ratio[0] ) ) {
		$ratio[0] = $ratio[1];
	}

	// Ensure we're dealing with numbers
	$height   = abs( floatval( $height ) );
	$ratio[0] = abs( floatval( $ratio[0] ) );
	$ratio[1] = abs( floatval( $ratio[1] ) );

	$width = ( $height * $ratio[0] ) / $ratio[1];
}

$width  = abs( floatval( $width ) );
$height = abs( floatval( $height ) );

// If the dimensions are too small then kill the script
if ( $width < 1 || $height < 1 ) {
	die( 'Too small of an image!' );
}

// Limit the size of the image to no more than an area of 33,177,600 (8K resolution)
$area = $width * $height;
if ( $area > 33177600 || $width > 9999 || $height > 9999 ) {
	die( 'Too big of an image!' );
}

// Let's round the dimensions to 3 decimal places for aesthetics
$width  = round( $width, 3 );
$height = round( $height, 3 );

// Find the background color which is always after the 2nd slash in the url
$bg_color = 'ccc';
if ( ! empty( $x_pieces[1] ) ) {
	$bg_color_parts = explode( '.', $x_pieces[1] );
	if ( ! empty( $bg_color_parts[0] ) ) {
		$bg_color = $bg_color_parts[0];
	}
}
$background = hex2rgba( $bg_color );

// Find the foreground color which is always after the 3rd slash in the url
$fg_color = '000';
if ( isset( $x_pieces[2] ) ) {
	$fg_color_parts = explode( '.', $x_pieces[2] );
	if ( isset( $fg_color_parts[0] ) && ! empty( $fg_color_parts[0] ) ) {
		$fg_color = $fg_color_parts[0];
	}
}
$foreground = hex2rgba( $fg_color );

// This is the default text string that will go right in the middle of the rectangle
// &#215; is the multiplication sign, it is not an 'x'
$text  = $width . ' &#215; ' . $height;
$lines = 1;

if ( ! empty( $_GET['text'] ) ) {
	$lines = substr_count( $_GET['text'], '|' );
	$text  = preg_replace( '/\|/i', "\n", $_GET['text'] );
}

// Determine the file format. This can be anywhere in the URL.
$file_format = 'png';
preg_match_all( '/\.(webp|gif|jpg|jpeg)/', $x, $result );
if ( ! empty( $result[1][0] ) ) {
	$file_format = $result[1][0];
}

// I don't use this but if you wanted to angle your text you would change it here.
$text_angle = 0;

 // If you want to use a different font simply upload the true type font (.ttf) file to the same directory as this PHP file and set the $font variable to the font file name. I'm using the M+ font which is free for distribution -> http://www.fontsquirrel.com/fonts/M-1c
$font = 'fonts/mplus-2c-light.ttf';

// Create an image
$img      = imageCreate( $width, $height );
$bg_alpha = 127 - ( $background->alpha >> 1);
$bg_color = imagecolorallocatealpha(
	$img,
	$background->r,
	$background->g,
	$background->b,
	$bg_alpha
);

$fg_alpha = 127 - ( $foreground->alpha >> 1);
$fg_color = imagecolorallocatealpha(
	$img,
	$foreground->r,
	$foreground->g,
	$foreground->b,
	$fg_alpha
);

// Ric Ewing: I modified this to behave better with long or narrow images and condensed the resize code to a single line
$fontsize = max( min( $width / strlen( $text ) * 1.15, $height * 0.5 ), 5 );
// Pass these variable to a function to calculate the position of the bounding box
$textBox = imagettfbbox_t( $fontsize, $text_angle, $font, $text );
// Calculate the width of the text box by subtracting the upper right "X" position with the lower left "X" position
$textWidth = ceil( ( $textBox[4] - $textBox[1] ) * 1.07 );
// Calculate the height of the text box by adding the absolute value of the upper left "Y" position with the lower left "Y" position
$textHeight = ceil( ( abs( $textBox[7] ) + abs( $textBox[1] ) ) * 1 );

// Determine where to set the X position of the text box so it is centered
$textX = ceil( ( $width - $textWidth ) / 2 );
// Determine where to set the Y position of the text box so it is centered
$textY = ceil( ( $height - $textHeight ) / 2 + $textHeight );

// Create the rectangle with the specified background color
imageFilledRectangle( $img, 0, 0, $width, $height, $bg_color );
// Create and positions the text
imagettftext( $img, $fontsize, $text_angle, $textX, $textY, $fg_color, $font, $text );


function process_output_buffer( $buffer = '' ) {
	$buffer = trim( $buffer );
	if ( strlen( $buffer ) == 0 ) {
		return '';
	}
	return $buffer;
}
// Start output buffering so we can determine the Content-Length of the file
ob_start( 'process_output_buffer' );

// Create the final image based on the provided file format.
switch ( $file_format ) {

	case 'gif':
		imagegif( $img );
		break;

	case 'png':
		imagepng( $img );
		break;

	case 'webp':
		if ( ! function_exists( 'imagewebp' ) ) {
			die( $file_format . ' is not supported!' );
		}
		imagewebp( $img );
		break;

	case 'jpg':
	case 'jpeg':
		imagejpeg( $img );
		break;

	default:
		die( $file_format . ' is not supported!' );
}
$output = ob_get_contents();

ob_end_clean();

// Caching Headers
$offset = 60 * 60 * 24 * 90; // 90 Days
header( 'Cache-Control: public, max-age=' . $offset );
// Set a far future expire date. This keeps the image locally cached by the user for less hits to the server
header( 'Expires: ' . gmdate( DATE_RFC1123, time() + $offset ) );
header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', time() ) . ' GMT' );
// Set the header so the browser can interpret it as an image and not a bunch of weird text
header( 'Content-type: image/' . $file_format );
header( 'Content-Length: ' . strlen( $output ) );

echo $output;

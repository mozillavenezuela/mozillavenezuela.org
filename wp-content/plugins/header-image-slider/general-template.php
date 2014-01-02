<?php

/**
 * Retrieve header image for custom header.
 *
 * @since 0.1
 * @uses HEADER_IMAGE
 * @uses HEADER_IMAGE_WIDTH
 * @uses HEADER_IMAGE_HEIGHT
 *
 * @return string
 */
function boom_header_image( $width = null, $height = null ) {
	$default = defined( 'HEADER_IMAGE' ) ? HEADER_IMAGE : '';
	$url = get_theme_mod( 'header_image', $default );
	$width = defined( 'HEADER_IMAGE_WIDTH' ) ? HEADER_IMAGE_WIDTH : $width;
	$height = defined( 'HEADER_IMAGE_HEIGHT' ) ? HEADER_IMAGE_HEIGHT : $height;

	if ( 'remove-header' == $url ) {
		return false;
	} elseif( 'boom-slider-uploaded' == $url || 'boom-slider-default' == $url ) { // slider
		echo boom_build_nivo_slider( boom_get_header_images(), $width, $height );
		return;
	} elseif ( is_random_header_image() ) { // random header mode
		$url = get_random_header_image();
	}

	if ( is_ssl() )
		$url = str_replace( 'http://', 'https://', $url );
	else
		$url = str_replace( 'https://', 'http://', $url );
	$output = "<img src='". esc_url_raw( $url ) . "' alt='' />";

	echo $output;
}

/**
 * Returns a one dimensional array of header images URIs.
 *
 * @since 0.2
 * @return array slides
 */
function boom_get_header_images() {
	$available_headers = array();
	if( get_theme_mod( 'header_image', '' ) == 'boom-slider-uploaded' ) {
		$available_headers = get_option( 'boom_available_headers' );
	} elseif( get_theme_mod( 'header_image', '' ) == 'boom-slider-default' ) {
		global $_wp_default_headers;
		array_walk( $_wp_default_headers, 'boom_fix_image_urls' );
		foreach( $_wp_default_headers as $header ) {
			$available_headers[] = $header['url'];
		}
	} else {
		return array();
	}
	return $available_headers;
}

/**
 * Builds the HTML output for Nivo Slider
 *
 * @since 0.1
 * @return void
 */
function boom_build_nivo_slider( $slides = array(), $width = null, $height = null ) {
	if( $width )
		$width = "max-width: {$width}px;";
	if( $height )
		$height = "max-height: {$height}px";

	$nivo_slides = '';
	foreach( $slides as $slide ) {
		$nivo_slides .= "<img src='{$slide}' alt='' />";
	}
	$options = boom_nivo_slider_options();
	echo "
		<div class='slider-wrapper theme-default'>
			<div class='ribbon'></div>
			<div style='{$width} {$height}' class='headerSlider nivoSlider'>
				{$nivo_slides}
			</div>
		</div>
		<script>
			jQuery('div.headerSlider').nivoSlider({
				{$options}
			});
		</script>
	";
}

function boom_nivo_slider_options() {
	extract( boom_slider_get_option() );
	$pauseOnHover = ( $hoverpause == 'on' ) ? 'true' : 'false';
	$keyboardNav = ( $keyboard == 'on' ) ? 'true' : 'false';
	$directionNav = ( $direction !== 'hide' ) ? 'true' : 'false';
	$directionNavHide = ( $direction == 'rollover' ) ? 'true' : 'false';
	$controlNav = ( $navcontrols == 'on' ) ? 'true' : 'false';
	return "
		effect: '{$transition}',
		slices: {$slices},
		animSpeed: {$speed},
		pauseTime: {$pause},
		pauseOnHover: {$pauseOnHover},
		keyboardNav: {$keyboardNav},
		directionNav: {$directionNav},
		directionNavHide: {$directionNavHide},
		controlNav: {$controlNav}
	";
}
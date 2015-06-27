<?php
/**
 * Yoko Theme Customizer
 *
 * @package Yoko
 */

/**
 * Implement Theme Customizer additions and adjustments.
 *
 * @param WP_Customize_Manager $wp_customize Theme Customizer object.
 *
 * @since Yoko 1.0
 */
function yoko_customize_register( $wp_customize ) {

	$wp_customize->get_setting( 'header_textcolor' )->transport = 'postMessage';

	$wp_customize->add_section( 'yoko_themeoptions', array(
		'title'         => __( 'Theme', 'yoko' ),
		'priority'      => 135,
	) );

	// Custom Colors.
	$wp_customize->add_setting( 'link_color' , array(
    	'default'     => '#009BC2',
		'transport'   => 'refresh',
		'sanitize_callback'	=> 'sanitize_hex_color',
	) );

	$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'special_color', array(
		'label'        => __( 'Link Color', 'yoko' ),
		'section'    => 'colors',
		'settings'   => 'link_color',
	) ) );

}
add_action( 'customize_register', 'yoko_customize_register' );


/**
 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
 */
function yoko_customize_preview_js() {
	wp_enqueue_script( 'yoko-customizer', get_template_directory_uri() . '/js/customizer.js', array( 'customize-preview' ), '20150203', true );
}
add_action( 'customize_preview_init', 'yoko_customize_preview_js' );
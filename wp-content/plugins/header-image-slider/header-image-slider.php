<?php
/*
Plugin Name: Header Image Slider
Description: Use WP3.0 Header feature to build an image slider.
Author: Hassan Derakhshandeh
Version: 0.3
Author URI: http://tween.ir/


		* 	Copyright (C) 2011  Hassan Derakhshandeh
		*	http://tween.ir/
		*	hassan.derakhshandeh@gmail.com

		This program is free software; you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation; either version 2 of the License, or
		(at your option) any later version.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program; if not, write to the Free Software
		Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class Boom_Header_Image_Slider {

	function Boom_Header_Image_Slider() {
		add_action( 'admin_head-appearance_page_custom-header', array( &$this, 'admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'queue_thickbox' ) );
		add_action( 'admin_init', array( &$this, 'remove_image' ) );
		add_action( 'admin_init', array( &$this, 'set_theme_mod' ) );
		add_action( 'admin_init', array( &$this, 'save_options' ) );
		add_action( 'template_redirect', array( &$this, 'queue_slider' ) );
		add_action( 'after_setup_theme', array( &$this, 'check_theme_support' ), 100 );
		add_action( 'wp_footer', array( &$this, 'auto_insert' ) );

		require_once( dirname( __FILE__ ) . '/general-template.php' );
	}

	/**
	 * Prints JavaScript codes in the header admin page, required to add the slider option
	 * and the remove image link.
	 *
	 * @since 0.1
	 * @return void
	 */
	function admin_scripts() { ?>
		<script>
			jQuery(function($){
			<?php
			$uploaded_headers = get_uploaded_header_images();
			if( ! empty( $uploaded_headers ) ) : ?>
				$('.available-headers').closest('form').find('table tr:first') // prevents showing remove links for default headers
				.find('div.default-header')
				.each(function(){
					$(this).append('<br/><a href="#" class="remove_header_image delete" style="padding-left: 25px;"><?php _e( 'Remove' ) ?></a>');
					<?php
						// convert radio buttons to checkboxes
						$available_headers = get_option( 'boom_available_headers' );
						if( get_theme_mod( 'header_image', '' ) == 'boom-slider-uploaded' ) : ?>
						var no_header = <?php if( ! $available_headers ) echo 'true'; else echo 'false'; ?>;
						var headers = <?php echo str_replace( '\/', '/', json_encode( boom_get_header_images() ) ) ?>;
						var $this = $(this),
							$input = $this.find('input'),
							$new_input = $input.clone();
						$new_input.attr({
							type: 'checkbox',
							name: 'images[]',
							value: $this.find('img').attr('src'),
						}).insertBefore( $input );
						if( $.inArray( $new_input.val(), headers ) >= 0 || no_header )
							$new_input.attr( 'checked', 'checked' );
						$input.remove();
					<?php endif; ?>
				});
				$('a.remove_header_image').live('click', function(){
					thiz = $(this);
					if( window.confirm(commonL10n.warnDelete) ) {
						$.ajax({
							url: window.location.href,
							type: 'POST',
							data: {
								'header_image_remove': thiz.parent().find('img').attr('src')
							},
							success: function(data){
								thiz.parent().fadeOut('slow', function(){
									$(this).remove();
								});
							}
						});
					}
					return false;
				});
			<?php endif; ?>
				$('div.random-header').each(function(){
					if( $('input[name="default-header"]', this).val() == 'random-default-image' ) {
						$(this).after("<div class='random-header'><label><input name='default-header' type='radio' value='boom-slider-default' <?php checked( 'boom-slider-default', get_theme_mod( 'header_image', '' ) ) ?> id='boom-slider-default'><strong>Slider</strong> <a class='slider-options-open button-secondary'>Settings</a></label></div>");
					} else {
						$(this).after("<div class='random-header'><label><input name='default-header' type='radio' value='boom-slider-uploaded' <?php checked( 'boom-slider-uploaded', get_theme_mod( 'header_image', '' ) ) ?> id='boom-slider-uploaded'><strong>Slider</strong> <a class='slider-options-open button-secondary'>Settings</a></label></div>");
					}
				});
				$('a.slider-options-open').live('click', function(){
					jQuery.get( "<?php echo plugins_url( 'options.php', __FILE__ ) ?>", function(b) {
						jQuery("#slider-options").remove();
						$(b).hide().appendTo('body');
						var width = jQuery(window).width(),
							height = jQuery(window).height();
						width = 720 < width ? 720 : width;
						width -= 80;
						height -= 84;
						tb_show( "Slider Options", "#TB_inline?width=" + width + "&height=" + height + "&inlineId=slider-options");
					})
				});
			});
		</script>
	<?php }

	/**
	 * Queue thickbox stylesheet and script
	 * Our slider option page appears as a modal window.
	 *
	 * @since 0.2
	 * @uses add_thickbox
	 * @return void
	 */
	function queue_thickbox() {
		global $hook_suffix;

		if( $hook_suffix == "appearance_page_custom-header" ) {
			add_thickbox();
		}
	}

	/**
	 * Remove header image
	 *
	 * @since 0.1
	 * @uses wp_delete_attachment
	 * @return void
	 */
	function remove_image() {
		global $wpdb;

		if( isset( $_POST['header_image_remove'] ) ) {
			if( ! $post = $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND post_content = %s", $_POST['header_image_remove'] ) ) ) {
				return;
			}
			wp_delete_attachment( $post->ID, true ); // true: force_delete, bypass trash
		}
	}

	/**
	 * Manually set the header type as slider if user has chosen it
	 *
	 * @since 0.1
	 * @return void
	 */
	function set_theme_mod() {
		if( isset( $_POST['default-header'] ) ) {
			if( $_POST['default-header'] == 'boom-slider-default' ) {
				set_theme_mod( 'header_image', 'boom-slider-default' );
			} elseif( $_POST['default-header'] == 'boom-slider-uploaded' ) {
				set_theme_mod( 'header_image', 'boom-slider-uploaded' );
				if( empty( $_POST['images'] ) ) {
					$available_headers = array();
					$headers = get_uploaded_header_images();
					foreach( $headers as $header ) {
						$available_headers[] = $header['url'];
					}
					update_option( 'boom_available_headers', $available_headers );
				} else {
					update_option( 'boom_available_headers', $_POST['images'] );
				}
			}
		}
	}

	/**
	 * Save slider options in the database
	 * The data is sent using AJAX, so we terminate the process after options saved.
	 *
	 * @since 0.2
	 * @return void
	 */
	function save_options() {
		if( isset( $_POST['slider'] ) ) {
			update_option( 'slider_options', $_POST['slider'] );
			exit;
		}
	}

	/**
	 * Queue slider style and script for the front-end
	 *
	 * @since 0.1
	 * @return void
	 */
	function queue_slider() {
		$header_mode = get_theme_mod( 'header_image', '' );
		if( $header_mode == 'boom-slider-uploaded' || $header_mode == 'boom-slider-default' ) {
			/**
			 * Nivo Slider
			 * @link http://nivo.dev7studios.com/
			 */
			wp_enqueue_script( 'nivo', plugins_url( 'nivo-slider/jquery.nivo.slider.pack.js', __FILE__ ) , array( 'jquery' ), '2.6' );
			wp_enqueue_style( 'nivo', plugins_url( 'slider.css.php', __FILE__ ), array(), '2.6' );
		}
	}

	/**
	 * Register support for WP 3.0 Custom Headers if the theme doesn't support it.
	 * Note that you need to use the boom_header_image() template tag where you want
	 * your slider to show up.
	 *
	 * @since 0.2
	 * @return void
	 */
	function check_theme_support() {
		if( ! current_theme_supports( 'custom-header' ) ) {
			global $chsfp;
			$chsfp = true;
			add_theme_support( 'custom-header' );
			define( 'HEADER_TEXTCOLOR', '' );
			define( 'NO_HEADER_TEXT', true );
			if( ! defined( 'HEADER_IMAGE_WIDTH' ) ) define( 'HEADER_IMAGE_WIDTH', boom_slider_get_option('width') );
			if( ! defined( 'HEADER_IMAGE_HEIGHT' ) ) define( 'HEADER_IMAGE_HEIGHT', boom_slider_get_option('height') );
			add_custom_image_header( create_function('',''), create_function('','') );
		}
	}

	/**
	 * Adds some scripts to the front-page to build the slider images.
	 * This works only if your theme supports WP3.0 Custom Headers,
	 * otherwise you have to use the boom_header_image() template tag.
	 *
	 * @since 0.2
	 * @return void
	 */
	function auto_insert() {
		if( boom_slider_get_option('autoinsert') ) : ?>
<script>
jQuery(function(a){function b(b){imgs="";a.each(b,function(a,b){imgs+='<img src="'+b+'" alt="" />'});return'<div class="slider-wrapper theme-default"><div class="ribbon"></div><div class="nivoSlider headerSlider">'+imgs+"</div></div>"}var c=<?php echo str_replace("\\/", "/", json_encode( boom_get_header_images() ) ) ?>,d=a('img[src="http://boom-slider-default"], img[src="http://boom-slider-uploaded"]'),e=d.parent();if(e.is("a")){e.after(b(c)).remove()}else{d.after(b(c)).remove()}var f=a("div.headerSlider");f.find("img:first").load(function(){var b=a(this).width(),c=a(this).height();f.css({maxWidth:b,maxHeight:c}).nivoSlider({<?php echo boom_nivo_slider_options() ?>})})})
</script>
		<?php endif;
	}
}
new Boom_Header_Image_Slider();

/**
 * Returns the number of available header images.
 *
 * @since 0.2
 * @return int count
 */
function boom_count_header_images() {
	return count( boom_get_header_images() );
}

/**
 * Fix the URL of default images
 *
 * This function gets called on all default images to point to the header image.
 *
 * @since 0.1
 */
function boom_fix_image_urls( &$value, $key ) {
	$value['url'] = sprintf( $value['url'], get_template_directory_uri(), get_stylesheet_directory_uri() );
}


/**
 * Default slider options
 *
 * @since 0.2
 * @return array options
 */
function boom_slider_default_options() {
	return array(
		'autoinsert'=> '',
		'width'		=> 940,
		'height'	=> 250,
		'transition'=> 'random',
		'slices'	=> 15,
		'speed'		=> 500,
		'hoverpause'=> 'on',
		'pause'		=> 3000,
		'direction'	=> 'rollover',
		'arrows'	=> 'white',
		'navcontrols'=> 'on',
		'bullets'	=> 'black',
		'bottom'	=> '-42',
		'keyboard'	=> '',
	);
}

/**
 * Gets the slider settings
 *
 * Uses the default options if there's no saved options
 *
 * @param $option retrieve a specific slider option, if it's not set returns an array of all options
 * @since 0.1
 * @return mixed
 */
function boom_slider_get_option( $option = null ) {
	global $boom_slider_options;

	if( ! isset( $boom_slider_options ) ) {
		$boom_slider_options = get_option( 'slider_options', boom_slider_default_options() );
	}
	if( $option )
		return $boom_slider_options[$option];
	else
		return $boom_slider_options;
}
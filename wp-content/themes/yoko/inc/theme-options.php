<?php
/**
 * Yoko Theme Options
 *
 * @package WordPress
 * @subpackage Yoko
 */

function yoko_admin_enqueue_scripts( $hook_suffix ) {
	if ( $hook_suffix != 'appearance_page_theme_options' )
		return;

	wp_enqueue_style( 'yoko-theme-options', get_template_directory_uri() . '/includes/theme-options.css', false );
}
add_action( 'admin_enqueue_scripts', 'yoko_admin_enqueue_scripts' );

// Default options values
$yoko_options = array(
	'custom_logo' => ''
);

if ( is_admin() ) : // Load only if we are viewing an admin page

function yoko_register_settings() {
	// Register the settings
	register_setting( 'yoko_theme_options', 'yoko_options', 'yoko_validate_options' );
}

add_action( 'admin_init', 'yoko_register_settings' );


function yoko_theme_options() {
	// Add theme options page to the addmin menu
	add_theme_page( __( 'Theme Options', 'yoko'), __( 'Theme Options', 'yoko'), 'edit_theme_options', 'theme_options', 'yoko_theme_options_page');
}

add_action( 'admin_menu', 'yoko_theme_options' );

// Function to generate options page
function yoko_theme_options_page() {
	global $yoko_options, $yoko_categories, $yoko_layouts;

	if ( ! isset( $_REQUEST['updated'] ) )
		$_REQUEST['updated'] = false; // This checks whether the form has just been submitted. ?>

	<div class="wrap">

	<h2><?php printf( __( '%s Theme Options', 'yoko' ), wp_get_theme() ); ?></h2>

	<?php if ( false !== $_REQUEST['updated'] ) : ?>
	<div class="updated fade"><p><strong><?php _e( 'Options saved', 'yoko' ); ?></strong></p></div>
	<?php endif; // If the form has just been submitted, this shows the notification ?>

	<form method="post" action="options.php">

	<?php $settings = get_option( 'yoko_options', $yoko_options ); ?>

	<?php settings_fields( 'yoko_theme_options' );
	/* This function outputs some hidden fields required by the form,
	including a nonce, a unique number used to ensure the form has been submitted from the admin page
	and not somewhere else, very important for security */ ?>

	<table class="form-table">

		<tr valign="top"><th scope="row"><label for="custom_logo"><?php _e('Custom Logo Image URL', 'yoko'); ?></label></th>
			<td>
				<input class="regular-text" id="custom_logo" name="yoko_options[custom_logo]" type="text" value="<?php  echo esc_attr($settings['custom_logo']); ?>" />
				<span class="description"> <a href="<?php echo home_url(); ?>/wp-admin/media-new.php" target="_blank"><?php _e('Upload your own logo image', 'yoko'); ?></a> <?php _e(' using the WordPress Media Library and insert the URL here', 'yoko'); ?> </span>
			</td>
		</tr>
	</table>

	<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Options', 'yoko'); ?>" /></p>

	</form>

	</div>

	<?php
}

function yoko_validate_options( $input ) {
	global $yoko_options, $yoko_categories, $yoko_layouts;

	$settings = get_option( 'yoko_options', $yoko_options );

	return $input;
}

endif;  // EndIf is_admin()

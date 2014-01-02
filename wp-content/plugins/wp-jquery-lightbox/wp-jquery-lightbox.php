<?php
/*
Plugin Name: wp-jquery-lightbox
Plugin URI: http://wordpress.org/extend/plugins/wp-jquery-lightbox/
Description: A drop in replacement for LightBox-2 and similar plugins. Uses jQuery to save you from the JS-library mess in your header. :)
Version: 1.4.5
Author: Ulf Benjaminsson
Author URI: http://www.ulfben.com
License: GPLv2 or later
*/
add_action( 'plugins_loaded', 'jqlb_init' );
global $jqlb_group;
$jqlb_group = -1;
function jqlb_init() {
	if(!defined('ULFBEN_DONATE_URL')){
		define('ULFBEN_DONATE_URL', 'http://www.amazon.com/gp/registry/wishlist/2QB6SQ5XX2U0N/105-3209188-5640446?reveal=unpurchased&filter=all&sort=priority&layout=standard&x=21&y=17');
	}
	//JQLB_PLUGIN_DIR == plugin_dir_path(__FILE__); 
	//JQLB_URL = plugin_dir_url(__FILE__);
	//JQLB_STYLES_URL = plugin_dir_url(__FILE__).'styles/'
	//JQLB_LANGUAGES_DIR = plugin_dir_path(__FILE__) . 'languages/'
	define('JQLB_SCRIPT', 'jquery.lightbox.min.js');
	define('JQLB_TOUCH_SCRIPT', 'jquery.touchwipe.min.js');
	load_plugin_textdomain('jqlb', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');	
	//load_plugin_textdomain('jqlb', false, plugin_dir_path(__FILE__).'languages/');
	add_action('admin_init', 'jqlb_register_settings');
	add_action('admin_menu', 'jqlb_register_menu_item');
	add_action('wp_print_styles', 'jqlb_css');	
	add_action('wp_print_scripts', 'jqlb_js');
	add_filter('plugin_row_meta', 	'jqlb_set_plugin_meta', 2, 10);	
	add_filter('the_content', 'jqlb_autoexpand_rel_wlightbox', 99);
	add_filter('post_gallery', 'jqlb_filter_groups', 10, 2);	
	if(get_option('jqlb_comments') == 1){
		remove_filter('pre_comment_content', 'wp_rel_nofollow');
		add_filter('comment_text', 'jqlb_lightbox_comment', 99);
	}
}
function jqlb_set_plugin_meta( $links, $file ) { // Add a link to this plugin's settings page
	static $this_plugin;
	if(!$this_plugin) $this_plugin = plugin_basename(__FILE__);
	if($file == $this_plugin) {
		$settings_link = '<a href="options-general.php?page=jquery-lightbox-options">'.__('Settings', 'jqlb').'</a>';	
		array_unshift($links, $settings_link);
	}
	return $links; 
}
function jqlb_add_admin_footer(){ //shows some plugin info in the footer of the config screen.
	$plugin_data = get_plugin_data(__FILE__);
	printf('%1$s by %2$s (who <a href="'.ULFBEN_DONATE_URL.'">appreciates books</a>) :)<br />', $plugin_data['Title'].' '.$plugin_data['Version'], $plugin_data['Author']);		
}	
function jqlb_register_settings(){
	register_setting( 'jqlb-settings-group', 'jqlb_automate', 'jqlb_bool_intval'); 
	register_setting( 'jqlb-settings-group', 'jqlb_showinfo', 'jqlb_bool_intval');
	register_setting( 'jqlb-settings-group', 'jqlb_comments', 'jqlb_bool_intval'); 
	register_setting( 'jqlb-settings-group', 'jqlb_resize_on_demand', 'jqlb_bool_intval');
	register_setting( 'jqlb-settings-group', 'jqlb_show_download', 'jqlb_bool_intval');
	register_setting( 'jqlb-settings-group', 'jqlb_navbarOnTop', 'jqlb_bool_intval');
	register_setting( 'jqlb-settings-group', 'jqlb_margin_size', 'floatval');
	register_setting( 'jqlb-settings-group', 'jqlb_resize_speed', 'jqlb_pos_intval');
	register_setting( 'jqlb-settings-group', 'jqlb_slideshow_speed', 'jqlb_pos_intval');	
	register_setting( 'jqlb-settings-group', 'jqlb_help_text');
	register_setting( 'jqlb-settings-group', 'jqlb_link_target');	
	add_option('jqlb_help_text', '');
	add_option('jqlb_showinfo', 1);
	add_option('jqlb_link_target', '_self');
	add_option('jqlb_automate', 1); //default is to auto-lightbox.
	add_option('jqlb_comments', 1);
	add_option('jqlb_resize_on_demand', 0); 
	add_option('jqlb_show_download', 0); 
	add_option('jqlb_navbarOnTop', 0);
	add_option('jqlb_resize_speed', 400); 
	add_option('jqlb_slideshow_speed', 4000); 	
}
function jqlb_register_menu_item() {		
	add_options_page('jQuery Lightbox Options', 'jQuery Lightbox', 'manage_options', 'jquery-lightbox-options', 'jqlb_options_panel');
}
function jqlb_get_locale(){
	//$lang_locales and ICL_LANGUAGE_CODE are defined in the WPML plugin (http://wpml.org/)
	global $lang_locales;
	if (defined('ICL_LANGUAGE_CODE') && isset($lang_locales[ICL_LANGUAGE_CODE])){
		$locale = $lang_locales[ICL_LANGUAGE_CODE];
	} else {
		$locale = get_locale();
	}
	return $locale;
}
function jqlb_css(){	
	if(is_admin() || is_feed()){return;}
	$locale = jqlb_get_locale();
	$fileName = "lightbox.min.{$locale}.css";	
	//$fileName = "lightbox.css";	
	$path = plugin_dir_path(__FILE__)."styles/{$fileName}";
	if(!is_readable($path)){
		$fileName = 'lightbox.min.css';
	}
	wp_enqueue_style('jquery.lightbox.min.css', plugin_dir_url(__FILE__).'styles/'.$fileName, false, '1.4');	
}

function jqlb_js() {			   	
	if(is_admin() || is_feed()){return;}
	wp_enqueue_script('jquery', '', array(), false, true);
	wp_enqueue_script('wp-jquery-lightbox-swipe', plugins_url(JQLB_TOUCH_SCRIPT, __FILE__),  Array('jquery'), '1.4', true);	
	wp_enqueue_script('wp-jquery-lightbox', plugins_url(JQLB_SCRIPT, __FILE__),  Array('jquery'), '1.4', true);
	wp_localize_script('wp-jquery-lightbox', 'JQLBSettings', array(
		'showInfo'	=> get_option('jqlb_showinfo'),
		'fitToScreen' => get_option('jqlb_resize_on_demand'),
		'resizeSpeed' => get_option('jqlb_resize_speed'),
		'displayDownloadLink' => get_option('jqlb_show_download'),
		'navbarOnTop' => get_option('jqlb_navbarOnTop'),		
		'resizeCenter' => get_option('jqlb_resizeCenter'),
		'marginSize' => get_option('jqlb_margin_size'),
		'linkTarget' => get_option('jqlb_link_target'),
		'slideshowSpeed' => get_option('jqlb_slideshow_speed'),
		/* translation */
		'help' => __(get_option('jqlb_help_text'), 'jqlb'),
		'prevLinkTitle' => __('previous image', 'jqlb'),
		'nextLinkTitle' => __('next image', 'jqlb'),		
		'closeTitle' => __('close image gallery', 'jqlb'),
		'image' => __('Image ', 'jqlb'),
		'of' => __(' of ', 'jqlb'),
		'download' => __('Download', 'jqlb'),
		'pause' => __('(pause slideshow)', 'jqlb'),
		'play' => __('(play slideshow)', 'jqlb')
	));
}

function jqlb_lightbox_comment($comment){
	$comment = str_replace('rel=\'external nofollow\'','', $comment);
	$comment = str_replace('rel=\'nofollow\'','', $comment);
	$comment = str_replace('rel="external nofollow"','', $comment);
	$comment = str_replace('rel="nofollow"','', $comment);
	return jqlb_autoexpand_rel_wlightbox($comment);
}

function jqlb_autoexpand_rel_wlightbox($content) {
	if(get_option('jqlb_automate') == 1){
		global $post;	
		$id = isset($post->ID) ? $post->ID : -1;
		$content = jqlb_do_regexp($content, $id);
	}			
	return $content;
}
function jqlb_apply_lightbox($content, $id = -1){
	if(!isset($id) || $id === -1){
		$id = time().rand(0, 32768);
	}
	return jqlb_do_regexp($content, $id);
}

/* automatically insert rel="lightbox[nameofpost]" to every image with no manual work. 
	if there are already rel="lightbox[something]" attributes, they are not clobbered. 
	Michael Tyson, you are a regular expressions god! - http://atastypixel.com */
function jqlb_do_regexp($content, $id){
	$id = esc_attr($id);			
	$pattern = "/(<a(?![^>]*?rel=['\"]lightbox.*)[^>]*?href=['\"][^'\"]+?\.(?:bmp|gif|jpg|jpeg|png)(\?\S{0,}){0,1}['\"][^\>]*)>/i";
	$replacement = '$1 rel="lightbox['.$id.']">';
	return preg_replace($pattern, $replacement, $content);
}

function jqlb_filter_groups($html, $attr) {//runs on the post_gallery filter.
	global $jqlb_group;
	if(empty($attr['group'])){
		$jqlb_group = -1;
		remove_filter('wp_get_attachment_link','jqlb_lightbox_gallery_links',10,1);			
	}else{
		$jqlb_group = $attr['group'];		
		add_filter('wp_get_attachment_link','jqlb_lightbox_gallery_links',10,1);	
	}
	return '';
}
function jqlb_lightbox_gallery_links($html){ //honors our custom group-attribute of the gallery shortcode.   
	global $jqlb_group;
	if(!isset($jqlb_group) || $jqlb_group == -1){return $html;}
    return str_replace('<a','<a rel="lightbox['.$jqlb_group.']"', $html);    
}

function jqlb_bool_intval($v){
	return $v == 1 ? '1' : '0';
}

function jqlb_pos_intval($v){
	return abs(intval($v));
}
function jqlb_options_panel(){
	if(!function_exists('current_user_can') || !current_user_can('manage_options')){
			die(__('Cheatin&#8217; uh?', 'jqlb'));
	} 
	add_action('in_admin_footer', 'jqlb_add_admin_footer');
	?>
	
	<div class="wrap">
	<h2>jQuery Lightbox</h2>	
	<?php include_once(plugin_dir_path(__FILE__).'about.php'); ?>
	<form method="post" action="options.php">
		<table>
		<?php settings_fields('jqlb-settings-group'); ?>
			<tr valign="baseline" colspan="2">
				<td colspan="">
					<?php $check = get_option('jqlb_automate') ? ' checked="yes" ' : ''; ?>
					<input type="checkbox" id="jqlb_automate" name="jqlb_automate" value="1" <?php echo $check; ?>/>
					<label for="jqlb_automate" title="<?php _e('Let the plugin add necessary html to image links', 'jqlb') ?>"> <?php _e('Auto-lightbox image links', 'jqlb') ?></label>
				</td>
			</tr>
			<tr valign="baseline" colspan="2">
				<td colspan="2">
					<?php $check = get_option('jqlb_comments') ? ' checked="yes" ' : ''; ?>
					<input type="checkbox" id="jqlb_comments" name="jqlb_comments" value="1" <?php echo $check; ?>/>
					<label for="jqlb_comments" title="<?php _e('Note: this will disable the nofollow-attribute of comment links, that otherwise interfere with the lightbox.', 'jqlb') ?>"> <?php _e('Enable lightbox in comments (disables <a href="http://codex.wordpress.org/Nofollow">the nofollow attribute!</a>)', 'jqlb') ?></label>
				</td>
			</tr>
			<tr>
			<td>
					<?php $check = get_option('jqlb_showinfo') ? ' checked="yes" ' : ''; ?>
					<input type="checkbox" id="jqlb_showinfo" name="jqlb_showinfo" value="1" <?php echo $check; ?> />
					<label for="jqlb_showinfo"> <?php _e('Show title & caption', 'jqlb') ?> </label>
				</td>
			</tr>
			<tr valign="baseline" colspan="2">				
				<td>
					<?php $check = get_option('jqlb_show_download') ? ' checked="yes" ' : ''; ?>
					<input type="checkbox" id="jqlb_show_download" name="jqlb_show_download" value="1" <?php echo $check; ?> />
					<label for="jqlb_show_download"> <?php _e('Show download link', 'jqlb') ?> </label>
				</td>
				<td>
				<?php $target = get_option('jqlb_link_target'); ?>
				<label for="jqlb_link_target" title="<?php _e('_blank: open the image in a new window or tab
_self: open the image in the same frame as it was clicked (default)
_parent: open the image in the parent frameset
_top: open the image in the full body of the window', 'jqlb') ?>"><?php _e('Target for download link:', 'jqlb'); ?></label> 
				<select id="jqlb_link_target" name="jqlb_link_target">
					<option <?php if ('_blank' == $target)echo 'selected="selected"'; ?>>_blank</option>
					<option <?php if ('_self' == $target)echo 'selected="selected"'; ?>>_self</option>
					<option <?php if ('_top' == $target)echo 'selected="selected"'; ?>>_top</option>
					<option <?php if ('_parent' == $target)echo 'selected="selected"'; ?>>_parent</option>
				</select>							
				</td>
			</tr>
      <tr valign="baseline" colspan="2">
        <td colspan="2"> 
          <?php $check = get_option('jqlb_navbarOnTop') ? ' checked="yes" ' : ''; ?>
          <input type="checkbox" id="jqlb_navbarOnTop" name="jqlb_navbarOnTop" value="1" <?php echo $check; ?> />
          <label for="jqlb_navbarOnTop">
            <?php _e('Show image info on top', 'jqlb') ?>
          </label>
        </td>
      </tr>
      <tr valign="baseline" colspan="2">
			<td>
				<?php $check = get_option('jqlb_resize_on_demand') ? ' checked="yes" ' : ''; ?>
				<input type="checkbox" id="jqlb_resize_on_demand" name="jqlb_resize_on_demand" value="1" <?php echo $check; ?> />
				<label for="jqlb_resize_on_demand"><?php _e('Shrink large images to fit smaller screens', 'jqlb') ?></label> 
			</td>
			<?php IF($check != ''): ?>
			<td>					
				<input type="text" id="jqlb_margin_size" name="jqlb_margin_size" value="<?php echo floatval(get_option('jqlb_margin_size')) ?>" size="3" />
				<label for="jqlb_margin_size" title="<?php _e('Keep a distance between the image and the screen edges.', 'jqlb') ?>"><?php _e('Minimum margin to screen edge (default: 0)', 'jqlb') ?></label>			
			</td>
			<?php ENDIF; ?>
		</tr>					
		<tr valign="baseline" colspan="2">
			<td colspan="2">					
				<input type="text" id="jqlb_resize_speed" name="jqlb_resize_speed" value="<?php echo intval(get_option('jqlb_resize_speed')) ?>" size="3" />
				<label for="jqlb_resize_speed"><?php _e('Animation duration (in milliseconds) ', 'jqlb') ?></label>			
			</td>
		</tr>
		<tr valign="baseline" colspan="2">
			<td colspan="2">					
				<input type="text" id="jqlb_slideshow_speed" name="jqlb_slideshow_speed" value="<?php echo intval(get_option('jqlb_slideshow_speed')) ?>" size="3" />
				<label for="jqlb_slideshow_speed"><?php _e('Slideshow speed (in milliseconds). 0 to disable.', 'jqlb') ?></label>			
			</td>
		</tr>
		<tr valign="baseline" colspan="2">			
			<td>
				<input type="text" id="jqlb_help_text" name="jqlb_help_text" value="<?php echo get_option('jqlb_help_text'); ?>" size="30" />		
				<label for="jqlb_help_text"><?php _e('Help text (default: none) ', 'jqlb'); ?></label>						
			</td>			
		</tr>			
		 </table>
		<p style="font-size:xx-small;font-style:italic;"><?php _e('Browse images with your keyboard: Arrows or P(revious)/N(ext) and X/C/ESC for close.', 'jqlb'); ?></p>
		<p class="submit">
		  <input type="submit" name="Submit" value="<?php _e('Save Changes', 'jqlb') ?>" />
		</p>
	</form>
	<?php
		$locale = jqlb_get_locale();
		$diskfile = plugin_dir_path(__FILE__)."languages/howtouse-{$locale}.html";
		if (!file_exists($diskfile)){
			$diskfile = plugin_dir_path(__FILE__).'languages/howtouse.html';
		}
		$text = false;
		if(function_exists('file_get_contents')){
			$text = @file_get_contents($diskfile);
		} else {
			$text = @file($diskfile);
			if($text !== false){
				$text = implode("", $text);
			}
		}
		if($text === false){
			$text = '<p>The documentation files are missing! Try <a href="http://wordpress.org/extend/plugins/wp-jquery-lightbox/">downloading</a> and <a href="http://wordpress.org/extend/plugins/wp-jquery-lightbox/installation/">re-installing</a> this plugin.</p>';
		}
		echo $text;
	?>
	</div>	
<?php }?>

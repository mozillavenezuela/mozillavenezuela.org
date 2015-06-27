<?php
/**
 * @package WordPress
 * @subpackage Yoko
 */

/**
 * Make theme available for translation
 * Translations can be filed in the /languages/ directory
 */
load_theme_textdomain( 'yoko', get_template_directory() . '/languages' );

/**
 * Set the content width based on the theme's design and stylesheet.
 */
if ( ! isset( $content_width ) )
	$content_width = 620;

/**
 * Tell WordPress to run yoko() when the 'after_setup_theme' hook is run.
 */
add_action( 'after_setup_theme', 'yoko' );

if ( ! function_exists( 'yoko' ) ):

/**
 * Returns the Google font stylesheet URL if available.
 */

function yoko_fonts_url() {
	$fonts_url = '';

	/* Translators: If there are characters in your language that are not
	 * supported by PT Sans or Raleway translate this to 'off'. Do not translate
	 * into your own language.
	 */
	$droid_sans = _x( 'on', 'Droid Sans font: on or off', 'yoko' );

	$droid_serif = _x( 'on', 'Droid Serif font: on or off', 'yoko' );

	if ( 'off' !== $droid_sans || 'off' !== $droid_serif ) {
		$font_families = array();

		if ( 'off' !== $droid_sans )
			$font_families[] = 'Droid Sans:400,700';

		if ( 'off' !== $droid_serif )
			$font_families[] = 'Droid Serif:400,700,400italic,700italic';

		$query_args = array(
			'family' => urlencode( implode( '|', $font_families ) ),
			'subset' => urlencode( 'latin,latin-ext' ),
		);
		$fonts_url = add_query_arg( $query_args, "//fonts.googleapis.com/css" );
	}

	return $fonts_url;
}


/**
 * Enqueue scripts and styles.
 */
function yoko_scripts() {
	global $wp_styles;

	// Adds JavaScript to pages with the comment form to support sites with threaded comments (when in use)
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) )
	wp_enqueue_script( 'comment-reply' );

	// Loads JavaScript for Smooth Scroll
	wp_enqueue_script( 'smoothscroll', get_template_directory_uri() . '/js/smoothscroll.js', array( 'jquery' ), '1.4', true );

	// Add Google Webfonts
	wp_enqueue_style( 'yoko-fonts', yoko_fonts_url(), array(), null );

	// Loads main stylesheet.
	wp_enqueue_style( 'yoko-style', get_stylesheet_uri(), array(), '2013-10-21' );

}
add_action( 'wp_enqueue_scripts', 'yoko_scripts' );


/**
 * Sets up theme defaults and registers support for WordPress features.
 */
function yoko() {

	// This theme styles the visual editor with editor-style.css to match the theme style.
	add_editor_style();

	// This theme uses post thumbnails
	add_theme_support( 'post-thumbnails' );

	// Add default posts and comments RSS feed links to head
	add_theme_support( 'automatic-feed-links' );

	//  Let WordPress manage the document title.
	add_theme_support( 'title-tag' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus( array(
		'primary' => __( 'Primary Navigation', 'yoko' ),
	) );

	// Add support for Post Formats
	add_theme_support( 'post-formats', array( 'aside', 'gallery', 'link', 'video', 'image', 'quote' ) );

	// This theme allows users to set a custom background.
	add_theme_support( 'custom-background', apply_filters( 'yoko_custom_background_args', array(
		'default-color'	=> 'ececec',
		'default-image'	=> '',
	) ) );

	// Your changeable header business starts here
	define( 'HEADER_TEXTCOLOR', '' );
	// No CSS, just IMG call. The %s is a placeholder for the theme template directory URI.
	define( 'HEADER_IMAGE', '%s/images/headers/ginko.jpg' );

	// The height and width of your custom header. You can hook into the theme's own filters to change these values.
	// Add a filter to yoko_header_image_width and yoko_header_image_height to change these values.
	define( 'HEADER_IMAGE_WIDTH', apply_filters( 'yoko_header_image_width', 1102 ) );
	define( 'HEADER_IMAGE_HEIGHT', apply_filters( 'yoko_header_image_height', 350 ) );

	// We'll be using post thumbnails for custom header images on posts and pages.
	// We want them to be 940 pixels wide by 350 pixels tall.
	// Larger images will be auto-cropped to fit, smaller ones will be ignored. See header.php.
	set_post_thumbnail_size( HEADER_IMAGE_WIDTH, HEADER_IMAGE_HEIGHT, true );

	// Don't support text inside the header image.
	define( 'NO_HEADER_TEXT', true );

	// Add a way for the custom header to be styled in the admin panel that controls
	// custom headers. See yoko_admin_header_style(), below.
	add_theme_support('custom-header');

	// ... and thus ends the changeable header business.

	// Default custom headers packaged with the theme. %s is a placeholder for the theme template directory URI.
	register_default_headers( array(
			'ginko' => array(
			'url' => '%s/images/headers/ginko.jpg',
			'thumbnail_url' => '%s/images/headers/ginko-thumbnail.jpg',
			/* translators: header image description */
			'description' => __( 'Ginko', 'yoko' )
			),
			'flowers' => array(
			'url' => '%s/images/headers/flowers.jpg',
			'thumbnail_url' => '%s/images/headers/flowers-thumbnail.jpg',
			/* translators: header image description */
			'description' => __( 'Flowers', 'yoko' )
			),
			'plant' => array(
			'url' => '%s/images/headers/plant.jpg',
			'thumbnail_url' => '%s/images/headers/plant-thumbnail.jpg',
			/* translators: header image description */
			'description' => __( 'Plant', 'yoko' )
			),
			'sailing' => array(
			'url' => '%s/images/headers/sailing.jpg',
			'thumbnail_url' => '%s/images/headers/sailing-thumbnail.jpg',
			/* translators: header image description */
			'description' => __( 'Sailing', 'yoko' )
			),
			'cape' => array(
			'url' => '%s/images/headers/cape.jpg',
			'thumbnail_url' => '%s/images/headers/cape-thumbnail.jpg',
			/* translators: header image description */
			'description' => __( 'Cape', 'yoko' )
			),
			'seagull' => array(
			'url' => '%s/images/headers/seagull.jpg',
			'thumbnail_url' => '%s/images/headers/seagull-thumbnail.jpg',
			/* translators: header image description */
			'description' => __( 'Seagull', 'yoko' )
			)
	) );
}
endif;

if ( ! function_exists( 'yoko_admin_header_style' ) ) :

/**
 * Styles the header image displayed on the Appearance > Header admin panel.
 * Referenced via add_custom_image_header() in yoko_setup().
 */
function yoko_admin_header_style() {
?>
<style type="text/css">
/* Shows the same border as on front end */
#heading {
	border-bottom: 1px solid #000;
	border-top: 4px solid #000;
}
/* If NO_HEADER_TEXT is false, you would style the text with these selectors:
	#headimg #name { }
	#headimg #desc { }
*/
</style>
<?php
}
endif;

/**
 * Get our wp_nav_menu() fallback, wp_page_menu(), to show a home link.
 */
function yoko_page_menu_args( $args ) {
	$args['show_home'] = true;
	return $args;
}
add_filter( 'wp_page_menu_args', 'yoko_page_menu_args' );

/**
 * Sets the post excerpt length to 40 characters.
 */
function yoko_excerpt_length( $length ) {
	return 40;
}
add_filter( 'excerpt_length', 'yoko_excerpt_length' );

/**
 * Returns a "Continue Reading" link for excerpts
 */
function yoko_continue_reading_link() {
	return ' <a href="'. get_permalink() . '">' . __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'yoko' ) . '</a>';
}

/**
 * Replaces "[...]" (appended to automatically generated excerpts) with an ellipsis and yoko_continue_reading_link().
 *
 * To override this in a child theme, remove the filter and add your own
 * function tied to the excerpt_more filter hook.
 */
function yoko_auto_excerpt_more( $more ) {
	return ' &hellip;' . yoko_continue_reading_link();
}
add_filter( 'excerpt_more', 'yoko_auto_excerpt_more' );

/**
 * Adds a pretty "Continue Reading" link to custom post excerpts.
 *
 * To override this link in a child theme, remove the filter and add your own
 * function tied to the get_the_excerpt filter hook.
 */
function yoko_custom_excerpt_more( $output ) {
	if ( has_excerpt() && ! is_attachment() ) {
		$output .= yoko_continue_reading_link();
	}
	return $output;
}
add_filter( 'get_the_excerpt', 'yoko_custom_excerpt_more' );

/**
 * Remove inline styles printed when the gallery shortcode is used.
 */
function yoko_remove_gallery_css( $css ) {
	return preg_replace( "#<style type='text/css'>(.*?)</style>#s", '', $css );
}
add_filter( 'gallery_style', 'yoko_remove_gallery_css' );

if ( ! function_exists( 'yoko_comment' ) ) :

/**
 * Template for comments and pingbacks.
 */
function yoko_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	switch ( $comment->comment_type ) :
		case '' :
	?>
	<li <?php comment_class(); ?> id="li-comment-<?php comment_ID(); ?>">
		<div id="comment-<?php comment_ID(); ?>">
		<div class="comment-gravatar"><?php echo get_avatar( $comment, 65 ); ?></div>

		<div class="comment-body">
		<div class="comment-meta commentmetadata">
		<?php printf( __( '%s', 'yoko' ), sprintf( '<cite class="fn">%s</cite>', get_comment_author_link() ) ); ?><br/>
		<a href="<?php echo esc_url( get_comment_link( $comment->comment_ID ) ); ?>">
			<?php
				/* translators: 1: date, 2: time */
				printf( __( '%1$s at %2$s', 'yoko' ), get_comment_date(),  get_comment_time() ); ?></a><?php edit_comment_link( __( 'Edit &rarr;', 'yoko' ), ' ' );
			?>
		</div><!-- .comment-meta .commentmetadata -->

		<?php comment_text(); ?>

		<?php if ( $comment->comment_approved == '0' ) : ?>
		<p class="moderation"><?php _e( 'Your comment is awaiting moderation.', 'yoko' ); ?></p>
		<?php endif; ?>

		<div class="reply">
			<?php comment_reply_link( array_merge( $args, array( 'depth' => $depth, 'max_depth' => $args['max_depth'] ) ) ); ?>
		</div><!-- .reply -->

		</div>
		<!--comment Body-->

	</div><!-- #comment-##  -->

	<?php
			break;
		case 'pingback'  :
		case 'trackback' :
	?>
	<li class="post pingback">
		<p><?php _e( 'Pingback:', 'yoko' ); ?> <?php comment_author_link(); ?><?php edit_comment_link( __('(Edit)', 'yoko'), ' ' ); ?></p>
	<?php
			break;
	endswitch;
}
endif;

/**
 * Register widgetized area and update sidebar with default widgets
 */
function yoko_widgets_init() {
	register_sidebar( array (
		'name' => __( 'Sidebar 1', 'yoko' ),
		'id' => 'sidebar-1',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => "</aside>",
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );

	register_sidebar( array (
		'name' => __( 'Sidebar 2', 'yoko' ),
		'id' => 'sidebar-2',
		'description' => __( 'An second sidebar area', 'yoko' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => "</aside>",
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );
}
add_action( 'init', 'yoko_widgets_init' );

/**
 * Removes the default styles that are packaged with the Recent Comments widget.
 */
function yoko_remove_recent_comments_style() {
	global $wp_widget_factory;
	remove_action( 'wp_head', array( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style' ) );
}
add_action( 'widgets_init', 'yoko_remove_recent_comments_style' );


/**
 * Search form custom styling
 */
function yoko_search_form( $form ) {

    $form = '<form role="search" method="get" class="searchform" action="'.home_url('/').'" >
    <div>
    <input type="text" class="search-input" value="' . get_search_query() . '" name="s" id="s" />
    <input type="submit" class="searchsubmit" value="'. esc_attr__('Search', 'yoko') .'" />
    </div>
    </form>';

    return $form;
}
add_filter( 'get_search_form', 'yoko_search_form' );

/**
 * Remove the default CSS style from the WP image gallery
 */
add_filter('gallery_style', create_function('$a', 'return "
<div class=\'gallery\'>";'));


/**
 * Add Theme Customizer CSS
 */
function yoko_customize_css() {
    ?>
	<style type="text/css" id="yoko-themeoptions-css">
		a {color: <?php echo get_theme_mod( 'link_color', '#009BC2' ); ?>;}
		#content .single-entry-header h1.entry-title {color: <?php echo get_theme_mod( 'link_color', '#009BC2' ); ?>!important;}
		input#submit:hover {background-color: <?php echo get_theme_mod( 'link_color', '#009BC2' ); ?>!important;}
		#content .page-entry-header h1.entry-title {color: <?php echo get_theme_mod( 'link_color', '#009BC2' ); ?>!important;}
		.searchsubmit:hover {background-color: <?php echo get_theme_mod( 'link_color', '#009BC2' ); ?>!important;}
	</style>
    <?php
}
add_action( 'wp_head', 'yoko_customize_css');

/**
 * Customizer additions
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Theme Options page
 */
require get_template_directory() . '/inc/theme-options.php';


/**
 * Custom Social Links Widget
 */
class Yoko_SocialLinks_Widget extends WP_Widget {
	function Yoko_SocialLinks_Widget() {
		$widget_ops = array(
		'classname' => 'widget_social_links',
		'description' => __('Link to your social profiles like twitter, facebook or flickr.', 'yoko'));
		$this->WP_Widget('social_links', 'Yoko Social Links', $widget_ops);
	}

	function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		echo $before_widget;
		$title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);

		$twitter_title = empty($instance['twitter_title']) ? ' ' : apply_filters('widget_twitter_title', $instance['twitter_title']);
		$twitter_url = empty($instance['twitter_url']) ? ' ' : apply_filters('widget_twitter_url', $instance['twitter_url']);
		$fb_title = empty($instance['fb_title']) ? ' ' : apply_filters('widget_fb_title', $instance['fb_title']);
		$fb_url = empty($instance['fb_url']) ? ' ' : apply_filters('widget_fb_url', $instance['fb_url']);
$googleplus_title = empty($instance['googleplus_title']) ? ' ' : apply_filters('widget_googleplus_title', $instance['googleplus_title']);
		$googleplus_url = empty($instance['googleplus_url']) ? ' ' : apply_filters('widget_googleplus_url', $instance['googleplus_url']);
		$pinterest_title = empty($instance['pinterest_title']) ? ' ' : apply_filters('widget_pinterest_title', $instance['pinterest_title']);
		$pinterest_url = empty($instance['pinterest_url']) ? ' ' : apply_filters('widget_pinterest_url', $instance['pinterest_url']);
		$vimeo_title = empty($instance['vimeo_title']) ? ' ' : apply_filters('widget_vimeo_title', $instance['vimeo_title']);
		$vimeo_url = empty($instance['vimeo_url']) ? ' ' : apply_filters('widget_vimeo_url', $instance['vimeo_url']);
		$youtube_title = empty($instance['youtube_title']) ? ' ' : apply_filters('widget_youtube_title', $instance['youtube_title']);
		$youtube_url = empty($instance['youtube_url']) ? ' ' : apply_filters('widget_youtube_url', $instance['youtube_url']);
		$instagram_title = empty($instance['instagram_title']) ? ' ' : apply_filters('widget_instagram_title', $instance['instagram_title']);
		$instagram_url = empty($instance['instagram_url']) ? ' ' : apply_filters('widget_instagram_url', $instance['instagram_url']);
		$flickr_title = empty($instance['flickr_title']) ? ' ' : apply_filters('widget_flickr_title', $instance['flickr_title']);
		$flickr_url = empty($instance['flickr_url']) ? ' ' : apply_filters('widget_flickr_url', $instance['flickr_url']);
		$dribbble_title = empty($instance['dribbble_title']) ? ' ' : apply_filters('widget_dribbble_title', $instance['dribbble_title']);
		$dribbble_url = empty($instance['dribbble_url']) ? ' ' : apply_filters('widget_dribbble_url', $instance['dribbble_url']);
		$github_title = empty($instance['github_title']) ? ' ' : apply_filters('widget_github_title', $instance['github_title']);
		$github_url = empty($instance['github_url']) ? ' ' : apply_filters('widget_github_url', $instance['github_url']);
		$foursquare_title = empty($instance['foursquare_title']) ? ' ' : apply_filters('widget_foursquare_title', $instance['foursquare_title']);
		$foursquare_url = empty($instance['foursquare_url']) ? ' ' : apply_filters('widget_foursquare_url', $instance['foursquare_url']);
		$wordpress_title = empty($instance['wordpress_title']) ? ' ' : apply_filters('widget_wordpress_title', $instance['wordpress_title']);
		$wordpress_url = empty($instance['wordpress_url']) ? ' ' : apply_filters('widget_wordpress_url', $instance['wordpress_url']);
		$xing_title = empty($instance['xing_title']) ? ' ' : apply_filters('widget_xing_title', $instance['xing_title']);
		$xing_url = empty($instance['xing_url']) ? ' ' : apply_filters('widget_xing_url', $instance['xing_url']);
		$linkedin_title = empty($instance['linkedin_title']) ? ' ' : apply_filters('widget_linkedin_title', $instance['linkedin_title']);
		$linkedin_url = empty($instance['linkedin_url']) ? ' ' : apply_filters('widget_linkedin_url', $instance['linkedin_url']);
		$delicious_title = empty($instance['delicious_title']) ? ' ' : apply_filters('widget_delicious_title', $instance['delicious_title']);
		$delicious_url = empty($instance['delicious_url']) ? ' ' : apply_filters('widget_delicious_url', $instance['delicious_url']);
		$rss_title = empty($instance['rss_title']) ? ' ' : apply_filters('widget_rss_title', $instance['rss_title']);
		$rss_url = empty($instance['rss_url']) ? ' ' : apply_filters('widget_rss_url', $instance['rss_url']);

		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; };
		echo '<ul>';
		if($twitter_title == ' ') { echo ''; } else {  echo  '<li class="widget_sociallinks"><a href="'. $twitter_url .'" class="twitter" target="_blank">'. $twitter_title .'</a></li>'; }
		if($fb_title == ' ') { echo ''; } else {  echo  '<li class="widget_sociallinks"><a href="'. $fb_url .'" class="facebook" target="_blank">'. $fb_title .'</a></li>'; }
		if($googleplus_title == ' ') { echo ''; } else {  echo  '<li class="widget_sociallinks"><a href="'. $googleplus_url .'" class="googleplus" target="_blank">'. $googleplus_title .'</a></li>'; }
		if($pinterest_title == ' ') { echo ''; } else {  echo  '<li class="widget_sociallinks"><a href="'. $pinterest_url .'" class="pinterest" target="_blank">'. $pinterest_title .'</a></li>'; }
		if($vimeo_title == ' ') { echo ''; } else {  echo  '  <li class="widget_sociallinks"><a href="'. $vimeo_url .'" class="vimeo" target="_blank">'. $vimeo_title .'</a></li>'; }
		if($youtube_title == ' ') { echo ''; } else {  echo  '  <li class="widget_sociallinks"><a href="'. $youtube_url .'" class="youtube" target="_blank">'. $youtube_title .'</a></li>'; }
		if($instagram_title == ' ') { echo ''; } else {  echo  '  <li class="widget_sociallinks"><a href="'. $instagram_url .'" class="instagram" target="_blank">'. $instagram_title .'</a></li>'; }
		if($flickr_title == ' ') { echo ''; } else {  echo  '<li class="widget_sociallinks"><a href="'. $flickr_url .'" class="flickr" target="_blank">'. $flickr_title .'</a></li>'; }
		if($dribbble_title == ' ') { echo ''; } else {  echo  '<li class="widget_sociallinks"><a href="'. $dribbble_url .'" class="dribbble" target="_blank">'. $dribbble_title .'</a></li>'; }
		if($github_title == ' ') { echo ''; } else {  echo  '<li class="widget_sociallinks"><a href="'. $github_url .'" class="github" target="_blank">'. $github_title .'</a></li>'; }
		if($foursquare_title == ' ') { echo ''; } else {  echo  '<li class="widget_sociallinks"><a href="'. $foursquare_url .'" class="foursquare" target="_blank">'. $foursquare_title .'</a></li>'; }
		if($wordpress_title == ' ') { echo ''; } else {  echo  '<li class="widget_sociallinks"><a href="'. $wordpress_url .'" class="wordpress" target="_blank">'. $wordpress_title .'</a></li>'; }
		if($xing_title == ' ') { echo ''; } else {  echo  '<li class="widget_sociallinks"><a href="'. $xing_url .'" class="xing" target="_blank">'. $xing_title .'</a></li>'; }
		if($linkedin_title == ' ') { echo ''; } else {  echo  '<li class="widget_sociallinks"><a href="'. $linkedin_url .'" class="linkedin" target="_blank">'. $linkedin_title .'</a></li>'; }
		if($delicious_title == ' ') { echo ''; } else {  echo  '<li class="widget_sociallinks"><a href="'. $delicious_url .'" class="delicious" target="_blank">'. $delicious_title .'</a></li>'; }
		if($rss_title == ' ') { echo ''; } else {  echo  '<li class="widget_sociallinks"><a href="'. $rss_url .'" class="rss" target="_blank">'. $rss_title .'</a></li>'; }
		echo '</ul>';
		echo $after_widget;

	}
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['twitter_title'] = strip_tags($new_instance['twitter_title']);
		$instance['twitter_url'] = strip_tags($new_instance['twitter_url']);
		$instance['fb_title'] = strip_tags($new_instance['fb_title']);
		$instance['fb_url'] = strip_tags($new_instance['fb_url']);
		$instance['googleplus_title'] = strip_tags($new_instance['googleplus_title']);
		$instance['googleplus_url'] = strip_tags($new_instance['googleplus_url']);
		$instance['pinterest_title'] = strip_tags($new_instance['pinterest_title']);
		$instance['pinterest_url'] = strip_tags($new_instance['pinterest_url']);
		$instance['vimeo_title'] = strip_tags($new_instance['vimeo_title']);
		$instance['vimeo_url'] = strip_tags($new_instance['vimeo_url']);
		$instance['youtube_title'] = strip_tags($new_instance['youtube_title']);
		$instance['youtube_url'] = strip_tags($new_instance['youtube_url']);
		$instance['instagram_title'] = strip_tags($new_instance['instagram_title']);
		$instance['instagram_url'] = strip_tags($new_instance['instagram_url']);
		$instance['flickr_title'] = strip_tags($new_instance['flickr_title']);
		$instance['flickr_url'] = strip_tags($new_instance['flickr_url']);
		$instance['dribbble_title'] = strip_tags($new_instance['dribbble_title']);
		$instance['dribbble_url'] = strip_tags($new_instance['dribbble_url']);
		$instance['github_title'] = strip_tags($new_instance['github_title']);
		$instance['github_url'] = strip_tags($new_instance['github_url']);
		$instance['foursquare_title'] = strip_tags($new_instance['foursquare_title']);
		$instance['foursquare_url'] = strip_tags($new_instance['foursquare_url']);
		$instance['wordpress_title'] = strip_tags($new_instance['wordpress_title']);
		$instance['wordpress_url'] = strip_tags($new_instance['wordpress_url']);
		$instance['xing_title'] = strip_tags($new_instance['xing_title']);
		$instance['xing_url'] = strip_tags($new_instance['xing_url']);
		$instance['linkedin_title'] = strip_tags($new_instance['linkedin_title']);
		$instance['linkedin_url'] = strip_tags($new_instance['linkedin_url']);
		$instance['delicious_title'] = strip_tags($new_instance['delicious_title']);
		$instance['delicious_url'] = strip_tags($new_instance['delicious_url']);
		$instance['rss_title'] = strip_tags($new_instance['rss_title']);
		$instance['rss_url'] = strip_tags($new_instance['rss_url']);
		return $instance;
	}
	function form($instance) {
		$instance = wp_parse_args(
		(array) $instance, array(
			'title' => '',
			'twitter_title' => '',
			'twitter_url' => '',
			'fb_title' => '',
			'fb_url' => '',
			'googleplus_title' => '',
			'googleplus_url' => '',
			'pinterest_title' => '',
			'pinterest_url' => '',
			'vimeo_title' => '',
			'vimeo_url' => '',
			'youtube_title' => '',
			'youtube_url' => '',
			'instagram_title' => '',
			'instagram_url' => '',
			'flickr_title' => '',
			'flickr_url' => '',
			'dribbble_title' => '',
			'dribbble_url' => '',
			'github_title' => '',
			'github_url' => '',
			'foursquare_title' => '',
			'foursquare_url' => '',
			'wordpress_title' => '',
			'wordpress_url' => '',
			'xing_title' => '',
			'xing_url' => '',
			'linkedin_title' => '',
			'linkedin_url' => '',
			'delicious_title' => '',
			'delicious_url' => '',
			'rss_title' => '',
			'rss_url' => ''
		) );
		$title = strip_tags($instance['title']);
		$twitter_title = strip_tags($instance['twitter_title']);
		$twitter_url = strip_tags($instance['twitter_url']);
		$fb_title = strip_tags($instance['fb_title']);
		$fb_url = strip_tags($instance['fb_url']);
		$googleplus_title = strip_tags($instance['googleplus_title']);
		$googleplus_url = strip_tags($instance['googleplus_url']);
		$pinterest_title = strip_tags($instance['pinterest_title']);
		$pinterest_url = strip_tags($instance['pinterest_url']);
		$vimeo_title = strip_tags($instance['vimeo_title']);
		$vimeo_url = strip_tags($instance['vimeo_url']);
		$youtube_title = strip_tags($instance['youtube_title']);
		$youtube_url = strip_tags($instance['youtube_url']);
		$instagram_title = strip_tags($instance['instagram_title']);
		$instagram_url = strip_tags($instance['instagram_url']);
		$flickr_title = strip_tags($instance['flickr_title']);
		$flickr_url = strip_tags($instance['flickr_url']);
		$dribbble_title = strip_tags($instance['dribbble_title']);
		$dribbble_url = strip_tags($instance['dribbble_url']);
		$github_title = strip_tags($instance['github_title']);
		$github_url = strip_tags($instance['github_url']);
		$foursquare_title = strip_tags($instance['foursquare_title']);
		$foursquare_url = strip_tags($instance['foursquare_url']);
		$wordpress_title = strip_tags($instance['wordpress_title']);
		$wordpress_url = strip_tags($instance['wordpress_url']);
		$xing_title = strip_tags($instance['xing_title']);
		$xing_url = strip_tags($instance['xing_url']);
		$linkedin_title = strip_tags($instance['linkedin_title']);
		$linkedin_url = strip_tags($instance['linkedin_url']);
		$delicious_title = strip_tags($instance['delicious_title']);
		$delicious_url = strip_tags($instance['delicious_url']);
		$rss_title = strip_tags($instance['rss_title']);
		$rss_url = strip_tags($instance['rss_url']);
?>
			<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('twitter_title'); ?>"><?php _e( 'Twitter Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('twitter_title'); ?>" name="<?php echo $this->get_field_name('twitter_title'); ?>" type="text" value="<?php echo esc_attr($twitter_title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('twitter_url'); ?>"><?php _e( 'Twitter  URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('twitter_url'); ?>" name="<?php echo $this->get_field_name('twitter_url'); ?>" type="text" value="<?php echo esc_attr($twitter_url); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('fb_title'); ?>"><?php _e( 'Facebook Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('fb_title'); ?>" name="<?php echo $this->get_field_name('fb_title'); ?>" type="text" value="<?php echo esc_attr($fb_title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('fb_url'); ?>"><?php _e( 'Facebook URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('fb_url'); ?>" name="<?php echo $this->get_field_name('fb_url'); ?>" type="text" value="<?php echo esc_attr($fb_url); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('googleplus_title'); ?>"><?php _e( 'Google+ Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('googleplus_title'); ?>" name="<?php echo $this->get_field_name('googleplus_title'); ?>" type="text" value="<?php echo esc_attr($googleplus_title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('googleplus_url'); ?>"><?php _e( 'Google+ URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('googleplus_url'); ?>" name="<?php echo $this->get_field_name('googleplus_url'); ?>" type="text" value="<?php echo esc_attr($googleplus_url); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('pinterest_title'); ?>"><?php _e( 'Pinterest Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('pinterest_title'); ?>" name="<?php echo $this->get_field_name('pinterest_title'); ?>" type="text" value="<?php echo esc_attr($pinterest_title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('pinterest_url'); ?>"><?php _e( 'Pinterest URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('pinterest_url'); ?>" name="<?php echo $this->get_field_name('pinterest_url'); ?>" type="text" value="<?php echo esc_attr($pinterest_url); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('vimeo_title'); ?>"><?php _e( 'Vimeo Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('vimeo_title'); ?>" name="<?php echo $this->get_field_name('vimeo_title'); ?>" type="text" value="<?php echo esc_attr($vimeo_title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('vimeo_url'); ?>"><?php _e( 'Vimeo URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('vimeo_url'); ?>" name="<?php echo $this->get_field_name('vimeo_url'); ?>" type="text" value="<?php echo esc_attr($vimeo_url); ?>" /></label></p>

			<p><label for="<?php echo $this->get_field_id('youtube_title'); ?>"><?php _e( 'YouTube Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('youtube_title'); ?>" name="<?php echo $this->get_field_name('youtube_title'); ?>" type="text" value="<?php echo esc_attr($youtube_title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('youtube_url'); ?>"><?php _e( 'YouTube URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('youtube_url'); ?>" name="<?php echo $this->get_field_name('youtube_url'); ?>" type="text" value="<?php echo esc_attr($youtube_url); ?>" /></label></p>

			<p><label for="<?php echo $this->get_field_id('instagram_title'); ?>"><?php _e( 'Instagram Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('instagram_title'); ?>" name="<?php echo $this->get_field_name('instagram_title'); ?>" type="text" value="<?php echo esc_attr($instagram_title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('instagram_url'); ?>"><?php _e( 'Instagram URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('instagram_url'); ?>" name="<?php echo $this->get_field_name('instagram_url'); ?>" type="text" value="<?php echo esc_attr($youtube_url); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('flickr_title'); ?>"><?php _e( 'Flickr Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('flickr_title'); ?>" name="<?php echo $this->get_field_name('flickr_title'); ?>" type="text" value="<?php echo esc_attr($flickr_title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('flickr_url'); ?>"><?php _e( 'Flickr URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('flickr_url'); ?>" name="<?php echo $this->get_field_name('flickr_url'); ?>" type="text" value="<?php echo esc_attr($flickr_url); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('dribbble_title'); ?>"><?php _e( 'Dribbble Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('dribbble_title'); ?>" name="<?php echo $this->get_field_name('dribbble_title'); ?>" type="text" value="<?php echo esc_attr($dribbble_title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('dribbble_url'); ?>"><?php _e( 'Dribbble URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('dribbble_url'); ?>" name="<?php echo $this->get_field_name('dribbble_url'); ?>" type="text" value="<?php echo esc_attr($dribbble_url); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('github_title'); ?>"><?php _e( 'GitHub Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('github_title'); ?>" name="<?php echo $this->get_field_name('github_title'); ?>" type="text" value="<?php echo esc_attr($github_title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('github_url'); ?>"><?php _e( 'GitHub URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('github_url'); ?>" name="<?php echo $this->get_field_name('github_url'); ?>" type="text" value="<?php echo esc_attr($github_url); ?>" /></label></p>

			<p><label for="<?php echo $this->get_field_id('foursquare_title'); ?>"><?php _e( 'Foursquare Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('foursquare_title'); ?>" name="<?php echo $this->get_field_name('foursquare_title'); ?>" type="text" value="<?php echo esc_attr($foursquare_title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('foursquare_url'); ?>"><?php _e( 'Foursquare URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('foursquare_url'); ?>" name="<?php echo $this->get_field_name('foursquare_url'); ?>" type="text" value="<?php echo esc_attr($foursquare_url); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('wordpress_title'); ?>"><?php _e( 'WordPress Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('wordpress_title'); ?>" name="<?php echo $this->get_field_name('wordpress_title'); ?>" type="text" value="<?php echo esc_attr($wordpress_title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('wordpress_url'); ?>"><?php _e( 'WordPress URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('wordpress_url'); ?>" name="<?php echo $this->get_field_name('wordpress_url'); ?>" type="text" value="<?php echo esc_attr($wordpress_url); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('xing_title'); ?>"><?php _e( 'Xing Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('xing_title'); ?>" name="<?php echo $this->get_field_name('xing_title'); ?>" type="text" value="<?php echo esc_attr($xing_title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('xing_url'); ?>"><?php _e( 'Xing URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('xing_url'); ?>" name="<?php echo $this->get_field_name('xing_url'); ?>" type="text" value="<?php echo esc_attr($xing_url); ?>" /></label></p>

			<p><label for="<?php echo $this->get_field_id('linkedin_title'); ?>"><?php _e( 'LinkedIn Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('linkedin_title'); ?>" name="<?php echo $this->get_field_name('linkedin_title'); ?>" type="text" value="<?php echo esc_attr($linkedin_title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('linkedin_url'); ?>"><?php _e( 'LinkedIn URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('linkedin_url'); ?>" name="<?php echo $this->get_field_name('linkedin_url'); ?>" type="text" value="<?php echo esc_attr($linkedin_url); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('delicious_title'); ?>"><?php _e( 'Delicious Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('delicious_title'); ?>" name="<?php echo $this->get_field_name('delicious_title'); ?>" type="text" value="<?php echo esc_attr($delicious_title); ?>" /></label></p>
			<p><label for="<?php echo $this->get_field_id('delicious_url'); ?>"><?php _e( 'Delicious URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('delicious_url'); ?>" name="<?php echo $this->get_field_name('delicious_url'); ?>" type="text" value="<?php echo esc_attr($delicious_url); ?>" /></label></p>

			<p><label for="<?php echo $this->get_field_id('rss_title'); ?>"><?php _e( 'RSS Text:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('rss_title'); ?>" name="<?php echo $this->get_field_name('rss_title'); ?>" type="text" value="<?php echo esc_attr($rss_title); ?>" /></label></p>

			<p><label for="<?php echo $this->get_field_id('rss_url'); ?>"><?php _e( 'RSS  URL:', 'yoko' ); ?> <input class="widefat" id="<?php echo $this->get_field_id('rss_url'); ?>" name="<?php echo $this->get_field_name('rss_url'); ?>" type="text" value="<?php echo esc_attr($rss_url); ?>" /></label></p>

<?php
	}
}
// register Yoko SocialLinks Widget
add_action('widgets_init', create_function('', 'return register_widget("Yoko_SocialLinks_Widget");'));
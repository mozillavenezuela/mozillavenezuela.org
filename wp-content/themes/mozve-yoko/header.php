<?php
/**
 * @package WordPress
 * @subpackage Yoko
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
<title><?php
	/*
	 * Print the <title> tag based on what is being viewed.
	 */
	global $page, $paged;
	wp_title( '|', true, 'right' );
	bloginfo( 'name' );
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) )
		echo " | $site_description";
	if ( $paged >= 2 || $page >= 2 )
		echo ' | ' . sprintf( __( 'Page %s', 'yoko' ), max( $paged, $page ) );
?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="icon" href="/favicon.ico" />
<link rel="stylesheet" type="text/css" media="screen" href="<?php bloginfo( 'stylesheet_url' ); ?>" />
<link rel="stylesheet" type="text/css" media="all" href="//www.mozilla.org/tabzilla/media/css/tabzilla.css">
<?php if ( is_singular() && get_option( 'thread_comments' ) ) wp_enqueue_script( 'comment-reply' ); ?>
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
<!--[if lt IE 9]>
<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js" type="text/javascript"></script>
<![endif]-->

<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<div id="page" class="clearfix">
	<header id="branding" role="banner">
		<?php global $yoko_options;
		$yoko_settings = get_option( 'yoko_options', $yoko_options ); ?>
		<a id="tabzilla" data-locale="es-ES" href="http://www.mozilla.org/">mozilla</a>
		<hgroup id="site-title">
		<img id="mascota-mozve" src="<?php echo get_stylesheet_directory_uri() ?>/images/mascota-mozve.png" title="Mascota Mozilla Venezuela" />
			<h1 id="headerA"><a href="<?php echo home_url( '/' ); ?>" title="Mozilla Venezuela">mozilla</a></h1>
			<h1 id="headerB"><a href="<?php echo home_url( '/' ); ?>" title="Mozilla Venezuela">Venezuela</a></h1>
		</hgroup><!-- end site-title -->
        
        <?php
		// The header image
		// Check if this is a post or page, if it has a thumbnail, and if it's a big one
			if ( is_singular() &&
				current_theme_supports( 'post-thumbnails' ) &&
				has_post_thumbnail( $post->ID ) &&
				( /* $src, $width, $height */ $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'post-thumbnail' ) ) &&
				$image[1] >= HEADER_IMAGE_WIDTH ) :
				// Houston, we have a new header image!
						echo get_the_post_thumbnail( $post->ID , array(1102,350), array('class' => 'headerimage'));
						elseif ( get_header_image() ) : ?>
						<img src="<?php header_image(); ?>" class="headerimage" width="<?php echo HEADER_IMAGE_WIDTH; ?>" height="<?php echo HEADER_IMAGE_HEIGHT; ?>" alt="" /><!-- end headerimage -->
					<?php endif; ?>
					<div class="clear"></div>
					
		<nav id="subnav">
			<?php
			if (is_nav_menu( 'Sub Menu' ) ) {
			wp_nav_menu( array('menu' => 'Sub Menu' ));} ?>
		</nav><!-- end subnav -->	
		
		<nav id="mainnav" class="clearfix">
			<?php wp_nav_menu( array( 'theme_location' => 'primary' ) ); ?>
		</nav><!-- end mainnav -->
</header><!-- end header -->
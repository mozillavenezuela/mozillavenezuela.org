  <header id="masthead" role="banner" <?php if (get_header_image()) : ?>class="image"<?php endif; ?>>
    <hgroup>
    
    <span id="nav-main-toggle" role="button" aria-controls="nav-main-menu" tabindex="0">Menu</span>
    
    <?php if ( (is_front_page()) && ($paged < 1) ) : ?>
      <h1 id="site-title" class="home"><a href="<?php echo esc_url( home_url('/') ); ?>" rel="home" title="<?php _e('Go to the front page', 'onemozilla'); ?>"><img src="<?php echo get_stylesheet_directory_uri(); ?>" height="196" width="891" alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>"></a></h1>
      <?php if (get_bloginfo('description','display')) : ?>
      <h2 id="site-description" class="home"><?php echo esc_attr( get_bloginfo('description', 'display') ); ?></h2>
    <?php endif; ?>
    <?php else : ?>
      <h1 id="site-title"><a href="<?php echo esc_url( home_url('/') ); ?>" rel="home" title="<?php _e('Go to the front page', 'onemozilla'); ?>"><img src="<?php echo get_stylesheet_directory_uri(); ?>" height="100" width="423" alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>"></a></h1>
      <?php if (get_bloginfo('description','display')) : ?>
      <h2 id="site-description"><?php echo esc_attr( get_bloginfo('description', 'display') ); ?></h2>
    <?php endif; ?>
    <?php endif; ?>
    </hgroup>
    <a href="http://www.mozilla.org/" id="tabzilla">Mozilla</a>
    <div id="topmenu">
		<?php if (function_exists('dynamic_sidebar') && dynamic_sidebar('topmenu')) : else : ?>
			<div class="pre-widget">
			</div>
		<?php endif; ?>
	</div>
    <div id="mobilemenu">
		<?php if (function_exists('dynamic_sidebar') && dynamic_sidebar('mobilemenu')) : else : ?>
			<div class="pre-widget">
			</div>
		<?php endif; ?>
	</div>
    <div id="socialbar">
		<?php if (function_exists('dynamic_sidebar') && dynamic_sidebar('socialbar')) : else : ?>
			<div class="pre-widget">
			</div>
		<?php endif; ?>
	</div>
  </header><!-- #masthead -->
  
  	<section id="firefox-promo" class="billboard">
  		<?php if (function_exists('dynamic_sidebar') && dynamic_sidebar('getfirefox')) : else : ?>
			<div class="pre-widget">
			</div>
		<?php endif; ?>
    	<div class="clr"></div>
  	</section>
  	<div class="clr"></div>
  	<section id="home-promo" class="pager pager-with-tabs pager-auto-rotate pager-no-history">
		<?php if (function_exists('dynamic_sidebar') && dynamic_sidebar('slider')) : else : ?>
			<div class="pre-widget">
			</div>
		<?php endif; ?>
        <div class="clr"></div>
	</section>


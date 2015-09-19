<?php $theme_options = onemozilla_get_theme_options(); ?>

  </div><!-- #content -->
</div></div><!-- /.wrap /#page -->

<!--<div class="wrap">
<section id="newsletter" class="billboard">
	<?php /* if (function_exists('dynamic_sidebar') && dynamic_sidebar('newsletter')) : else : ?>
		<div class="pre-widget">
		</div>
	<?php endif; */ ?>
	<div class="clr"></div>
</section>
</div>-->

<footer id="site-info" role="contentinfo">
  <div class="wrap">
    <p id="foot-logo">
      <a class="top" href="#page"><?php _e('Return to top', 'onemozilla'); ?></a>
      <a target="_blank" class="logo" href="http://mozilla.org" rel="external">Mozilla</a>
    </p>

    <p id="colophon">
       A no ser que se indique lo contrario, el contenido de este sitio está licenciado bajo  <a href="http://creativecommons.org/licenses/by-sa/3.0/" rel="external license">Creative Commons Attribution Share-Alike License v3.0 .</a> o una versión posterior.<br/><br/>
    
    </p>
   
    <div id="footermenu">
    <nav id="nav-meta">
		<?php if (function_exists('dynamic_sidebar') && dynamic_sidebar('footermenu')) : else : ?>
		<?php endif; ?>
        </nav>
	</div>
    
    <nav id="nav-social">
      <ul role="navigation">
        <li><a target="_blank" href="https://www.facebook.com/MozillaVenezuela" rel="home">Facebook</a></li>
        <li><a target="_blank" href="https://twitter.com/mozillaVe" rel="external">Twitter</a></li>
        <li><a target="_blank" href="https://www.youtube.com/user/mozillaVe" rel="external">Youtube</a></li>
      </ul>
    </nav>
    
  </div>
</footer>

<script src="https://www.mozilla.org/tabzilla/media/js/tabzilla.js"></script>

<?php wp_footer(); ?>

</body>
</html>

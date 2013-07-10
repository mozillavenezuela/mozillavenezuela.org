<?php 
if(!defined('ULFBEN_DONATE_URL')){
	define('ULFBEN_DONATE_URL', 'http://www.amazon.com/gp/registry/wishlist/2QB6SQ5XX2U0N/105-3209188-5640446?reveal=unpurchased&filter=all&sort=priority&layout=standard&x=21&y=17');
}
$plugin_title = 'WP jQuery Lightbox';
$plugin_changelog = 'http://wordpress.org/extend/plugins/wp-jquery-lightbox/changelog/';	
$plugin_forum = 'http://wordpress.org/tags/wp-jquery-lightbox?forum_id=10';
$flattr_profile = 'http://flattr.com/thing/367557/Support-my-plugins';
$flattr_api = 'http://api.flattr.com/js/0.6/load.js?mode=auto';
$wishlist = ULFBEN_DONATE_URL;
$author_site = 'http://www.ulfben.com/';
$author_profile = 'http://profiles.wordpress.org/users/ulfben/';
?>
<script type="text/javascript">
(function() {
	var s = document.createElement('script'), t = document.getElementsByTagName('script')[0];
	s.type = 'text/javascript';
	s.async = true;
	s.src = '<?php echo $flattr_api; ?>';
	t.parentNode.insertBefore(s, t);
})();
</script>
<style type="text/css">
	#about{ float: right; width:350px; background: #ffc; border: 1px solid #333; padding: 5px; text-align: justify; font-family:verdana; font-size:11px; }
	div#about h3 {text-align:center;}
	.field_info {text-align:right;};
</style>
<?php echo "<div id='about'> 
	<h3>From the author</h3> 				
	<p>My name is <a href='{$author_site}'>Ulf Benjaminsson</a> and I've developed WP jQuery Lightbox <a href='{$plugin_changelog}'>since 2010</a>. Nice to meet you! :)<p>
	<p>If you value <a href='{$author_profile}'>my plugins</a>, please <strong>help me out</strong> by <a href='{$flattr_profile}' target='_blank'>Flattr-ing them</a>!</p>
	<p style='text-align:center;'>
	<a class='FlattrButton' style='display:none;' rev='flattr;button:compact;' href='{$author_profile}'></a>
	<noscript><a href='{$flattr_profile}' target='_blank'>
	<img src='http://api.flattr.com/button/flattr-badge-large.png' alt='Flattr this' title='Flattr this' border='0' /></a></noscript></span></p>
	<p>Or perhaps <a href='{$wishlist}' title='Amazon whishlist'>send me a book</a>? Used ones are fine! :)</p>
	<p>//<a href='$author_site'>Ulf Benjaminsson</a></p>								
	<hr />
	<h3>Need Help?</h3> 
	<ol> 					
	<li><a href='{$plugin_forum}'>Support Forum</a></li> 
	</ol> 				
</div>"; 
?>
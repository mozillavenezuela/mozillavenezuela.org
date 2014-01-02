<?php
/*
Plugin Name: Related Links by Category
Plugin URI: http://andrewanimation.biz/games/RLBC-plugin/
Description: This is a widget for the sidebar that shows other posts in the same category as the post being viewed.
Author: Andrew Stephens
Version: 1.1
Author URI: http://andrewanimation.biz/
*/


function init_RLBC() {
	if ( !function_exists('register_sidebar_widget') )
		return;

//Plugin Itself
function widget_RLBC($args) {
extract($args);
$data = get_option('RLBC');

if (is_single()) {
echo $before_widget . $before_title . $data['title'] . $after_title;
?>

<ul>
<?php
foreach((get_the_category($post->ID)) as $category) {

$related = '';

global $post; $cur_id = $post->ID;
$catposts = get_posts('category='.$category->cat_ID);

foreach($catposts as $p) {

if ($d <= $data['disp']) {
if ($cur_id != $p->ID) {
$related .= '<li><a href="'.get_bloginfo('home').'/?p='.$p->ID.'">'.$p->post_title.'</a></li>';
$d++;
}}

}

if ($related != '') {
echo '<li>'.$category->cat_name;
echo '<ul>'.$related.'</ul>';
echo '</li>';
}

}
?>
</ul>
<?php

}

echo $after_widget;

}

register_sidebar_widget(array('Related Links by Category', 'widgets'), 'widget_RLBC');
register_widget_control('Related Links by Category',  'RLBC_control');

}

function RLBC_control() {

$data = get_option('RLBC');?>
<p><label>Title <input name="rlbc_title" type="text" value="<?php echo $data['title']; ?>" /></label></p>
<p><label>Number of Posts to Display <input name="rlbc_disp" size=3 type="text" value="<?php echo $data['disp']; ?>" /></label></p>
<?php

if (isset($_POST['rlbc_title'])) {
$data['title'] = attribute_escape($_POST['rlbc_title']);
$data['disp'] = attribute_escape($_POST['rlbc_disp']);
update_option('RLBC', $data);
}

}

register_activation_hook( __FILE__, 'RLBC_activate' );
function RLBC_activate() {
$data = array( 'title' => 'Related Links' , 'disp' => 5 );
if (!get_option('RLBC')){
   add_option('RLBC' , $data);
} else {
   update_option('RLBC' , $data);
}
}


register_deactivation_hook( __FILE__, 'RLBC_deactivate');
function RLBC_deactivate(){ delete_option('RLBC'); }



// Run our code later in case this loads prior to any required plugins.
add_action('widgets_init', 'init_RLBC');


?>

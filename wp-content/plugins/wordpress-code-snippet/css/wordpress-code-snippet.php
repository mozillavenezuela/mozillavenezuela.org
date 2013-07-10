<?php
/* 
Plugin Name: Wordpress Code Snippet
Plugin URI: http://www.allancollins.net/wordpress-code-snippet/
Description: Add code snippets to posts without reformatting issues.
Version: 1.0
Author: Allan Collins
Author URI: http://www.allancollins.net
*/
/*
Copyright (C) 2008 Allan Collins

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

function code_editor_form() {
	global $wpdb;
		if (!isset($id)) {
			$post_id = $_GET['post'];
		}
	$key="code_type";
	$code_editor2=get_post_meta($post_id, $key, $single);
	$code_editor2=$code_editor2[0];
	$selected="selected=\"selected\"";
	switch($code_editor2) {
	
	case "Xml":
		$selectxml=$selected;
		break;
	
	case "Css":
		$selectcss=$selected;
		break;

	case "JScript":
		$selectjscript=$selected;
		break;	
	
	case "Php":
		$selectphp=$selected;
		break;

	case "Sql":
		$selectsql=$selected;
		break;
		
	case "CSharp":
		$selectcsharp=$selected;
		break;

	case "Vb":
		$selectvb=$selected;
		break;

	case "Cpp":
		$selectcpp=$selected;
		break;

	case "Java":
		$selectjava=$selected;
		break;

	case "Python":
		$selectpython=$selected;
		break;

	case "Ruby":
		$selectruby=$selected;
		break;

	case "Delphi":
		$selectdelphi=$selected;
		break;

}
		

	$key="code_editor";
	$code_editor=get_post_meta($post_id, $key, $single);
	$code_editor=$code_editor[0];
	echo "<div class=\"handlediv\" title=\"Click to toggle\"><br /></div><h3 class=\"hndle\"><span>Code Snippet</span></h3><div class=\"inside\">";
	echo "Code Language: 
	<select name=\"code_type\">
		<option value=\"Xml\" $selectxml>HTML / XML</option>
		<option value=\"Css\" $selectcss>CSS</option>
		<option value=\"JScript\" $selectjscript>JavaScript</option>
		<option value=\"Php\" $selectphp>PHP</option>
		<option value=\"Sql\" $selectsql>SQL</option>
		<option value=\"CSharp\" $selectcsharp>C#</option>
		<option value=\"Vb\" $selectvb>VB</option>
		<option value=\"Cpp\" $selectcpp>C++</option>
		<option value=\"Java\" $selectjava>Java</option>
		<option value=\"Python\" $selectpython>Python</option>
		<option value=\"Ruby\" $selectruby>Ruby</option>
		<option value=\"Delphi\" $selectdelphi>Delphi</option>
	</select><br/>";
	echo "Enter your code into this box...";
	echo "<textarea name=\"code_editor\" rows=\"10\" cols=\"40\" style=\"width:720px\">$code_editor</textarea>";
	echo "</div></div><br/><br/>";
}



function code_editor_add() {
	global $wpdb;
		if (!isset($id)) {
			$post_id = $_REQUEST[ 'post_ID' ];
		}
	$key="code_editor";
	$value=$_REQUEST['code_editor'];
	delete_post_meta($post_id, $key);
	add_post_meta($post_id, $key, $value, true);
	
	$key="code_type";
	$value=$_REQUEST['code_type'];
	delete_post_meta($post_id, $key);
	add_post_meta($post_id, $key, $value, true);
}



function code_editor_show($text) {
	$post_id=get_the_ID();
	$jscode=code_editor_head($post_id);
	$key="code_editor";
	$noeditor=get_post_meta($post_id, $key, $single);
	$noeditor=$noeditor[0];
	$key="code_type";
	$noeditor2=get_post_meta($post_id, $key, $single);
	$noeditor2=$noeditor2[0];
	if ($noeditor) {
		$noeditor="<textarea cols=\"40\" rows=\"10\" name=\"code\" class=\"$noeditor2\">$noeditor</textarea>" . $jscode;
		$text=$text . $noeditor;
	}
	return $text;
}


function code_editor_head($post_id) {
	$key="code_type";
	$noeditor=get_post_meta($post_id, $key, $single);
	$noeditor=$noeditor[0];
	
	$path=get_option('siteurl');
	$path=$path . "/wp-content/plugins/wordpress-code-snippet";
$jscode= "
	<!-- Wordpress Code Snippet -->
	<script type=\"text/javascript\" src=\"$path/js/shCore.js\"></script>";
	if ($noeditor) {

	$jscode.= "<script type=\"text/javascript\" src=\"$path/js/shBrush$noeditor.js\"></script>";

	}
	$jscode.= "
	<link type=\"text/css\" rel=\"stylesheet\" href=\"$path/css/SyntaxHighlighter.css\"/>
	
	<script language=\"javascript\">
	dp.SyntaxHighlighter.ClipboardSwf = '$path/js/clipboard.swf';
	dp.SyntaxHighlighter.HighlightAll('code');
	</script>
	<!-- End Wordpress Code Snippet -->
	";

return $jscode;


}

add_action('edit_form_advanced','code_editor_form',1);
add_action('edit_page_form','code_editor_form',1);
add_action('edit_post','code_editor_add');
add_action('publish_post ','code_editor_add');
add_action('publish_page','code_editor_add');
add_filter('the_content','code_editor_show');

?>
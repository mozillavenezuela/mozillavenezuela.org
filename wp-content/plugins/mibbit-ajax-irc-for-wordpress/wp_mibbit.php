<?php
/* 
Plugin name: Mibbit AJAX Chat
Plugin Description: Mibbit AJAX IRC Plugin for wordpress 
Version: 1.1.2
Author: Keiran Smith
Author URI: http://www.keiran-smith.net 
*/  

define('WP_STR_SHOW_IRC_CHAT', '/<!-- irc_chat(\((([0-9],|[0-9]|,)*?)\))? -->/i');

require('wplib/utils_formbuilder.inc.php');
require('wplib/utils_sql.inc.php');
require('wplib/utils_tablebuilder.inc.php');

add_action('admin_menu', 'WPIRC_menu');

function WPIRC_menu()
{

	add_menu_page('Mibbit IRC Chat Configuration', 'IRC Config', 8, __FILE__, 'IRC_Chat_Conf');
	
}

function IRC_Chat_Conf() {
?>
	<div class="wrap">
	<div id="icon-options-general" class="icon32">
	<br/>
	</div>
	<h2>IRC Configuration</h2>
<?php 	

	// Get all the options from the database for the form
	$setting_irc_server		= get_option('IRC_Setting_server');
	$setting_irc_channel	= get_option('IRC_Setting_channel');
	
	// Check if updated data.
	if( isset($_POST) && isset($_POST['update']) )
	{
		$setting_irc_server		= trim($_POST['IRC_Setting_server']);
		$setting_irc_channel	= trim($_POST['IRC_Setting_channel']);						
				
		update_option('IRC_Setting_server',     	$setting_irc_server);
		update_option('IRC_Setting_channel',     	$setting_irc_channel);
								
	}	
	
	
	$form = new FormBuilder();
	
	$formElem = new FormElement("IRC_Setting_server", "The IRC Server you wish to use");
	$formElem->value = $setting_irc_server;
	$formElem->description = "irc.terogen.com";
	$form->addFormElement($formElem);
	
	$formElem = new FormElement("IRC_Setting_channel", "The IRC Channel you wish to use (Without the #)");
	$formElem->value = $setting_irc_channel;
	$formElem->description = "E.G terogen";
	$form->addFormElement($formElem);
	
	echo $form->toString();
	
	?>	

	
	<h2>Useful Information</h2>
	
	<h3>Comments and Feedback</h3>
	<p>If you have any comments, ideas or any other feedback on this plugin, please leave comments on the <a href="http://www.keiran-smith.net">Mibbit IRC Chat Plugin</a> on my blog.</p>	

	<br /><br />
	<small>Powered by mibbit AJAX IRC Chat by <a href="http://keiran-smith.net/2009/09/06/mibbit-ajax-irc-chat-for-wordpress/">Keiran Smith</a></small>
	
	<p>&nbsp;</p>	
	<p>&nbsp;</p>
</div>
<?php 
}

function IRC_Chat_install()
{
	global $wpdb;

	// ### Create Default Settings
	if (!get_option('IRC_Setting_server'))
		update_option('IRC_Setting_server', "Your IRC Server (E.G irc.terogen.com)");
	
	if (!get_option('IRC_Setting_channel'))
		update_option('IRC_Setting_channel', "Your IRC Channel (NO # SIGN)(e.g terogen)");
		
	$wpdb->show_errors();

	// Update the version regardless
	update_option("IRC_Chat_Version", WPP_VERSION);
}

function IRC_CHAT_show_chat($oldcontent)
{	
	// Ensure we don't lose the original page
	$newcontent = $oldcontent;
	
	// Detect if we need to render the portfolio by looking for the 
	// special string <!-- ShowMyPortfolio -->
	if (preg_match(WP_STR_SHOW_IRC_CHAT, $oldcontent, $matches))
	{    	    	
		//echo "Here!";
		// Turn DB stuff into HTML
		$content = IRC_Render_Chat();
		
		// Now replace search string with formatted portfolio
		$newcontent = chat_replace_string($matches[0], $content, $oldcontent);			    	
	}
	return $newcontent;
}

add_filter('the_content', 'IRC_CHAT_show_chat');

function chat_replace_string($searchstr, $replacestr, $haystack) {
	
	// Faster, but in PHP5.
	if (function_exists("str_ireplace")) {
		return str_ireplace($searchstr, $replacestr, $haystack);
	}
	// Slower but handles PHP4
	else { 
		return preg_replace("/$searchstr/i", $replacestr, $haystack);
	}
}

function IRC_Render_Chat()
{
	$content .= "\n\n";
	
	$setting_irc_server = stripslashes(get_option('IRC_Setting_server'));
	$setting_irc_channel   = stripslashes(get_option('IRC_Setting_channel'));						
	
	$content .= "<div><iframe style=\"width:100%;height:500px;\" frameborder=\"0\" src=\"http://widget.mibbit.com/?server=".$setting_irc_server."&channel=%23".$setting_irc_channel."\"></iframe></div>";
	
	// Credit link on portfolio. 				
	$content .= "<div style=\"font-size: 8pt; font-family: Verdana;\" align=\"center\">Powered By Wordpress Mibbit AJAX Chat Plugin Created By <a href=\"http://keiran-smith.net\">Keiran Smith</a></div>";
				
	// Add some space after the portfolio HTML 
	$content .= "<br /><br />\n\n";
	
	return $content;
}
?>

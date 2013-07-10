<?php
/*
Plugin Name: WP From Email
Plugin URI: http://skullbit.com/wordpress-plugin/wp-from-email/
Description: Override the default 'WordPress &lt;wordpress@mydomain.com&gt;' from name and email address
Author: Skullbit.com
Version: 1.1
*/

if( !class_exists('WPFromEmail') ){
	class WPFromEmail{
		function WPFromEmail() { //constructor
			//ACTIONS
				#Add Settings Panel
				add_action( 'admin_menu', array($this, 'AddPanel') );
				#Update Settings on Save
				if( $_POST['action'] == 'wp_from_update' )
					add_action( 'init', array($this,'SaveSettings') );
				#Save Default Settings
					add_action( 'init', array($this, 'DefaultSettings') );
			//FILTERS
				#Update From Address
				add_filter('wp_mail_from', array($this, 'fromemail'));
				add_filter('wp_mail_from_name', array($this, 'fromname'));
			//LOCALIZATION
				#Place your language file in the plugin folder and name it "wpfrom-{language}.mo"
				#replace {language} with your language value from wp-config.php
				load_plugin_textdomain( 'wpfrom', '/wp-content/plugins/register-plus' );
		}
		
		function fromemail($email){
			$wpfrom = get_option( 'wp_from_email' );
			return $wpfrom['email'];
		}
		
		function fromname($email){
			$wpfrom = get_option( 'wp_from_email' );
			return $wpfrom['name'];
		}
		
		function AddPanel(){
			add_options_page( 'WP From Email', 'WP From Email', 10, 'wp-from-email', array($this, 'EmailSettings') );
		}
		
		function DefaultSettings () {
			$default = array( 
								'email' => get_option( 'admin_email' ),
								'name' 	=> get_option( 'blogname' ), 
							);
			if( !get_option('wp_from_email') ){ #Set Defaults if no values exist
				add_option( 'wp_from_email', $default );
			}else{ #Set Defaults if new value does not exist
				$wpfrom = get_option( 'wp_from_email' );
				foreach( $default as $key => $val ){
					if( !$wpfrom[$key] ){
						$wpfrom[$key] = $val;
						$new = true;
					}
				}
				if( $new )
					update_option( 'wp_from_email', $wpfrom );
			}
		}
		
		function SaveSettings(){
			check_admin_referer('wpfromemail-update-options');
			$update = get_option( 'wp_from_email' );
			$update["email"] = $_POST['wp_from_email'];
			$update["name"] = $_POST['wp_from_name'];
			update_option( 'wp_from_email', $update );
			$_POST['notice'] = __('Settings Saved', 'wpfrom');
		}
		
		function EmailSettings(){
			$wpfrom = get_option( 'wp_from_email' );
			if( $_POST['notice'] )
				echo '<div id="message" class="updated fade"><p><strong>' . $_POST['notice'] . '.</strong></p></div>';
			?>
             <div class="wrap">
            	<h2><?php _e('WP From Email Settings', 'wpfrom')?></h2>
                <form method="post" action="">
                	<?php if( function_exists( 'wp_nonce_field' )) wp_nonce_field( 'wpfromemail-update-options'); ?>
                    <table class="form-table">
                        <tbody>
                        	<tr valign="top">
                       			 <th scope="row"><label for="email"><?php _e('From Email', 'wpfrom');?></label></th>
                        		<td><input type="input" name="wp_from_email" id="email" value="<?php echo $wpfrom['email'];?>" /></td>
                        	</tr>
                            <tr valign="top">
                       			 <th scope="row"><label for="name"><?php _e('From Name', 'wpfrom');?></label></th>
                        		<td><input type="input" name="wp_from_name" id="name" value="<?php echo $wpfrom['name'];?>" /></td>
                        	</tr>
                         </tbody>
                     </table>
                     </div>
                     
                    <p class="submit"><input name="Submit" value="<?php _e('Save Changes','wpfrom');?>" type="submit" />
                    <input name="action" value="wp_from_update" type="hidden" />
                </form>
              
            </div>
            <?php
		}
		
	}
} //END Class WPFromEmail

if( class_exists('WPFromEmail') )
	$wp_from_email = new WPFromEmail();

?>
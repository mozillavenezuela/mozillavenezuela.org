<div class="wrap">
	<div id="killabot_admin_container" style="margin-top:10px;height:auto;width:450px;">
		<div style="width:450px;">
			<h2>
			<img src="<?php print KILLABOT_DIRECTORY; ?>/images/shield.gif" alt="Killabot Shield" style="vertical-align:middle;"/>
			&nbsp;Killabot APx Configuration
			</h2>
		</div>
		<a href="http://www.killabot.com" target="blank">The Killabot APx System</a>
		&nbsp;<a href="<?php print KILLABOT_DIRECTORY; ?>/killabot-apx-faq.php?popupwindow" class="popupwindow" rel="windowFAQ">Frequently Asked Questions</a>
		<div style="width:450px">
    		<p align="justify">
		    The Killabot APx System detects when users are accessing your blog via an
		    anonymous proxy. The  anonymity afforded by these proxies can lead to unsafe
		    conditions for any blog administrator.</p>
		<form>
		<fieldset style="height:auto;padding:.5em;border:1px solid #cdcdcd;">
  			<legend>Killabot APx System</legend>

        	<div id="install" style="visibility:hidden;">
    			<img  id="install_image" src="<?php echo KILLABOT_DIRECTORY; ?>/images/waiting.gif" alt="processing..." style="vertical-align:middle;" />
    			<span id="install_text"></span>
    		</div>
    
    	<p /> 
		    
        	<div id="connect" style="visibility:hidden;">
    			<img  id="connect_image" src="<?php echo KILLABOT_DIRECTORY; ?>/images/waiting.gif" alt="processing..." style="vertical-align:middle;" />
    			<span id="connect_text"></span>
    		</div>
    
    	<p />
    		
    		<div id="api" style="visibility:hidden;">
    			<img id="api_image" src="<?php echo KILLABOT_DIRECTORY; ?>/images/waiting.gif" alt="processing..." style="vertical-align:middle;" />
    			<span id="api_text"></span><br />
    			<input type="text" name="api_key" id="api_key" value="" size="25" style="margin-left:18px;font-size:11px;"/>
    			<input type="button" class='button-secondary' name="api_verify" id="api_verify" value="Verify API Key" style="visibility:hidden;display:none;"/>
    			<input type="button" class='button-primary'   name="api_register" id="api_register" value="Register API Key" style="visibility:hidden;display:none"/>
			
			</div>
    		
    	<p /> 
		    
        	<div id="proxy" style="visibility:hidden;">
    			<img  id="proxy_image" src="<?php echo KILLABOT_DIRECTORY; ?>/images/waiting.gif" alt="processing..." style="vertical-align:middle;" />
    			<span id="proxy_text"></span><br />
    			<div style="text-align:center;">
    			<input type="button" class='button-secondary' name="proxy_enable"  id="proxy_enable"  value="Enable Protection"  />
    			<input type="button" class='button-secondary' name="proxy_disable" id="proxy_disable" value="Disable Protection" />
    			</div>
    		</div>
    	</fieldset>
    	<p />
    	
    	<div id="proxy_test_div" style="border:1px solid #efefef;width:450px;text-align:center;visibility:hidden;display:none;">
  			<input type="button" class='button-primary' name="proxy_test_button"  id="proxy_test_button"  value="Anonymous Proxy Test"  />
  		</div><br />
    	<a href="<?php echo KILLABOT_DIRECTORY; ?>/killabot-apx-test.php?popupwindow&uri=<?php print get_option('siteurl') ?>/wp-comments-post.php" id="proxy_test_link" class="popupwindow" rel="windowCenter" style="visibility:hidden;"></a>
    	
    	</form>
   		</div>
	</div>
</div>
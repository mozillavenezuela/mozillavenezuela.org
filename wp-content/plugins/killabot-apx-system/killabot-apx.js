//-----------------------------------------------------------------------------	
// INITIALIZATION:
//-----------------------------------------------------------------------------	
function killabot_DBcheck(){
	clearTimeout(killabot_timer);
	jQuery.post(killabot_ajax,{action:"db_check","cookie": encodeURIComponent(document.cookie)}, 
		function(db_check){
			if(db_check==0){
				jQuery("#install_text").html('<i>Installing Killabot APx Plugin Database.</i>'); 
				jQuery.post(killabot_ajax,{action:"db_install","cookie": encodeURIComponent(document.cookie)}, 
					function(db_install){
						if(db_install==1){
				       		killabot_timer = setTimeout('killabot_DBcheck()', 500);
						}
					}
				);
			}else{
				jQuery("#install_text").html('<i>Killabot APx Plugin Database is properly installed.</i>'); 
	    		jQuery("#install_image").attr('src',killabot_glite);
	    		killabot_api_key = killabot_DB('api_key');
	    		killabot_timer = setTimeout('killabot_check_connectivity()', 500);
			}	
		}
	);
}
//-----------------------------------------------------------------------------
// CONNECTIVITY:
//-----------------------------------------------------------------------------	
function killabot_check_connectivity(){
	clearTimeout(killabot_timer);
	jQuery("#connect").css("visibility", "visible");
	jQuery("#connect_text").html('<i>Checking Connectivity to Web Service...</i>');
	jQuery.post(killabot_ajax,{action:"ws_ping","cookie": encodeURIComponent(document.cookie)}, 
		function(ws_ping){
			if(ws_ping == 1){
				killabot_timer = setTimeout('killabot_show_connectivity()', 500);
			}
		},"text"
	);
}
function killabot_show_connectivity(){
	clearTimeout(killabot_timer);
	jQuery("#connect_image").attr('src',killabot_glite);
	jQuery("#connect_text").html('<i>Connection to Web Service has been established.</i>');
	killabot_timer = setTimeout('killabot_check_api_key()', 500);
}
//-----------------------------------------------------------------------------
// API KEY:
//-----------------------------------------------------------------------------	
function killabot_check_api_key(){
	clearTimeout(killabot_timer);
	jQuery.post(killabot_ajax,{action:"db_value","cookie": encodeURIComponent(document.cookie),
		field:"reg"}, 
		function(reg_val){
			jQuery("#api").css("visibility", "visible");
			jQuery("#api_key").attr('value',killabot_api_key);
			if(reg_val == 1){
				jQuery("#api_image").attr('src',killabot_wlite);
	    		jQuery("#api_text").html('<i>Verifying Killabot API Key...</i>');
	        	killabot_timer = setTimeout('killabot_show_verify_button()', 500);
	    	}else{
	    		jQuery("#api_image").attr('src',killabot_wlite);
	        	jQuery("#api_text").html('<i>Checking Killabot APx Key...</i>');
	        	killabot_timer = setTimeout('killabot_show_register_button()', 500); 
	    	}
		}
	);
}
function killabot_show_register_button(){
	clearTimeout(killabot_timer);
	jQuery("#api_image").attr('src',killabot_rlite);
	jQuery("#api_text").html('<i>This Killabot API Key needs to be registered</i>');
	jQuery("#api_register").css("visibility", "visible"); 
	jQuery("#api_register").css("display", "inline");
}
function killabot_show_verify_button(){
	clearTimeout(killabot_timer);
	var api_key  = jQuery('input[name=api_key]').val();
	jQuery.post(killabot_ajax,{action:"ui_register","cookie": encodeURIComponent(document.cookie),
      	key:api_key}, 
			function(ui_verify){
				if(ui_verify == 1){
					jQuery("#api_image").attr('src',killabot_glite); 
	      			jQuery("#api_text").html('<i>This Killabot API Key is </i><span style="color:green;font-weight:bold;">VALID</span>.');
	      			jQuery("#api_verify").css("display", "inline");
	      			jQuery("#api_verify").css("visibility", "visible");
	      			jQuery("#proxy").css("visibility", "visible");
	      			jQuery("#proxy_text").html('<i>Checking Anonymous Proxy detection...</i>');
	      			killabot_timer = setTimeout('killabot_proxy_detection()', 500); 	
				}
			},"text"
	);
}
//-----------------------------------------------------------------------------
// ANONYMOUS PROXY DETECTION:
//-----------------------------------------------------------------------------	
function killabot_proxy_detection(){
	clearTimeout(killabot_timer);
	jQuery.post(killabot_ajax,{action:"db_value","cookie": encodeURIComponent(document.cookie),
		field:"protect"}, 
		function(protect){
			if(protect == 1){
	        	jQuery("#proxy_image").attr('src',killabot_glite);
				jQuery("#proxy_text").html('<i>Anonymous Proxy Detection is </i><span style="color:green;font-weight:bold;">ON.</span>');
				jQuery("#proxy_test_div").css("visibility", "visible");
				jQuery("#proxy_test_div").css("display", "inline");
			}else{
				jQuery("#proxy_image").attr('src',killabot_rlite);
				jQuery("#proxy_text").html('<i>Anonymous Proxy Detection is </i><span style="color:red;font-weight:bold;">OFF.</span>');
			}
		}
	);
}
function killabot_show_proxy(){
	clearTimeout(killabot_timer);
	jQuery("#proxy").css("visibility", "visible");
	jQuery("#proxy_text").html('<i>Checking Anonymous Proxy detection...</i>');
	killabot_timer = setTimeout('killabot_proxy_detection()', 500);
}
function killabot_show_enable_proxy(){
	clearTimeout(killabot_timer);
	jQuery("#proxy_image").attr('src',killabot_glite);
	jQuery("#proxy_test_div").css("visibility", "visible");
	jQuery("#proxy_test_div").css("display", "inline");
	jQuery("#proxy_text").html('<i>Anonymous Proxy Detection is </i><span style="color:green;font-weight:bold;">ON.</span>');
}
function killabot_show_disable_proxy(){
	clearTimeout(killabot_timer);
	jQuery("#proxy_image").attr('src',killabot_rlite);
	jQuery("#proxy_test_div").css("visibility", "hidden");
	jQuery("#proxy_test_div").css("display", "none");
	jQuery("#proxy_text").html('<i>Anonymous Proxy Detection is </i><span style="color:red;font-weight:bold;">OFF.</span>');
}
//-----------------------------------------------------------------------------
// DATABASE:
//-----------------------------------------------------------------------------	
function killabot_DB(field){
	 var value;
	 jQuery.ajax({ 
         type: "POST", 
         url: killabot_ajax, 
         data: ({
         	action:"db_value",
         	"cookie": encodeURIComponent(document.cookie),
         	field:field
         }), 
         async: false, 
         dataType: "text", 
         success: function(data){value = data} 
   	});
   	return value;
}
//-----------------------------------------------------------------------------	
// Document.Ready
//-----------------------------------------------------------------------------	
jQuery(document).ready(function(){
				
	var profiles = {
		windowCenter:{height:550,width:780,center:1},
		windowFAQ:{height:550,width:780,center:1,scrollbars:1}
	};	
	jQuery("#install").css("visibility", "visible");
	jQuery("#install_text").html('<i>Checking Killabot APx Plugin Database...</i>'); 
	killabot_timer = setTimeout('killabot_DBcheck()', 500);
	
	// API Key Registration Button
	jQuery("#api_register").click(function () {
		jQuery("#api_image").attr('src',killabot_wlite); 
   		jQuery("#api_text").html('<i>Registering Killabot API Key...</i>');
   		jQuery.post(killabot_ajax,{action:"ui_register","cookie": encodeURIComponent(document.cookie)}, 
			function(ui_register){
				//alert(ui_register);
				if(ui_register == 1){
					jQuery("#api_image").attr('src',killabot_glite); 
		   			jQuery("#api_text").html('<i>This Killabot API Key is </i><span style="color:green;font-weight:bold;">VALID</span>.');
		   			jQuery("#api_verify").css("visibility", "visible");
		   			jQuery("#api_verify").css("display", "inline");
		   			jQuery("#api_register").css("visibility", "hidden");
		   			jQuery("#api_register").css("display", "none");
		   			killabot_timer = setTimeout('killabot_show_proxy()', 500);
				}
			},"text"
		);
   	});
   	
   	// API Key Verify Button
    jQuery("#api_verify").click(function () {
		jQuery("#api_image").attr('src',killabot_wlite); 
      	jQuery("#api_text").html('<i>Verifying Killabot API Key...</i>');
      	var api_key  = jQuery('input[name=api_key]').val();
      	jQuery.post(killabot_ajax,{action:"ui_register","cookie": encodeURIComponent(document.cookie),
      	key:api_key}, 
			function(ui_verify){
				if(ui_verify == 1){
					jQuery("#api_image").attr('src',killabot_glite); 
	      			jQuery("#api_text").html('<i>This Killabot API Key is </i><span style="color:green;font-weight:bold;">VALID</span>.');
	      			jQuery("#api_verify").css("visibility", "visible");
	      			jQuery("#api_register").css("visibility", "hidden");
				}else{
					jQuery("#api_image").attr('src',killabot_rlite); 
	      			jQuery("#api_text").html('<i>This Killabot API Key is </i><span style="color:red;font-weight:bold;">INVALID</span>.');
	      			jQuery("#api_verify").css("visibility", "visible");
	      			jQuery("#api_register").css("visibility", "hidden");
				}		
			},"text"
		);
    });
    
    // Enable Protection Button
    jQuery("#proxy_enable").click(function () {
		jQuery("#proxy_image").attr('src',killabot_wlite); 
   		jQuery("#proxy_text").html('<i>Enabling Anonymous Proxy protection...</i>');
   		jQuery.post(killabot_ajax,{action:"ui_protectE","cookie": encodeURIComponent(document.cookie)}, 
			function(ui_protectE){
				if(ui_protectE == 1){
					killabot_timer = setTimeout('killabot_show_enable_proxy()', 500);
				}
			},"text"
		);
	});
    
     // Disable Protection Button
    jQuery("#proxy_disable").click(function () {
		jQuery("#proxy_image").attr('src',killabot_wlite); 
   		jQuery("#proxy_text").html('<i>Disabling Anonymous Proxy protection...</i>');
   		jQuery.post(killabot_ajax,{action:"ui_protectD","cookie": encodeURIComponent(document.cookie)}, 
			function(ui_protectD){
				if(ui_protectD == 1){
					killabot_timer = setTimeout('killabot_show_disable_proxy()', 500);
				}
			},"text"
		);
    });
    
    
    jQuery("#proxy_test_button").click(function() { 
		jQuery('#proxy_test_link').click();
	}); 
    jQuery(function(){
		jQuery(".popupwindow").popupwindow(profiles);
	});    
	
}); // End (document).ready
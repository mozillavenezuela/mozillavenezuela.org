/**
 * WP Backitup Admin Control Panel JavaScripts
 * 
 * @version 1.3.0
 * @since 1.0.1
 */

(function($){
	//define backup variables
	var backup = {
		action: 'backup',
		beforeSend: function() {
			$('.backup-icon').css('visibility','visible');
			var htmlText = "<div class='prerequisites'>Checking prerequisites: <span class='currentStatus'>Pending</span></div><div class='backupfiles'>Backing-up /wp-content/: <span class='currentStatus'>Pending</span></div><div class='backupdb'>Backing-up database: <span class='currentStatus'>Pending</span></div><div class='infofile'>Creating backup directory: <span class='currentStatus'>Pending</span></div><div class='zipfile'>Zipping backup directory: <span class='currentStatus'>Pending</span></div><div class='cleanup'>Cleaning up: <span class='currentStatus'>Pending</span></div><div class='errorMessage'><span class='currentStatus'></span></div>";
			$("#status").html(htmlText);
		    window.intervalDefine = setInterval(display_log, 1000);
		}
	};

	//define download variables
	var download = {
		action: 'download'
	};
	//define logreader variables
	var logreader = {
		action: 'logreader'
	};
	//define logreader function
	function display_log() {		
		$.post(ajaxurl, logreader, function(response) {
			var xmlObj = $(response);
            xmlObj.each(function() {
                var attributename = "." + $(this).attr('code');
                $(attributename).find(".currentStatus").html($(this).text());
                if($(this).attr('code') == "finalinfo" || $(this).attr('code') == "errorMessage") {
                    clearInterval(window.intervalDefine);
                }
            });
		});
	}

	//define download function
	function download_link() {
		$.post(ajaxurl, download, function(response) {
			$("#download-link").html(response);
		});
	}

	//execute download (on page load/refresh)
	download_link();
	
	//execute backup on button click
    $(".backup-button").click( function() {
        $.post(ajaxurl, backup, function(response) {
			download_link(); 
			clearInterval(display_log); 
			$('.backup-icon').fadeOut(1000); 
			$("#php").html(response); //Return PHP messages, used for development
        });   
    })
    
    //execute restore on button click
	$("#restore-form").submit(function() {
		var htmlvals = '<div class="upload">Uploading: <span class="currentStatus">Pending</span></div><div class="unzipping">Unzipping: <span class="currentStatus">Pending</span></div><div class="validation">Validating restoration file: <span class="currentStatus">Pending</span></div><div class="wpcontent">Restoring /wp-content/ directory: <span class="currentStatus">Pending</span></div><div class="database">Restoring database: <span class="currentStatus">Pending</span></div><div class="infomessage"><span class="currentStatus"></span></div><div class="errorMessage"><span class="currentStatus"></span></div>';
		$("#status").html(htmlvals);
		$(".upload").find('.currentStatus').html('In Progress');
		$('.restore-icon').css('visibility','visible');  
		window.intervalDefine = setInterval(display_log, 1000);
		$("#restore-form").attr("target","upload_target"); 
		$("#upload_target").load(function (){
			importRestore(); 
		});
	});
	
	//define importRestore function
	function importRestore() {
		var ret = frames['upload_target'].document.getElementsByTagName("body")[0].innerHTML; //process upload
		$("#php").html(ret); //Return PHP messages, used for development
		clearInterval(display_log); 
		$('.restore-icon').fadeOut(1000); 
	}
})(jQuery);
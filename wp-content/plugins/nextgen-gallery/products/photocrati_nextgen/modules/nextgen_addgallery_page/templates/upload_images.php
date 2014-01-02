<div id="gallery_selection">
    <label for="gallery_id">Gallery</label>
    <select id="gallery_id">
        <option value="0">Create a new gallery</option>
        <?php foreach ($galleries as $gallery): ?>
            <option value="<?php echo esc_attr($gallery->{$gallery->id_field}) ?>"><?php echo esc_attr($gallery->title) ?></option>
        <?php endforeach ?>
    </select>
    <input type="text" id="gallery_name" name="gallery_name"/>
</div>

<div id="uploader">
    <p>You browser doesn't have Flash, Silverlight, HTML5, or HTML4 support.</p>
</div>
<script type="text/javascript">
    (function($){

        // Listen for events emitted in other frames
        if (window.Frame_Event_Publisher) {

            // If a gallery has been deleted, remove it from the drop-downs of available galleries
            Frame_Event_Publisher.listen_for('attach_to_post:manage_galleries', function() {
				window.location.href = window.location.href;
            });
        }


		$(function(){
                // Show the page content
                $('#ngg_page_content').css('visibility', 'visible');

                // Only execute this code once!
                var flag = 'addgallery';
                if (typeof($(window).data(flag)) == 'undefined')
                    $(window).data(flag, true);
                else return;

                window.urlencode = function(str){
                    str = (str + '').toString();

                    // Tilde should be allowed unescaped in future versions of PHP (as reflected below), but if you want to reflect current
                    // PHP behavior, you would need to add ".replace(/~/g, '%7E');" to the following.
                    return encodeURIComponent(str).replace(/!/g, '%21').replace(/'/g, '%27').replace(/\(/g, '%28').
                        replace(/\)/g, '%29').replace(/\*/g, '%2A').replace(/%20/g, '+');
                };

                // Sets the plupload url with necessary parameters in the QS
                window.set_plupload_url = function(gallery_id, gallery_name) {
                    var qs = "?action=upload_image&gallery_id="+urlencode(gallery_id);
                    qs += "&gallery_name="+urlencode(gallery_name);
                    return photocrati_ajax.url + qs;
                };

                // Reinitializes plupload
                window.reinit_plupload = function(up){
                    $("#uploader").animate({
                        'opacity': 0.0,
                    }, 'slow');
                    up.destroy();
                    $('#gallery_id').val(0);
                    $('#gallery_name').val('');
                    init_plupload();
                    $("#uploader").animate({
                        'opacity': 1.0
                    }, 'slow');
                };

                // Initializes plupload
                window.init_plupload = function() {
                    var plupload_options =  <?php echo $plupload_options ?>;
                    var $gallery_id = $('#gallery_id');
                    var $gallery_name = $('#gallery_name').show();
                    var $gallery_selection = $('#gallery_selection').detach();
                    window.uploaded_image_ids = [];

                    // Override some final plupload options
                    plupload_options.url = photocrati_ajax.url;
                    plupload_options.preinit = {
                        PostInit: function(up){

                            // Hide/show the gallery name field
                            $gallery_selection.insertAfter('.plupload_header');
                            var gallery_select    = $('#gallery_id');
                            gallery_select.on('change', function(){
                                var optionSelected = $("option:selected", this);
                                var valueSelected = parseInt(this.value);

                                if (valueSelected == 0) {
                                    $('#gallery_name:hidden').fadeIn().focus(function(){
                                        up.refresh(); // must be done for IE
                                    }).focus();
                                }
                                else {
                                    $('#gallery_name:visible').fadeOut(400, function(){
                                        gallery_select.focus();
                                        up.refresh(); // must be done for IE
                                    });
                                }
                            });

                            // Change the text for the dragdrop
                            $('.plupload_droptext').html("Drag image and ZIP files here or click <strong>Add Files</strong>");

                            // Move the buttons
                            var buttons = $('.plupload_buttons').detach();
                            $gallery_selection.append(buttons);

                            // Hide/show the validation for the gallery name field
                            $gallery_name.keypress(function(){
                                if ($gallery_name.val().length > 0) {
                                    $gallery_name.removeClass('error');
                                }
                            });

                            // Don't let the uploader continue without a gallery name
                            var start_button = $('#uploader a.plupload_start');
                            start_button.click(function(e){
                                e.preventDefault();

                                var up = $('#uploader').pluploadQueue();

                                if ($gallery_id.val() == 0 && $gallery_name.val().length == 0) {
                                    $gallery_name.addClass('error');
                                    e.stopImmediatePropagation();
                                    alert("Please enter a gallery name");
                                    $gallery_name.focus();
                                    return false;
                                }
                                else {
                                    $gallery_name.removeClass('error');
                                    return true;
                                }
                            });

                            // Rearrange event handler for start button, to ensure that it has the ability
                            // to execute first
                            var click_events = $._data(start_button[0], 'events').click;
                            if (click_events.length == 2) click_events.unshift(click_events.pop());

                        },

                        // change url before upload
                        BeforeUpload: function(up, file) {
                            up.settings.url = window.set_plupload_url($gallery_id.val(), $gallery_name.val());
                        },

                        // Refresh the interface after a successful upload
                        StateChanged: function(up){

                            // Determine appropriate message to display
                            var upload_count = window.uploaded_image_ids.length;
                            var msg = upload_count + " images were uploaded successfully";
                            if (upload_count == 1) {
                                msg = "1 image was uploaded successfully";
                            }
                            else if (upload_count == 0) {
                                msg = "0 images were uploaded";
                            }

                            // Display message/notification
                            if (up.state == plupload.STOPPED) {
								if (typeof(up.error_msg) != 'undefined') {
									$.gritter.add({
										title: up.error_msg,
										text: msg,
										sticky: true
									});
								}
								else {
									$.gritter.add({
										title: "Upload complete",
										text: msg,
										sticky: true
									});
								}

                                setTimeout(function(){
                                    reinit_plupload(up);
                                }, 3000);
                            }
                        },

                        // When a gallery has been created, use the same gallery for each request going forward
                        FileUploaded: function(up, file, info){
                            var response = info.response;
                            if (typeof(response) != 'object') {
                                try {
                                    response = JSON.parse(info.response);
                                }
                                catch (ex) {
                                    up.trigger('Error', {
                                        code: plupload.IO_ERROR,
                                        msg:  "An unexpected error occured. This is most likely due to a server misconfiguration. Check your PHP error log or ask your hosting provider for assistance.",
                                        details: response.replace(/<.*>/, '').trim(),
                                        file: file
                                    });
                                    return;
                                }
                            }
							if(typeof(response.error) != 'undefined') {
								up.trigger('Error', {
									code: plupload.IO_ERROR,
									msg: response.error,
									details: response,
									file: file
								});
							}
							else {
								window.uploaded_image_ids = window.uploaded_image_ids.concat(response.image_ids);
								up.settings.url = window.set_plupload_url(response.gallery_id, $gallery_name.val());

								// If we created a new gallery, ensure it's now in the drop-down list, and select it
								if ($gallery_id.find('option[value="'+response.gallery_id+'"]').length == 0) {
									var option = $('<option/>').attr('value', response.gallery_id).text(response.gallery_name);
									$gallery_id.append(option);
									$gallery_id.val(response.gallery_id);
									option.attr('selected', 'selected');
								}

								// our Frame-Event-Publisher hooks onto the jQuery ajaxComplete action which plupload
								// of course does not honor. Tie them together here..
								if (window.Frame_Event_Publisher) {
									$.post(photocrati_ajax.url, {'action': 'cookie_dump'}, function(){
										window.Frame_Event_Publisher.find_parent(window).broadcast();
									});
								}
							}
                        },

                        Error: function(up, args){
							if (typeof(up.error_msg) == 'undefined') {
								up.error_msg = args.msg;
							}{}
                        }
                    };
                    $("#uploader").pluploadQueue(plupload_options);
                    var uploader = $('#uploader').pluploadQueue();
                    uploader.refresh();

                };

                window.init_plupload();
            });
    })(jQuery);
</script>

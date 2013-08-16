jQuery(function($){

    // Activate accordions
    $('.accordion').accordion({
        clearStyle: true,
        autoHeight: false,
        heightStyle: 'content'
    });

     // When a submit button is clicked...
	$('input[type="submit"]').click(function(e){
		var $button = $(this);
		var message = false;

		// Check if a confirmation dialog is required
		if ((message = $button.attr('data-confirm'))) {
			if (!confirm(message)) {
				e.preventDefault();
				return;
			}
		}

		// Check if this is a proxy button for another field
		if ($button.attr('name').indexOf('_proxy') != -1) {

			// Get the value to set
			var value = $button.attr('data-proxy-value');
			if (!value) value = $button.attr('value');

			// Get the name of the field that is being proxied
			var field_name = $button.attr('name').replace('_proxy', '');

			// Try getting the existing field
			var $field = $('input[name="'+field_name+'"]');
			if ($field.length > 0) $field.val(value);
			else {
				$field = $('<input/>').attr({
					type: 'hidden',
					name: field_name,
					value: value
				});
				$button.parents('form').append($field);
			}
		}
	});
    
    
	// Toggle the advanced settings
	$('.nextgen_advanced_toggle_link').on('click', function(e){
		e.preventDefault();
		var form_id = '#'+$(this).attr('rel');
		var btn = $(this);
		$(form_id).toggle(500, 'swing', function(){
			if ($(this).hasClass('hidden')) {
				$(this).removeClass('hidden');
				btn.text(btn.attr('active_label'));
			}
			else {
				$(this).addClass('hidden');
				btn.text(btn.attr('hidden_label'));
			}
		});
	});

    $('input.nextgen_settings_field_colorpicker').wpColorPicker();
    $('#ngg_page_content').css('visibility', 'visible');
});
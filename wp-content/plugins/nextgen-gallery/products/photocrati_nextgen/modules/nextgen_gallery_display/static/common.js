(function($){
    window.NggPaginatedGallery = function(displayed_gallery_id, container, links) {
        this.displayed_gallery_id = displayed_gallery_id;
        this.links                = links;
        this.container            = container;

        this.get_displayed_gallery_obj = function(){
            var index = 'gallery_'+this.displayed_gallery_id;
            if (typeof(window.galleries[index]) == 'undefined')
                return false;
            else
                return window.galleries[index];
        };


        this.enable_ajax_pagination = function(){
            var transient_id = this.get_displayed_gallery_obj().transient_id;
            var obj         = this;

            // Attach a click event handler for each pagination link to
            // adjust the request to be sent via XHR
            this.links.each(function(){
                var $link = $(this);
                $link.click(function(e){
                    e.preventDefault();

                    // Describe AJAX request
                    var request = {
                        action: 'render_displayed_gallery',
                        displayed_gallery_id: transient_id,
                        ajax_referrer: $link.attr('href')
                    }

                    // Notify the user that we're busy
                    obj.do_ajax(request);
                });
            });
        };

        this.do_ajax = function(request){

            var container    = this.container;

            // Adjust the user notification
            window['ngg_ajax_operaton_count']++;
            $('body, a').css('cursor', 'wait');

            // Send the AJAX request
            $.post(photocrati_ajax.url, request, function(response){

                // Adjust the user notification
                window['ngg_ajax_operaton_count']--;
                if (window['ngg_ajax_operaton_count'] <= 0) {
                    window['ngg_ajax_operaton_count'] = 0;
                    $('body, a').css('cursor', 'auto');
                }

                // Ensure that the server returned JSON
                if (typeof(response) != 'object') response = JSON.parse(response);
                if (response) {
                    container.replaceWith(response.html);

                    // Let the user know that we've refreshed the content
                    $(document).trigger('refreshed');
                }
            });
        };

        // Initialize
        var displayed_gallery = this.get_displayed_gallery_obj();
        if (displayed_gallery) {
            if (typeof(displayed_gallery.display_settings['ajax_pagination']) != 'undefined') {
                if (parseInt(displayed_gallery.display_settings['ajax_pagination'])) {
                    this.enable_ajax_pagination();
                }
            }
        }

        // We maintain a count of all the current AJAX actions initiated
        if (typeof(window['ngg_ajax_operation_count']) == 'undefined') {
            window['ngg_ajax_operaton_count'] = 0;
        }
    };

})(jQuery);
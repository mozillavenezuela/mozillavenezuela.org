jQuery(function($){
    NggAjaxNavigation = {
        loading_image:            null,

        /**
         * Initializes the AJAX paginators
         */
        init:                     function(){
            var self = this;
            jQuery('body').on('click', 'a.page-numbers, a.prev, a.next', function(e){
              var $this     = $(this);
              var $gallery  = $this.parents('.ngg-galleryoverview:first');
              var gallery_id= $gallery.attr('id').replace('ngg-gallery-','').replace(/-\d+$/, '');
              
              if ($gallery.hasClass('ngg-ajax-pagination-none')) 
              	return;
              
              e.preventDefault();

              self.toggle_busy(true);

              // Create a request to render a displayed gallery
              var params = self.get_querystring_params_from_url($this.attr('href'));
              params['action']                   = 'get_displayed_gallery_page';
              params['displayed_gallery_id']     = gallery_id;
              params['page']                     = $this.data('pageid');
              params['ajax_pagination_referrer'] = document.URL;

              $.get(photocrati_ajax.url, params, function(response){

                  // Ensure that the server returned JSON
                  if (typeof(response) != 'object') response = JSON.parse(response);
                  if (response) {
                      $gallery.replaceWith(response.html);
                  }

                  // Let the user know that we've refreshed the content
                  $(document).trigger('refreshed');
              }).always(function() { 
                  self.toggle_busy(false);
              });

            });
        },

        /**
         * Gets the querystring parameters for a url
         * @param url
         * @return {Object}
         */
        get_querystring_params_from_url: function(url){
            var url_params = {};
            var url_parts = url.split('?');
            if (url_parts.length == 2) {
                url_parts = url_parts[1].split('&');
                for (var key in url_parts) {
                    var param = url_parts[key].split('=');
                    url_params[param[0]] = param.length == 2 ? param[1] : '';
                }
            }
            return url_params;
        },


        toggle_busy:                    function(busy) {
            $('body, a').css('cursor', busy ? 'wait' : 'auto');
        }
    };

    NggAjaxNavigation.init();
});

jQuery.fn.nggShowSlideshow = function(args) {
  
    var defaults = {
        id: 1,
        width: 600,
        height: 400,
        fx: 'fade',
        domain: '',
        timeout: 5000
    };
                   
    var s = jQuery.extend({}, defaults, args);
    var selector = this.selector;
	
    jQuery(selector + '-loader').empty().remove();
		
		var container = jQuery(selector);
    var gallery = jQuery(selector + '-image-list');
    var self = this;

    jQuery(gallery).waitForImages(function() {
        var list = gallery.contents().detach();
        var placeholder = container.attr('data-placeholder');
        
        gallery.remove();

        list.appendTo(self);

        self.show();
        
        if (placeholder) {
        	self.prepend('<img class="image-placeholder" src="' + placeholder + '" width="' + s.width + '" height="' + s.height + '" style="width: ' + s.width + 'px; height: ' + s.height + 'px;" />');
        }

        if (self.children().length > 1) {
            self.cycle({
                fx: s.fx,
                slideExpr: '.ngg-gallery-slideshow-image',
                slideResize: false,
                containerResize: false,
                fit: 1,
                timeout: s.timeout,
                next: self, // advance to next image when clicked
                after: function(currSlideElement, nextSlideElement, options, forwardFlag) {
                    // update the pro-lightbox triggers should they exist
                    jQuery(nextSlideElement).parent().siblings('div.ngg-trigger-buttons').each(function() {
                        jQuery(this).find('i').each(function() {
                            jQuery(this).data('image-id', jQuery(nextSlideElement).find('img').data('image-id'));
                        });
                    });
                }
            });
        }
    });
};

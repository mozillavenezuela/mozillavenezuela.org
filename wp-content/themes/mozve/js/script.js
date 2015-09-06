jQuery(document).ready(function () {
   	
	(function(jQuery) {
    jQuery.fn.clickToggle = function(func1, func2) {
        var funcs = [func1, func2];
        this.data('toggleclicked', 0);
        this.click(function() {
            var data = jQuery(this).data();
            var tc = data.toggleclicked;
            jQuery.proxy(funcs[tc], this)();
            data.toggleclicked = (tc + 1) % 2;
        });
        return this;
    };
	}(jQuery));
	
	
	
	jQuery("#nav-main-toggle").clickToggle(function() {	
    	jQuery(this).css('background-position', 'center -100px');
		jQuery('#mobilemenu #menu-main-menu-1').stop().animate({height: "131px"}, 350);
		jQuery('#mobilemenu').css('display', 'block');
	}, function() {
    	jQuery(this).css('background-position', 'center 0px');
		jQuery('#mobilemenu #menu-main-menu-1').stop().animate({height: "0px"}, 350, function() {
    		jQuery('#mobilemenu').css('display', 'none');
			jQuery('#mobilemenu #menu-main-menu-1').css('display', 'none');
  		});
	});	
			
});
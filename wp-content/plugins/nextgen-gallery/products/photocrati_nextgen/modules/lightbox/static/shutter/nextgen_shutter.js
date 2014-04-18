jQuery(function($) {
    var callback = function() {
        var selector = nextgen_lightbox_filter_selector($, $([]));
        selector.addClass('shutterset');
        var shutterLinks = {},
            shutterSets = {};
        window.shutterSettings = {imageCount: true};
        shutterReloaded.init();
    };
    $(this).bind('refreshed', callback);

    var flag = 'shutter';

    if (typeof($(window).data(flag)) == 'undefined') {
        $(window).data(flag, true);
    } else {
        return;
    }

    callback();
});

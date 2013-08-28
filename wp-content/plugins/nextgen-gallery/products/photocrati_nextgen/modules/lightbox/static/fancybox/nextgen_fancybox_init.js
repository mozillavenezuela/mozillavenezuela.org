jQuery(function($) {
    var nextgen_fancybox_init = function() {
        $(".ngg-fancybox").fancybox({
            titlePosition: 'inside',
            // Needed for twenty eleven
            onComplete: function() {
                $('#fancybox-wrap').css('z-index', 10000);
            }
        });
    };
    $(this).bind('refreshed', nextgen_fancybox_init);
    nextgen_fancybox_init();
});

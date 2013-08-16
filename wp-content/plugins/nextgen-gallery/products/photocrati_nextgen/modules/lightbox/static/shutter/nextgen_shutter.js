jQuery(function($){
    var callback = function(){
        var shutterLinks = {}, shutterSets = {}; shutterReloaded.init();
    };
    $(this).bind('refreshed', callback);

    var flag = 'shutter';
    if (typeof($(window).data(flag)) == 'undefined')
        $(window).data(flag, true);
    else return;

    callback();
});

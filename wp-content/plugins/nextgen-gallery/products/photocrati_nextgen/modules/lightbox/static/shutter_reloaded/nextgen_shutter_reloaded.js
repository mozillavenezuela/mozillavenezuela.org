jQuery(function($){
	var callback = function(){
		var shutterLinks = {}, shutterSets = {}; shutterReloaded.Init();
	};
	$(this).bind('refreshed', callback);

   var flag = 'shutterReloaded';
   if (typeof($(window).data(flag)) == 'undefined')
       $(window).data(flag, true);
   else return;

   callback();
});
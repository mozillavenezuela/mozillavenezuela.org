jQuery(function($){

	// Creates a Firefox-friendly wrapper around jQuery Tabs
	$.fn.ngg_tabs = function(options){

		// Create jQuery tabs
		this.tabs(options);

		// Change from display:none to visbibility:hidden
		var i = 0;
		this.find('.main_menu_tab').each(function(){
			if (i == 0) $.fn.ngg_tabs.show_tab(this);
			else		$.fn.ngg_tabs.hide_tab(this);
			i++;
		});

		// When the selected tab changes, then we need to re-adjust
		this.bind('tabsactivate', function(event, ui){

			// Ensure that all tabs are still displayed, but hidden ;)
			$.fn.ngg_tabs.hide_tab($.fn.ngg_tabs.get_tab_by_li(ui.oldTab));
			$.fn.ngg_tabs.show_tab($.fn.ngg_tabs.get_tab_by_li(ui.newTab));
		});
	};

	$.fn.ngg_tabs.hide_tab = function(tab){
		tab = $(tab);
		setTimeout(function(){
			tab.css({
				display:	'block',
				position:	'absolute',
				top:		-1000,
				visibility:	'hidden',
				height:     0
			});
		}, 0);
	};

	$.fn.ngg_tabs.show_tab = function(tab){
		tab = $(tab);
		setTimeout(function(){
			tab.css({
				display:	'block',
				position:	'static',
				top:		0,
				visibility: 'visible',
				height:		'100%'
			});
		}, 0);
	};

	$.fn.ngg_tabs.get_tab_by_li = function(list_item){
		var active_id = list_item.attr('aria-labelledby');
		var active_tab = list_item.parents('div').find('.main_menu_tab[aria-labelledby="'+active_id+'"]');
		return active_tab;
	}
});
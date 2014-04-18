// Apply default settings
var galleryOptions = {
	  align: 'center',
	  dimmingOpacity: 0.8,
	  fadeInOut: true,
	  marginBottom: 80,
	  marginLeft: 100,
	  numberPosition: 'caption',
	  slideshowGroup: 'gallery',
	  transitions: ['expand', 'crossfade'],
	  wrapperClassName: 'dark borderless floating-caption',
	  graphicsDir: nextgen_highslide_graphics_dir + '/'
};

hs.align            = galleryOptions['align'];
hs.dimmingOpacity   = galleryOptions['dimmingOpacity'];
hs.fadeInOut        = galleryOptions['fadeInOut'];
hs.marginBottom     = galleryOptions['marginBottom'];
hs.marginLeft       = galleryOptions['marginLeft'];
hs.numberPosition   = galleryOptions['numberPosition'];
hs.transitions      = galleryOptions['transitions'];
hs.showCredits      = galleryOptions['showCredits'];
hs.graphicsDir      = galleryOptions['graphicsDir'];
hs.wrapperClassName = galleryOptions['wrapperClassName'];

jQuery(function($) {
	var selector = nextgen_lightbox_filter_selector($, $([]));
	selector.addClass('highslide');
	selector.click(function () { return hs.expand(this) });
	
	hs.updateAnchors();
	
	// Enable slideshows
	hs.addSlideshow({
		  fixedControls: true,
		  interval: 5000,
		  overlayOptions: {
		      hideOnMouseOut: true,
		      opacity: .6,
		      position: 'top center'
		  },
		  repeat: true,
		  useControls: true
	});
});

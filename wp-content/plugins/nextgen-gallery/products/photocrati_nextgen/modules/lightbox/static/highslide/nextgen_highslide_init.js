var galleryOptions = {
    align: 'center',
    dimmingOpacity: 0.8,
    fadeInOut: true,
    marginBottom: 80,
    marginLeft: 100,
    numberPosition: 'caption',
    slideshowGroup: 'gallery',
    transitions: ['expand', 'crossfade'],
    wrapperClassName: 'dark borderless floating-caption'
};

hs.graphicsDir = nextgen_highslide_graphics_dir + '/';
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

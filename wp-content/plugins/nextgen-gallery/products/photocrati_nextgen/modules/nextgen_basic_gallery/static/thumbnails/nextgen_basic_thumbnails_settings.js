jQuery(function($) {
    $('input[name="photocrati-nextgen_basic_thumbnails[override_thumbnail_settings]"]')
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_thumbnails_thumbnail_dimensions'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_thumbnails_thumbnail_quality'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_thumbnails_thumbnail_crop'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_thumbnails_thumbnail_watermark'));

    $('input[name="photocrati-nextgen_basic_thumbnails[show_piclens_link]"]')
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_thumbnails_piclens_link_text'));

    $('input[name="photocrati-nextgen_basic_thumbnails[show_slideshow_link]"]')
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_thumbnails_slideshow_link_text'))
});
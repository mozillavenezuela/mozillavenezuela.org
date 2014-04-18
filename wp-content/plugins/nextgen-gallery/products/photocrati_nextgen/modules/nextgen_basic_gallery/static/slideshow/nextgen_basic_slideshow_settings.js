jQuery(function($) {
    $('input[name="photocrati-nextgen_basic_slideshow[show_thumbnail_link]"]')
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_slideshow_thumbnail_link_text'));

    $('input[name="photocrati-nextgen_basic_slideshow[flash_enabled]"]')
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_slideshow_flash_background_music'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_slideshow_flash_stretch_image'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_slideshow_flash_transition_effect'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_slideshow_flash_shuffle'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_slideshow_flash_next_on_click'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_slideshow_flash_navigation_bar'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_slideshow_flash_loading_icon'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_slideshow_flash_watermark_logo'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_slideshow_flash_slow_zoom'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_slideshow_flash_xhtml_validation'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_slideshow_flash_background_color'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_slideshow_flash_text_color'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_slideshow_flash_rollover_color'))
        .nextgen_radio_toggle_tr('1', $('#tr_photocrati-nextgen_basic_slideshow_flash_screen_color'));
});
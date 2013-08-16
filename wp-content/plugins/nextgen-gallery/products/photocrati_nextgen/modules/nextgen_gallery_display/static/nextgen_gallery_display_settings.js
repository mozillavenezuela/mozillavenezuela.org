jQuery(function($) {

    $('.accordion').accordion({
        clearStyle: true,
        autoHeight: false,
        heightStyle: 'content'
    });

    $('input, textarea').placeholder();
    $('label.tooltip, span.tooltip').tooltip();

});
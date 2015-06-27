/*global jQuery:false */
"use strict";

function jqueryArchiveListAnimate(clickedObj, listElements, options) 
{
    var changeSymbol = function () {
        var symbol = jQuery(this).is(":hidden") ? options.expSym : options.conSym;        
        jQuery(clickedObj).children(".jaw_symbol").html(symbol);
    };

    switch ( options.fxIn )
    {
        case "fadeIn":
            listElements.fadeToggle("", changeSymbol);
            break;
        case "slideDown":
            listElements.slideToggle("", changeSymbol);
            break;
        default:
            listElements.toggle(0, changeSymbol);
            break;
    }

    jQuery(clickedObj).parent().toggleClass("expanded");
}

jQuery(function() 
{
    jQuery(".jaw_widget").each(function(){
        var options = {
            fxIn: jQuery(this).siblings(".fx_in").val(),
            expSym: jQuery(this).siblings(".ex_sym").val(),
            conSym: jQuery(this).siblings(".con_sym").val(),
        };

        jQuery(this).on("click", "li.jaw_years a.jaw_years, li.jaw_months a.jaw_months", function(e)
        {
            var elements = jQuery(this).siblings("ul").children("li");

            if (elements.length)
            {
                e.preventDefault(); 
                jqueryArchiveListAnimate(this, elements, options);
            } 
        });
    });
});
(function( $ ) {
    $.widget("ui.combobox", {
        _create: function() {
            var input,
                that = this,
                select = this.element.hide(),
                selected = select.children(":selected"),
                value = selected.val() ? selected.text() : "",
                wrapper = this.wrapper = $("<span>").addClass("ui-combobox")
                                                    .insertAfter(select);

            function removeIfInvalid(element) {
                var value = $(element).val(),
                    matcher = new RegExp("^" + $.ui.autocomplete.escapeRegex(value) + "$", "i" ),
                    valid = false;

                select.children("option").each(function() {
                    if ($(this).text().match(matcher)) {
                        this.selected = valid = true;
                        return false;
                    }
                });

                if (!valid) {
                    var new_option = new Option(value, value, true, true);
                    select.append(new_option);
                    select.val(value);
                    $(element).val(value);
                    input.data('autocomplete').term = value;
                    return true;
                }
            }

            input = $("<input>").appendTo(wrapper)
                                .val(value)
                                .attr("title", "")
                                .addClass("ui-state-default ui-combobox-input")
                                .autocomplete({
                                    delay: 0,
                                    minLength: 0,
                                    source: function(request, response) {
                                        var matcher = new RegExp($.ui.autocomplete.escapeRegex(request.term), "i");
                                        response(select.children("option").map(function() {
                                            var text = $(this).text();
                                            if (!request.term || matcher.test(text)) {
                                                var label = text;
                                                if (request.term != '' && matcher.test(label)) {
                                                    label = label.replace(new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + $.ui.autocomplete.escapeRegex(request.term) + ")(?![^<>]*>)(?![^&;]+;)", "gi"), "<strong>$1</strong>");
                                                }
                                                return {
                                                    label: label,
                                                    value: text,
                                                    option: this
                                                };
                                            }
                                        }));
                                    },
                                    select: function(event, ui) {
                                        ui.item.option.selected = true;
                                        that._trigger("selected", event, {
                                            item: ui.item.option
                                        });
                                    },
                                    change: function(event, ui) {
                                        if (!ui.item) {
                                            return removeIfInvalid(this);
                                        }
                                    }
                                })
                                .addClass("ui-widget ui-widget-content ui-corner-left");
			var autocomplete = input.data("autocomplete");
            if (autocomplete) autocomplete._renderItem = function(ul, item) {
                return $("<li>").data("item.autocomplete", item)
                                .append("<a>" + item.label + "</a>")
                                .appendTo(ul);
            };

            $("<a>").attr("tabIndex", -1)
                    .attr("title", "Show All Items")
                    .appendTo(wrapper)
                    .button({
                        icons: { primary: "ui-icon-triangle-1-s" },
                        text: false
                    })
                    .removeClass("ui-corner-all")
                    .addClass("ui-corner-right ui-combobox-toggle")
                    .click(function() {
                        // close if already visible
                        if (input.autocomplete("widget").is(":visible")) {
                            input.autocomplete("close");
                            removeIfInvalid(input);
                            return;
                        }

                        // work around a bug (likely same cause as #5265)
                        $(this).blur();

                        // pass empty string as value to search for, displaying all results
                        input.autocomplete("search", "");
                        input.focus();
                    });
        },

        destroy: function() {
            this.wrapper.remove();
            this.element.show();
            $.Widget.prototype.destroy.call( this );
        }
    });
})(jQuery);

jQuery(function($) {
    $(".ngg_settings_template").combobox();
});

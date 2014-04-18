// Self-executing function to create and register the TinyMCE plugin
(function(siteurl) {

    tinyMCE.addI18n('en.ngg_attach_to_post', {
        title: 'Attach NextGEN Gallery to Post'
    });

	// Create the plugin. We'll register it afterwards
	tinymce.create('tinymce.plugins.NextGEN_AttachToPost', {

		/**
		 * The WordPress Site URL
		 */
		siteurl: siteurl,

		/**
		 * Returns metadata about this plugin
		 */
		getInfo: function() {
			return {
				longname: 'NextGEN Gallery',
				author: 'Photocrati Media',
				authorurl: 'http://www.photocrati.com',
				infourl: 'http://www.nextgen-gallery.com',
				version: '0.1'
			};
		},

		/**
		 * Initializes the plugin, this will be executed after the plugin has been created.
		 */
		init: function(editor, plugin_url) {
            var self = this;

            // TinyMCE 4s events are a bit weird, but this lets us listen to the window-manager close event
            editor.windowManager.nggOldOpen = editor.windowManager.open;
            editor.windowManager.open = function(one, two) {
                var modal = editor.windowManager.nggOldOpen(one, two);
                modal.on('close', self.wm_close_event);
            };

			// Register a new TinyMCE command
			editor.addCommand('ngg_attach_to_post', this.render_attach_to_post_interface, {
				editor: editor,
				plugin:	editor.plugins.NextGEN_AttachToPost
			});

			// Add a button to trigger the above command
			editor.addButton('NextGEN_AttachToPost', {
				title:	'ngg_attach_to_post.title',
				cmd:	'ngg_attach_to_post',
				image:	plugin_url+'/atp_button.png'
			});

			// When the shortcode is clicked, open the attach to post interface
			editor.settings.extended_valid_elements += ",shortcode";
			editor.settings.custom_elements = "shortcode";

            editor.on('mouseup', function(e) {
                if (e.target.tagName == 'IMG') {
                    if (self.get_class_name(e.target).indexOf('ngg_displayed_gallery') >= 0) {
                        editor.dom.events.cancel(e);
                        var id = e.target.src.match(/\d+$/);
                        if (id) id = id.pop();
                        var obj = tinymce.extend(self, {
                            editor: editor,
                            plugin: editor.plugins.NextGEN_AttachToPost,
                            id:		id
                        });
                        self.render_attach_to_post_interface(id);
                    }
                }
			});
		},

		get_class_name: function(node) {
			var class_name = node.getAttribute('class') ? node.getAttribute('class') : node.className;
            if (class_name) {
                return class_name;
            } else {
                return "";
            }
		},

        wm_close_event: function() {
            // Restore scrolling for the main content window when the attach to post interface is closed
            jQuery('html,body').css('overflow', 'auto');
            tinyMCE.activeEditor.selection.select(tinyMCE.activeEditor.dom.select('p')[0]);
            tinyMCE.activeEditor.selection.collapse(0);
        },

		/**
		 * Renders the attach to post interface
		 */
		render_attach_to_post_interface: function(id) {
			// Determine the attach to post url
			var attach_to_post_url = nextgen_gallery_attach_to_post_url;
            if (typeof(id) != 'undefined') {
				attach_to_post_url += "?id=" + this.id;
            }

			var win = window;
			while (win.parent != null && win.parent != win) {
				win = win.parent;
			}

			win = jQuery(win);
			var winWidth    = win.width();
			var winHeight   = win.height();
			var popupWidth  = 1200;
			var popupHeight = 600;
			var minWidth    = 800;
			var minHeight   = 600;
			var maxWidth    = winWidth  - (winWidth  * 0.05);
			var maxHeight   = winHeight - (winHeight * 0.05);

			if (maxWidth    < minWidth)  { maxWidth    = winWidth - 10;  }
			if (maxHeight   < minHeight) { maxHeight   = winHeight - 10; }
			if (popupWidth  > maxWidth)  { popupWidth  = maxWidth;  }
			if (popupHeight > maxHeight) { popupHeight = maxHeight; }

			// Open a window, occupying 90% of the screen real estate
			this.editor.windowManager.open({
				url: attach_to_post_url,
				id: 'ngg_attach_to_post_dialog',
				width: popupWidth,
				height: popupHeight,
				title: "NextGEN Gallery - Attach To Post"
			});

			// Ensure that the window cannot be scrolled - XXX actually allow scrolling in the main window and disable it for the inner-windows/frames/elements as to create a single scrollbar
            jQuery('html,body').css('overflow', 'hidden');
			jQuery('#ngg_attach_to_post_dialog_ifr').css('overflow-y', 'auto');
			jQuery('#ngg_attach_to_post_dialog_ifr').css('overflow-x', 'hidden');
		}
	});

	// Register plugin
	tinymce.PluginManager.add('NextGEN_AttachToPost', tinymce.plugins.NextGEN_AttachToPost);
})(photocrati_ajax.wp_site_url);

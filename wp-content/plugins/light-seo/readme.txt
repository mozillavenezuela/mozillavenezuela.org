=== Light SEO ===
Contributors: aldenml
Tags: post,google,seo,seo plugin,wordpress seo,meta,meta keywords,meta description,meta index,title,posts,plugin,search engine optimization,admin,tool,rewrite,url,urls,content,canonical,page,home,search engines,bing,yahoo
Requires at least: 2.9
Tested up to: 2.9.1
Stable tag: 0.0.15

WordPress SEO plugin. Focused in creating a clean plugin, with emphasis in source code's good practices.

== Description ==

WordPress SEO plugin. Focused in creating a clean plugin, with emphasis in source code's good practices. Automatically optimizes your Wordpress blog for Search Engines (Search Engine Optimization).

Features:

* Automatically optimizes your titles for search engines.
* Generates META tags automatically.
* You can override any title and set any META description and any META keywords you want.
* Avoids the typical duplicate content found on Wordpress blogs.
* Custom field for Google Webmasters tools site verification meta tag.
* Easy for beginners, it works out-of-the-box.
* For advanced users, you can fine-tune everything.
* For programmers, clean and documented code.

For more information see: [Light SEO WordPress plugin](http://www.aldentorres.com/light-seo-wordpress-plugin/ "Light SEO WordPress plugin home")

== Installation ==

1. Download and extract it
2. Copy light-seo folder to the "/wp-content/plugins/" directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure the plugin.
5. Done

== Frequently Asked Questions ==

= Is this a fork of All in One SEO Pack? =

Yes.

== Screenshots ==

1. Admin page at the begining.
2. Admin page at the end.
3. Post or Page options.

== Changelog ==

= 0.0.15 =
* Security fix.

= 0.0.14 =
* Fixed charset in strtolower, strtoupper, capitalize.
* Refactor of get_unique_keywords.

= 0.0.13 =
* Fixed replace_title function.

= 0.0.12 =
* Important use of the function wp_kses to prevent XSS attacks (thanks to Mark Jaquith).
* Fixed wrong esc_attr usage (thanks to Mark Jaquith).

= 0.0.11 =
* Huge security revision.

The following changes are thanks to @andreasnrb and reinforced by Mark Jaquith:
* Line 379: Use esc_attr($description).
* Line 391: Use esc_attr($keywords).
* Line 472: Use esc_url($url).
* Line 698: esc_url($_SERVER['REQUEST_URI']).
* Line 699: esc_url($_SERVER['REQUEST_URI']).
* Line 944: esc_url($_SERVER['REQUEST_URI']).
* Line 945: esc_url($_SERVER['REQUEST_URI']).

* Removed get_url() unused function.
* Removed management_panel() unused function.
* 70+ changes regarding security checks (thanks to Mark Jaquith).
* Removed edit_category unused function.

= 0.0.10 =
* Removed log feature since it's not used properly.
* Removed unused class fields.
* Refactor of replace_title function to use regex.
* More comments for readability.
* Added screenshots.
* Cosmetic change in the copyright notice.

= 0.0.9 =
* Fixed important issue regarding initial installation.

= 0.0.8 =
* Removed old upgrade code.
* Added custom field for Google Webmasters tools site verification meta tag.
* More code cleanup.

= 0.0.7 =
* Fixed very important issue regarding the character \n.

= 0.0.6 =
* Fixed copyright notice.
* Removed unusued global constants.
* Removed deprecated function calls.
* Added comments for readability.
* Minor performance improvement.

= 0.0.5 =
* Removed author link from the admin page.
* Removed Enable/Disable option.

= 0.0.4 =
* Removed unused fields and functions.
* Removed Ultimate Tag Warrior integration.
* Code refactor.

= 0.0.3 =
* Ready for tag versioning.

= 0.0.2 =
* Correct code indentation completed.

= 0.0.1 =
* First version.

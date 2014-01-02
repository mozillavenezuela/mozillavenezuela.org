=== Header Image Slider ===
Contributors: shazdeh
Plugin Name: Header Image Slider
Tags: header, header-image, slider
Requires at least: 3.2
Tested up to: 3.2.1
Stable tag: 0.3

Use WP3.0 built-in Header feature to build a beautiful slider.

== Description ==

You can easily build a slider of your header images. This plugin also adds an option to remove your uploaded header images right from the Header options page.

As of now it only supports Nivo Slider. Please email me if you want your favorite slider script to be embedded in the plugin.


== Installation ==

1. Upload the whole plugin directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Goto Appearance -> Header and select Slider option
5. Enjoy!

== Screenshots ==

1. Slider in TwentyEleven theme
2. Admin options

== Changelog ==

= 0.2 =
* Added the option to select (tick) header images you want to show up
* Now you can specify the width and height of the slider if the theme doesn't support header images.
* The script for auto-insert is now minified

= 0.2 =
* Added option panel for Nivo Slider
* Added auto-insertion of slider, no need to call the boom_header_image() template tag if the theme supports WP3.0 Custom Headers. This is still in beta stage, so please report any bugs and malfunctions.
* The plugin is OOPed and documented.
* bugfix: could not use default images with child themes
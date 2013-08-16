<?php

class C_NextGen_Settings_Installer
{
	private $_global_settings = array();
	private $_local_settings  = array();

	function __construct()
	{
		$this->site_settings = C_NextGen_Global_Settings::get_instance();
		$this->blog_settings = C_NextGen_Settings::get_instance();

		$this->_global_defaults = array(
			'gallerypath' => 'wp-content/blogs.dir/%BLOG_ID%/files/',
			'wpmuCSSfile' => 'nggallery.css',
			'wpmuStyle' => TRUE,
			'datamapper_driver'     => 'custom_table_datamapper',
			'gallerystorage_driver' => 'ngglegacy_gallery_storage',
			'maximum_entity_count'  => 500,
			'router_param_slug'     => 'nggallery'
		);

		$this->_local_settings = array(
			'gallerypath'	 => 'wp-content/gallery/',
			'deleteImg'      => True,              // delete Images
			'swfUpload'      => True,              // activate the batch upload
			'usePermalinks'  => False,             // use permalinks for parameters
			'permalinkSlug'  => 'nggallery',       // the default slug for permalinks
			'graphicLibrary' => 'gd',              // default graphic library
			'imageMagickDir' => '/usr/local/bin/', // default path to ImageMagick
			'useMediaRSS'    => False,             // activate the global Media RSS file
			'usePicLens'     => False,             // activate the PicLens Link for galleries

			// Tags / categories
			'activateTags' => 0,  // append related images
			'appendType'   => 'tags', // look for category or tags
			'maxImages'    => 7,      // number of images toshow

			// Thumbnail Settings
			'thumbwidth'   => 120,  // Thumb Width
			'thumbheight'  => 90,   // Thumb height
			'thumbfix'     => True, // Fix the dimension
			'thumbquality' => 100,  // Thumb Quality

			// Image Settings
			'imgWidth'      => 800,   // Image Width
			'imgHeight'     => 600,   // Image height
			'imgQuality'    => 85,    // Image Quality
			'imgBackup'     => True,  // Create a backup
			'imgAutoResize' => False, // Resize after upload

			// Gallery Settings
			'galImages'         => '20', // Number of images per page
			'galPagedGalleries' => 0,    // Number of galleries per page (in a album)
			'galColumns'        => 0,    // Number of columns for the gallery
			'galShowSlide'      => True, // Show slideshow
			'galTextSlide'      => __('[Show as slideshow]', 'nggallery'), // Text for slideshow
			'galTextGallery'    => __('[Show picture list]', 'nggallery'), // Text for gallery
			'galShowOrder'      => 'gallery',   // Show order
			'galSort'           => 'sortorder', // Sort order
			'galSortDir'        => 'ASC',       // Sort direction
			'galNoPages'        => True,        // use no subpages for gallery
			'galImgBrowser'     => 0,       // Show ImageBrowser => instead effect
			'galHiddenImg'      => 0,       // For paged galleries we can hide image
			'galAjaxNav'        => 0,       // AJAX Navigation for Shutter effect

			// Thumbnail Effect
			'thumbEffect'  => 'fancybox', // select effect
			'thumbCode'    => 'class="ngg-fancybox" rel="%GALLERY_NAME%"',

			// Watermark settings
			'wmPos'    => 'botRight',             // Postion
			'wmXpos'   => 5,                      // X Pos
			'wmYpos'   => 5,                      // Y Pos
			'wmType'   => 0,                 // Type : 'image' / 'text'
			'wmPath'   => '',                     // Path to image
			'wmFont'   => 'arial.ttf',            // Font type
			'wmSize'   => 10,                     // Font Size
			'wmText'   => get_option('blogname'), // Text
			'wmColor'  => '000000',               // Font Color
			'wmOpaque' => '100',                  // Font Opaque

			// Image Rotator settings
			'enableIR'          => 0,
			'slideFx'           => 'fade',
			'irURL'             => '',
			'irXHTMLvalid'      => 0,
			'irAudio'           => '',
			'irWidth'           => 600,
			'irHeight'          => 400,
			'irShuffle'         => True,
			'irLinkfromdisplay' => True,
			'irShownavigation'  => 0,
			'irShowicons'       => 0,
			'irWatermark'       => 0,
			'irOverstretch'     => 'True',
			'irRotatetime'      => 10,
			'irTransition'      => 'random',
			'irKenburns'        => 0,
			'irBackcolor'       => '000000',
			'irFrontcolor'      => 'FFFFFF',
			'irLightcolor'      => 'CC0000',
			'irScreencolor'     => '000000',

			// CSS Style
			'activateCSS'       => 1, // activate the CSS file
			'CSSfile'           => 'nggallery.css',     // set default css filename
		);
	}

	function install_global_settings($reset=FALSE)
	{
		foreach ($this->_global_defaults as $key => $value) {
			if ($reset) $this->site_settings->set($key, NULL);
			$this->site_settings->set_default_value($key, $value);
		}
	}

	function install_local_settings($reset=FALSE)
	{
		if (is_multisite()) {
			$gallerypath = str_replace(
				array('%BLOG_ID%', get_current_blog_id()),
				array('%BLOG_NAME%', get_bloginfo('name')),
				$this->_global_defaults['gallerypath']
			);
			$this->_local_settings['gallerypath'] = $gallerypath;
		}

		foreach ($this->_local_settings as $key => $value) {
			if ($reset) $this->blog_settings->set($key, NULL);
			$this->blog_settings->set_default_value($key, $value);
		}
	}

	function install($reset=FALSE)
	{
		$this->install_global_settings($reset);
		$this->install_local_settings($reset);
	}
}
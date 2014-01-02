<?php

class C_NextGen_Settings_Installer
{
	private $_global_settings = array();
	private $_local_settings  = array();

	function __construct()
	{
		$this->blog_settings = C_NextGen_Settings::get_instance();
		$this->site_settings = C_NextGen_Global_Settings::get_instance();

		$this->_global_settings = array(
			'gallerypath' => 'wp-content/blogs.dir/%BLOG_ID%/files/',
			'wpmuCSSfile' => 'nggallery.css',
			'wpmuStyle'   => FALSE,
			'wpmuRoles'   => FALSE,
			'wpmuImportFolder' => FALSE,
			'wpmuZipUpload'    => FALSE,
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
			'relatedHeading'   => __('<h3>Related Images:</h3>', 'nggallery'), // subheading for related images

			// Thumbnail Settings
			'thumbwidth'   => 120,  // Thumb Width
			'thumbheight'  => 90,   // Thumb height
			'thumbfix'     => True, // Fix the dimension
			'thumbquality' => 100,  // Thumb Quality

			// Image Settings
			'imgWidth'      => 800,   // Image Width
			'imgHeight'     => 600,   // Image height
			'imgQuality'    => 100,   // Image Quality
			'imgBackup'     => True,  // Create a backup
			'imgAutoResize' => False, // Resize after upload

			// Gallery Settings
			'galImages'         => '20', // Number of images per page
			'galPagedGalleries' => 0,    // Number of galleries per page (in a album)
			'galColumns'        => 0,    // Number of columns for the gallery
			'galShowSlide'      => True, // Show slideshow
			'galTextSlide'      => __('[Show slideshow]', 'nggallery'), // Text for slideshow
			'galTextGallery'    => __('[Show thumbnails]', 'nggallery'), // Text for gallery
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
			'thumbEffectContext'  => 'nextgen_images', // select effect

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
		foreach ($this->_global_settings as $key => $value) {
			if ($reset) $this->site_settings->set($key, NULL);
			$this->site_settings->set_default_value($key, $value);
		}
	}

	function install_local_settings($reset=FALSE)
	{
		foreach ($this->_local_settings as $key => $value) {
			if ($reset) $this->blog_settings->set($key, NULL);
			$this->blog_settings->set_default_value($key, $value);
		}

		if (is_multisite())
		{
			// If this is already network activated we just need to use the existing setting
			// Note: attempting to use C_NextGen_Global_Settings here may result in an infinite loop,
			// so get_site_option() is used to check
			if ($options = get_site_option('ngg_options'))
				$gallerypath = $options['gallerypath'];
			else
				$gallerypath = $this->_global_settings['gallerypath'];

			$gallerypath = $this->gallerypath_replace($gallerypath);

			// a gallerypath setting has already been set, so we explicitly set a default AND set a new value
			$this->blog_settings->set_default_value('gallerypath', $gallerypath);
			$this->blog_settings->set('gallerypath', $gallerypath);
		}
	}

	function install($reset=FALSE)
	{
		$this->install_global_settings($reset);
		$this->install_local_settings($reset);
	}

	function get_global_defaults()
	{
		return $this->_global_settings;
	}

	function get_local_defaults()
	{
		return $this->_local_settings;
	}

	function gallerypath_replace($gallerypath)
	{
		$gallerypath = str_replace('%BLOG_NAME%', get_bloginfo('name'),  $gallerypath);
		$gallerypath = str_replace('%BLOG_ID%',   get_current_blog_id(), $gallerypath);
		return $gallerypath;
	}
}

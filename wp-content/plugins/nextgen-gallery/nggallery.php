<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

/**
 * Plugin Name: NextGEN Gallery by Photocrati
 * Description: The most popular gallery plugin for WordPress and one of the most popular plugins of all time with over 7 million downloads.
 * Version: 2.0.40
 * Author: Photocrati Media
 * Plugin URI: http://www.nextgen-gallery.com
 * Author URI: http://www.photocrati.com
 * License: GPLv2
 */

if (!class_exists('E_Clean_Exit')) { class E_Clean_Exit extends RuntimeException {} }
if (!class_exists('E_NggErrorException')) { class E_NggErrorException extends RuntimeException {} }

// This is a temporary function to replace the use of WP's esc_url which strips spaces away from URLs
if (!function_exists('nextgen_esc_url')) {
	function nextgen_esc_url( $url, $protocols = null, $_context = 'display' ) {
		$original_url = $url;

		if ( '' == $url )
			return $url;
		$url = preg_replace('|[^a-z0-9 \\-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);
		$strip = array('%0d', '%0a', '%0D', '%0A');
		$url = _deep_replace($strip, $url);
		$url = str_replace(';//', '://', $url);
		/* If the URL doesn't appear to contain a scheme, we
		 * presume it needs http:// appended (unless a relative
		 * link starting with /, # or ? or a php file).
		 */
		 
		if ( strpos($url, ':') === false && ! in_array( $url[0], array( '/', '#', '?' ) ) &&
			! preg_match('/^[a-z0-9-]+?\.php/i', $url) )
			$url = 'http://' . $url;

		// Replace ampersands and single quotes only when displaying.
		if ( 'display' == $_context ) {
			$url = wp_kses_normalize_entities( $url );
			$url = str_replace( ' ', '%20', $url );
			$url = str_replace( '&amp;', '&#038;', $url );
			$url = str_replace( "'", '&#039;', $url );
		}

		if ( '/' === $url[0] ) {
			$good_protocol_url = $url;
		} else {
			if ( ! is_array( $protocols ) )
				$protocols = wp_allowed_protocols();
			$good_protocol_url = wp_kses_bad_protocol( $url, $protocols );
			if ( strtolower( $good_protocol_url ) != strtolower( $url ) )
				return '';
		}

		return apply_filters('clean_url', $good_protocol_url, $original_url, $_context);
	}
}

/**
 * NextGEN Gallery is built on top of the Photocrati Pope Framework:
 * https://bitbucket.org/photocrati/pope-framework
 *
 * Pope constructs applications by assembling modules.
 *
 * The Bootstrapper. This class performs the following:
 * 1) Loads the Pope Framework
 * 2) Adds a path to the C_Component_Registry instance to search for products
 * 3) Loads all found Products. A Product is a collection of modules with some
 * additional meta data. A Product is responsible for loading any modules it
 * requires.
 * 4) Once all Products (and their associated modules) have been loaded (or in
 * otherwords, "included"), the modules are initialized.
 */
class C_NextGEN_Bootstrap
{
	var $_registry = NULL;
	var $_settings_option_name = 'ngg_options';
	var $_pope_loaded = FALSE;
	static $debug = FALSE;

	static function shutdown($exception=NULL)
	{
		if (is_null($exception)) {
			throw new E_Clean_Exit;
		}
		elseif (!($exception instanceof E_Clean_Exit)) {
			ob_end_clean();
			self::print_exception($exception);
		}

	}

	static function print_exception($exception)
	{
		$klass = get_class($exception);
		echo "<h1>{$klass} thrown</h1>";
		echo "<p>{$exception->getMessage()}</p>";
		if (self::$debug OR (defined('NGG_DEBUG') AND NGG_DEBUG == TRUE)) {
			echo "<h3>Where:</h3>";
			echo "<p>On line <strong>{$exception->getLine()}</strong> of <strong>{$exception->getFile()}</strong></p>";
			echo "<h3>Trace:</h3>";
			echo "<pre>{$exception->getTraceAsString()}</pre>";
			if (method_exists($exception, 'getPrevious')) {
				if (($previous = $exception->getPrevious())) {
					self::print_exception($previous);
				}
			}
		}
	}

	static function get_backtrace($objects=FALSE, $remove_dynamic_calls=TRUE)
	{
		$trace = debug_backtrace($objects);
		if ($remove_dynamic_calls) {
			$skip_methods = array(
				'_exec_cached_method',
				'__call',
				'get_method_property',
				'set_method_property',
				'call_method'
			);
			foreach ($trace as $key => &$value) {
				if (isset($value['class']) && isset($value['function'])) {
					if ($value['class'] == 'ReflectionMethod' && $value['function'] == 'invokeArgs')
						unset($trace[$key]);

					else if ($value['class'] == 'ExtensibleObject' && in_array($value['function'], $skip_methods))
						unset($trace[$key]);
				}
			}
		}

		return $trace;
	}

	function __construct()
	{
		// Boostrap
		set_exception_handler(__CLASS__.'::shutdown');

		$this->_define_constants();
		$this->_load_non_pope();
		$this->_register_hooks();
		$this->_load_pope();

	}

	function _load_non_pope()
	{
		// Load caching component
		include_once('non_pope/class.photocrati_cache.php');
		C_Photocrati_Cache::get_instance();
		C_Photocrati_Cache::get_instance('displayed_galleries');
		C_Photocrati_Cache::get_instance('displayed_gallery_rendering');
		C_Photocrati_Cache::$enabled = PHOTOCRATI_CACHE;

		if (isset($_REQUEST['ngg_flush'])) {
			C_Photocrati_Cache::flush('all');
			die("Flushed all caches");
		}
		elseif (isset($_REQUEST['ngg_force_update'])) {
			C_Photocrati_Cache::$do_not_lookup = TRUE;
			C_Photocrati_Cache::$force_update = TRUE;
			$_SERVER['QUERY_STRING'] = str_replace('ngg_force_update=1', '', $_SERVER['QUERY_STRING']);
		}
		elseif (isset($_REQUEST['ngg_flush_expired'])) {
			C_Photocrati_Cache::flush('all', TRUE);
			die("Flushed all expired items from the cache");
		}

		// Load Settings Manager
		include_once('non_pope/class.photocrati_settings_manager.php');
		include_once('non_pope/class.nextgen_settings.php');
		C_Photocrati_Global_Settings_Manager::$option_name = $this->_settings_option_name;
		C_Photocrati_Settings_Manager::$option_name = $this->_settings_option_name;

		// Load the installer
		include_once('non_pope/class.photocrati_installer.php');

		// Load the resource manager
		include_once('non_pope/class.photocrati_resource_manager.php');
		C_Photocrati_Resource_Manager::init();

		// Load the style manager
		include_once('non_pope/class.nextgen_style_manager.php');

		// Load the shortcode manager
		include_once('non_pope/class.nextgen_shortcode_manager.php');
	}

	/**
	 * Loads the Pope Framework
	 */
	function _load_pope()
	{
		// No need to initialize pope again
		if ($this->_pope_loaded) return;

		// Pope requires a a higher limit
        	$tmp = ini_get('xdebug.max_nesting_level');
	        if ($tmp && (int)$tmp <= 300) @ini_set('xdebug.max_nesting_level', 300);

		// Include pope framework
		require_once(path_join(NEXTGEN_GALLERY_PLUGIN_DIR, implode(
			DIRECTORY_SEPARATOR, array('pope','lib','autoload.php')
		)));

		// Get the component registry
		$this->_registry = C_Component_Registry::get_instance();

		// Add the default Pope factory utility, C_Component_Factory
		$this->_registry->add_utility('I_Component_Factory', 'C_Component_Factory');

		// Load embedded products. Each product is expected to load any
		// modules required
		$this->_registry->add_module_path(NEXTGEN_GALLERY_PRODUCT_DIR, true, false);
		$this->_registry->load_all_products();

	        // Give third-party plugins that opportunity to include their own products
        	// and modules
	        do_action('load_nextgen_gallery_modules', $this->_registry);

		// Initializes all loaded modules
		$this->_registry->initialize_all_modules();

		// Set the document root
		$this->_registry->get_utility('I_Fs')->set_document_root(ABSPATH);

		$this->_pope_loaded = TRUE;
	}


	/**
	 * Registers hooks for the WordPress framework necessary for instantiating
	 * the plugin
	 */
	function _register_hooks()
	{
		// Load text domain
		load_plugin_textdomain(
			NEXTGEN_GALLERY_I8N_DOMAIN,
			false,
			$this->directory_path('lang')
		);

		// Register the activation routines
		add_action('activate_'.NEXTGEN_GALLERY_PLUGIN_BASENAME, array(get_class(), 'activate'));

		// Register the deactivation routines
		add_action('deactivate_'.NEXTGEN_GALLERY_PLUGIN_BASENAME, array(get_class(), 'deactivate'));

		// Register our test suite
		add_filter('simpletest_suites', array(&$this, 'add_testsuite'));

		// Ensure that settings manager is saved as an array
		add_filter('pre_update_option_'.$this->_settings_option_name, array(&$this, 'persist_settings'));
		add_filter('pre_update_site_option_'.$this->_settings_option_name, array(&$this, 'persist_settings'));

		// This plugin uses jQuery extensively
		add_action('init', array(&$this, 'enqueue_jquery'), 1);
		add_action('wp_print_scripts', array(&$this, 'fix_jquery'));
		add_action('admin_print_scripts', array(&$this, 'fix_jquery'));

		// If the selected stylesheet is using an unsafe path, then notify the user
		if (C_NextGen_Style_Manager::get_instance()->is_directory_unsafe()) {
			add_action('all_admin_notices', array(&$this, 'display_stylesheet_notice'));
		}

		// Delete displayed gallery transients periodically
		add_action('ngg_delete_expired_transients', array(&$this, 'delete_expired_transients'));
		if (!wp_next_scheduled('ngg_delete_expired_transients')) {
			wp_schedule_event(time(), 'hourly', 'ngg_delete_expired_transients');
		}

		// Update modules
		add_action('init', array(&$this, 'update'), PHP_INT_MAX-1);

		// Start the plugin!
		add_action('init', array(&$this, 'route'), 11);
	}

	function delete_expired_transients()
	{
		C_Photocrati_Cache::flush('displayed_galleries', TRUE);
	}

	/**
	 * Ensure that C_Photocrati_Settings_Manager gets persisted as an array
	 * @param $settings
	 * @return array
	 */
	function persist_settings($settings)
	{
		if (is_object($settings) && $settings instanceof C_Photocrati_Settings_Manager_Base) {
			$settings = $settings->to_array();
		}
		return $settings;
	}

	/**
	 * Enqueues jQuery
	 */
	function enqueue_jquery()
	{
		wp_enqueue_script('jquery');
	}

	/**
	 * Ensures that the latest version of jQuery bundled with WordPress is used
	 */
	function fix_jquery()
	{
		global $wp_scripts;

		if (isset($wp_scripts->registered['jquery'])) {
			$jquery = $wp_scripts->registered['jquery'];
			if (!isset($jquery->ver) OR version_compare('1.8', $jquery->ver) == 1) {
				ob_start();
				wp_deregister_script('jquery');
				ob_end_clean();
				wp_register_script('jquery', false, array( 'jquery-core', 'jquery-migrate' ), '1.10.0' );
			}
		}
		else wp_register_script( 'jquery', false, array( 'jquery-core', 'jquery-migrate' ), '1.10.0' );

		wp_enqueue_script('jquery');
	}

	/**
	 * Displays a notice to the user that the current stylesheet location is unsafe
	 */
	function display_stylesheet_notice()
	{
		$styles		= C_NextGen_Style_Manager::get_instance();
		$filename	= $styles->get_selected_stylesheet();
		$abspath	= $styles->find_selected_stylesheet_abspath();
		$newpath	= $styles->new_dir;

		echo "<div class='updated error'>
			<h3>WARNING: NextGEN Gallery Stylesheet NOT Upgrade-safe</h3>
			<p>
			<strong>{$filename}</strong> is currently stored in <strong>{$abspath}</strong>, which isn't upgrade-safe. Please move the stylesheet to
			<strong>{$newpath}</strong> to ensure that your customizations persist after updates.
		</p></div>";
	}

	/**
	 * Updates all modules
	 */
	function update()
	{
		$this->_load_pope();

		// Try updating all modules
		C_Photocrati_Installer::update();
	}

	/**
	 * Routes access points using the Pope Router
	 * @return boolean
	 */
	function route()
	{
		$this->_load_pope();
		$router = $this->_registry->get_utility('I_Router');
		if (!$router->serve_request() && $router->has_parameter_segments()) {
			return $router->passthru();
		}
	}

	/**
	 * Run the installer
	 */
	static function activate($network=FALSE)
	{
		C_Photocrati_Installer::update();
	}

	/**
	 * Run the uninstaller
	 */
	static function deactivate()
	{
		C_Photocrati_Installer::uninstall(NEXTGEN_GALLERY_PLUGIN_BASENAME);
	}

	/**
	 * Defines necessary plugins for the plugin to load correctly
	 */
	function _define_constants()
	{
		// NextGEN by Photocrati Constants
		define('NEXTGEN_GALLERY_PLUGIN', basename($this->directory_path()));
		define('NEXTGEN_GALLERY_PLUGIN_BASENAME', plugin_basename(__FILE__));
		define('NEXTGEN_GALLERY_PLUGIN_DIR', $this->directory_path());
		define('NEXTGEN_GALLERY_PLUGIN_URL', $this->path_uri());
		define('NEXTGEN_GALLERY_I8N_DOMAIN', 'nggallery');
		define('NEXTGEN_GALLERY_TESTS_DIR', path_join(NEXTGEN_GALLERY_PLUGIN_DIR, 'tests'));
		define('NEXTGEN_GALLERY_PRODUCT_DIR', path_join(NEXTGEN_GALLERY_PLUGIN_DIR, 'products'));
		define('NEXTGEN_GALLERY_PRODUCT_URL', path_join(NEXTGEN_GALLERY_PLUGIN_URL, 'products'));
		define('NEXTGEN_GALLERY_MODULE_DIR', path_join(NEXTGEN_GALLERY_PRODUCT_DIR, 'photocrati_nextgen/modules'));
		define('NEXTGEN_GALLERY_MODULE_URL', path_join(NEXTGEN_GALLERY_PRODUCT_URL, 'photocrati_nextgen/modules'));
		define('NEXTGEN_GALLERY_PLUGIN_CLASS', path_join(NEXTGEN_GALLERY_PLUGIN_DIR, 'module.NEXTGEN_GALLERY_PLUGIN.php'));
		define('NEXTGEN_GALLERY_PLUGIN_STARTED_AT', microtime());
		define('NEXTGEN_GALLERY_PLUGIN_VERSION', '2.0.40');

		if (!defined('NGG_HIDE_STRICT_ERRORS')) {
			define('NGG_HIDE_STRICT_ERRORS', TRUE);
		}

		// Should we display E_STRICT errors?
		if (NGG_HIDE_STRICT_ERRORS) {
			$level = error_reporting();
			if ($level != 0) error_reporting($level & ~E_STRICT);
		}

		// Should we display NGG debugging information?
		if (!defined('NGG_DEBUG')) {
			define('NGG_DEBUG', FALSE);
		}
		self::$debug = NGG_DEBUG;

		// User definable constants
		if (!defined('NEXTGEN_GALLERY_IMPORT_ROOT')) {
			$path = WP_CONTENT_DIR;
			if (is_multisite()) {
				$uploads = wp_upload_dir();
				$path = $uploads['path'];
			}
			define('NEXTGEN_GALLERY_IMPORT_ROOT', $path);
		}

		// Should the Photocrati cache be enabled
		if (!defined('PHOTOCRATI_CACHE')) {
			define('PHOTOCRATI_CACHE', TRUE);
		}
	}


	/**
	 * Defines the NextGEN Test Suite
	 * @param array $suites
	 * @return array
	 */
	function add_testsuite($suites=array())
	{
		$tests_dir = NEXTGEN_GALLERY_TESTS_DIR;

		if (file_exists($tests_dir)) {

			// Include mock objects
			// TODO: These mock objects should be moved to the appropriate
			// test folder
			require_once(path_join($tests_dir, 'mocks.php'));

			// Define the NextGEN Test Suite
            $suites['nextgen'] = array(
//                path_join($tests_dir, 'mvc'),
                path_join($tests_dir, 'datamapper'),
                path_join($tests_dir, 'nextgen_data'),
                path_join($tests_dir, 'gallery_display')
            );
        }

		return $suites;
	}


	/**
	 * Returns the path to a file within the plugin root folder
	 * @param type $file_name
	 * @return type
	 */
	function file_path($file_name=NULL)
	{
		$path = dirname(__FILE__);

		if ($file_name != null)
		{
			$path .= '/' . $file_name;
		}

		return str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
	}


	/**
	 * Gets the directory path used by the plugin
	 * @return string
	 */
	function directory_path($dir=NULL)
	{
		return $this->file_path($dir);
	}


	/**
	 * Determines the location of the plugin - within a theme or plugin
	 * @return string
	 */
	function get_plugin_location()
	{
		$path = dirname(__FILE__);
		$gallery_dir = strtolower($path);
		$gallery_dir = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $gallery_dir);

		$theme_dir = strtolower(get_stylesheet_directory());
		$theme_dir = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $theme_dir);

		$plugin_dir = strtolower(WP_PLUGIN_DIR);
		$plugin_dir = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $plugin_dir);

		$common_dir_theme = substr($gallery_dir, 0, strlen($theme_dir));
		$common_dir_plugin = substr($gallery_dir, 0, strlen($plugin_dir));

		if ($common_dir_theme == $theme_dir)
		{
			return 'theme';
		}

		if ($common_dir_plugin == $plugin_dir)
		{
			return 'plugin';
		}

		$parent_dir = dirname($path);

		if (file_exists($parent_dir . DIRECTORY_SEPARATOR . 'style.css'))
		{
			return 'theme';
		}

		return 'plugin';
	}


	/**
	 * Gets the URI for a particular path
	 * @param string $path
	 * @param boolean $url_encode
	 * @return string
	 */
	function path_uri($path = null, $url_encode = false)
	{
		$location = $this->get_plugin_location();
		$uri = null;

		$path = str_replace(array('/', '\\'), '/', $path);

		if ($url_encode)
		{
			$path_list = explode('/', $path);

			foreach ($path_list as $index => $path_item)
			{
				$path_list[$index] = urlencode($path_item);
			}

			$path = implode('/', $path_list);
		}

		if ($location == 'theme')
		{
			$theme_uri = get_stylesheet_directory_uri();

			$uri = $theme_uri . 'nextgen-gallery';

			if ($path != null)
			{
				$uri .= '/' . $path;
			}
		}
		else
		{
			// XXX Note, paths could not match but STILL being contained in the theme (i.e. WordPress returns the wrong path for the theme directory, either with wrong formatting or wrong encoding)
			$base = basename(dirname(__FILE__));

			if ($base != 'nextgen-gallery')
			{
				// XXX this is needed when using symlinks, if the user renames the plugin folder everything will break though
				$base = 'nextgen-gallery';
			}

			if ($path != null)
			{
				$base .= '/' . $path;
			}

			$uri = plugins_url($base);
		}

		return $uri;
	}

	/**
	 * Returns the URI for a particular file
	 * @param string $file_name
	 * @return string
	 */
	function file_uri($file_name = NULL)
	{
		return $this->path($file_name);
	}
}

new C_NextGEN_Bootstrap();

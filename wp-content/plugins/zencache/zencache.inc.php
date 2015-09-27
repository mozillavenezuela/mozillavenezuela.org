<?php
/**
 * ZenCache Plugin
 *
 * @package zencache\plugin
 * @since 140422 First documented version.
 * @copyright WebSharks, Inc. <http://www.websharks-inc.com>
 * @license GNU General Public License, version 2
 */
namespace zencache
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	require_once dirname(__FILE__).'/includes/share.php';
	require dirname(__FILE__).'/includes/conflicts-check.php';

	if(!class_exists('\\'.__NAMESPACE__.'\\plugin') && empty($GLOBALS[__NAMESPACE__.'_conflicting_plugin']))
	{
		/**
		 * ZenCache Plugin
		 *
		 * @package zencache\plugin
		 * @since 140422 First documented version.
		 */
		class plugin extends share
		{
			/**
			 * Stub `__FILE__` location.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var string Current `__FILE__` from the stub; NOT from this file.
			 *    Note that ZenCache has a stub loader that checks for PHP v5.3 compat;
			 *    which is why we have this property. This is the stub `__FILE__`.
			 */
			public $file = '';

			/**
			 * An array of all default option values.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var array Default options array; set by constructor.
			 */
			public $default_options = array();

			/**
			 * Configured option values.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var array Options configured by site owner; set by constructor.
			 */
			public $options = array();

			/**
			 * General capability requirement.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var string WordPress capability required to
			 *    administer QC in any environment; i.e. in multisite or otherwise.
			 */
			public $cap = '';

			/**
			 * Network capability requirement.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var string WordPress capability required to
			 *    administer QC in a multisite network.
			 */
			public $network_cap = '';

			/**
			 * Uninstall capability requirement.
			 *
			 * @since 140829 Adding uninstall handler.
			 *
			 * @var string WordPress capability required to
			 *    completely uninstall/delete QC.
			 */
			public $uninstall_cap = '';

			/**
			 * Cache directory.
			 *
			 * @since 140605 Moving to a base directory.
			 *
			 * @var string Cache directory; relative to the configured base directory.
			 */
			public $cache_sub_dir = 'cache';

			/**
			 * Used by methods in this class to help optimize performance.
			 *
			 * @since 140725 Reducing auto-purge overhead.
			 *
			 * @var array An instance-based cache used by methods in this class.
			 */
			public $cache = array();

			/**
			 * Used for temporarily storing the permalink for posts transitioning from
			 *    `publish` or `private` post status to `pending` or `draft` post status.
			 *
			 * @since 150409
			 *
			 * @var array An associative array with the Post ID as the named key containing the post permalink before the post has been transitioned
			 *
			 * @see auto_clear_post_cache()
			 * @see auto_clear_post_cache_transition()
			 */
			protected $pre_post_update_post_permalink = array();

			/**
			 * Used by the plugin's uninstall handler.
			 *
			 * @since 140829 Adding uninstall handler.
			 *
			 * @var boolean If FALSE, run without any hooks.
			 */
			public $enable_hooks = TRUE;

			/**
			 * ZenCache plugin constructor.
			 *
			 * @param boolean $enable_hooks Defaults to a TRUE value.
			 *    If FALSE, setup runs but without adding any hooks.
			 *
			 * @since 140422 First documented version.
			 */
			public function __construct($enable_hooks = TRUE)
			{
				parent::__construct(); // Shared constructor.

				/* -------------------------------------------------------------- */

				$this->enable_hooks = (boolean)$enable_hooks;
				$this->file         = preg_replace('/\.inc\.php$/', '.php', __FILE__);

				/* -------------------------------------------------------------- */

				if(!$this->enable_hooks) // Without hooks?
					return; // Stop here; construct without hooks.

				/* -------------------------------------------------------------- */

				add_action('after_setup_theme', array($this, 'setup'));
				register_activation_hook($this->file, array($this, 'activate'));
				register_deactivation_hook($this->file, array($this, 'deactivate'));
			}

			/**
			 * Setup the ZenCache plugin.
			 *
			 * @since 140422 First documented version.
			 */
			public function setup()
			{
				if(isset($this->cache[__FUNCTION__]))
					return; // Already setup. Once only!
				$this->cache[__FUNCTION__] = -1;

				if($this->enable_hooks) // Hooks enabled?
					$this->do_wp_action('before__'.__METHOD__, get_defined_vars());

				/* -------------------------------------------------------------- */

				load_plugin_textdomain($this->text_domain);

				$this->default_options = array(
					/* Core/systematic plugin options. */

					'version'                          => $this->version,
					'crons_setup'                      => '0', // `0` or timestamp.

					/* Primary switch; enable? */

					'enable'                           => '0', // `0|1`.

					/* Related to debugging. */

					'debugging_enable'                 => '1',
					// `0|1|2` // 2 indicates greater debugging detail.

					/* Related to cache directory. */

					'base_dir'                         => 'cache/zencache', // Relative to `WP_CONTENT_DIR`.
					'cache_max_age'                    => '7 days', // `strtotime()` compatible.

					/* Related to automatic cache clearing. */

					'cache_clear_xml_feeds_enable'         => '1', // `0|1`.

					'cache_clear_xml_sitemaps_enable'      => '1', // `0|1`.
					'cache_clear_xml_sitemap_patterns'     => '/sitemap*.xml',
					// Empty string or line-delimited patterns.

					'cache_clear_home_page_enable'     => '1', // `0|1`.
					'cache_clear_posts_page_enable'    => '1', // `0|1`.

					'cache_clear_custom_post_type_enable'  => '1', // `0|1`.
					'cache_clear_author_page_enable'   => '1', // `0|1`.

					'cache_clear_term_category_enable' => '1', // `0|1`.
					'cache_clear_term_post_tag_enable' => '1', // `0|1`.
					'cache_clear_term_other_enable'    => '0', // `0|1`.

					/* Misc. cache behaviors. */

					'allow_browser_cache'              => '0', // `0|1`.
					'get_requests'                     => '0', // `0|1`.
					'feeds_enable'                     => '0', // `0|1`.
					'cache_404_requests'               => '0', // `0|1`.

                    /* Related to exclusions. */

                    'exclude_uris'                         => '', // Empty string or line-delimited patterns.
                    'exclude_refs'                         => '', // Empty string or line-delimited patterns.
                    'exclude_agents'                       => 'w3c_validator', // Empty string or line-delimited patterns.

                    /* Related to uninstallation routines. */

					'uninstall_on_deletion'            => '0', // `0|1`.

				); // Default options are merged with those defined by the site owner.
				$options               = is_array($options = get_option(__NAMESPACE__.'_options')) ? $options : array();
				if(is_multisite() && is_array($site_options = get_site_option(__NAMESPACE__.'_options')))
					$options = array_merge($options, $site_options); // Multisite network options.

				if(!$options && is_multisite() && is_array($quick_cache_site_options = get_site_option('quick_cache_options')))
				{ // Old Multisite Network options from Quick Cache.
					$options                = $quick_cache_site_options;
					$options['crons_setup'] = $this->default_options['crons_setup'];
				}
				if(!$options && is_array($quick_cache_options = get_option('quick_cache_options')))
				{ // Old options from Quick Cache standard installation.
					$options                = $quick_cache_options;
					$options['crons_setup'] = $this->default_options['crons_setup'];
				}
				$this->default_options = $this->apply_wp_filters(__METHOD__.'__default_options', $this->default_options, get_defined_vars());
				$this->options         = array_merge($this->default_options, $options); // This considers old options also.
				$this->options         = $this->apply_wp_filters(__METHOD__.'__options', $this->options, get_defined_vars());
				$this->options         = array_intersect_key($this->options, $this->default_options);

				$this->options['base_dir'] = trim($this->options['base_dir'], '\\/'." \t\n\r\0\x0B");
				if(!$this->options['base_dir']) // Security enhancement; NEVER allow this to be empty.
					$this->options['base_dir'] = $this->default_options['base_dir'];

				$this->cap           = $this->apply_wp_filters(__METHOD__.'__cap', 'activate_plugins');
				$this->network_cap   = $this->apply_wp_filters(__METHOD__.'__network_cap', 'manage_network_plugins');
				$this->uninstall_cap = $this->apply_wp_filters(__METHOD__.'__uninstall_cap', 'delete_plugins');

				/* -------------------------------------------------------------- */

				if(!$this->enable_hooks) // Without hooks?
					return; // Stop here; setup without hooks.

				/* -------------------------------------------------------------- */

				add_action('init', array($this, 'check_advanced_cache'));
				add_action('init', array($this, 'check_blog_paths'));
				add_action('wp_loaded', array($this, 'actions'));

				add_action('admin_init', array($this, 'check_version'));
				add_action('admin_init', array($this, 'maybe_auto_clear_cache'));

				add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
				add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

				add_action('all_admin_notices', array($this, 'all_admin_notices'));
				add_action('all_admin_notices', array($this, 'all_admin_errors'));

				add_action('network_admin_menu', array($this, 'add_network_menu_pages'));
				add_action('admin_menu', array($this, 'add_menu_pages'));

				add_action('upgrader_process_complete', array($this, 'upgrader_process_complete'), 10, 2);
				add_action('safecss_save_pre', array($this, 'jetpack_custom_css'), 10, 1);

				add_action('switch_theme', array($this, 'auto_clear_cache'));
				add_action('wp_create_nav_menu', array($this, 'auto_clear_cache'));
				add_action('wp_update_nav_menu', array($this, 'auto_clear_cache'));
				add_action('wp_delete_nav_menu', array($this, 'auto_clear_cache'));

				add_action('save_post', array($this, 'auto_clear_post_cache'));
				add_action('delete_post', array($this, 'auto_clear_post_cache'));
				add_action('clean_post_cache', array($this, 'auto_clear_post_cache'));
				add_action('post_updated', array($this, 'auto_clear_author_page_cache'), 10, 3);
				add_action('pre_post_update', array($this, 'auto_clear_post_cache_transition'), 10, 2);

				add_action('added_term_relationship', array($this, 'auto_clear_post_terms_cache'), 10, 1);
				add_action('delete_term_relationships', array($this, 'auto_clear_post_terms_cache'), 10, 1);

				add_action('trackback_post', array($this, 'auto_clear_comment_post_cache'));
				add_action('pingback_post', array($this, 'auto_clear_comment_post_cache'));
				add_action('comment_post', array($this, 'auto_clear_comment_post_cache'));
				add_action('transition_comment_status', array($this, 'auto_clear_comment_transition'), 10, 3);

				add_action('create_term', array($this, 'auto_clear_cache'));
				add_action('edit_terms', array($this, 'auto_clear_cache'));
				add_action('delete_term', array($this, 'auto_clear_cache'));

				add_action('add_link', array($this, 'auto_clear_cache'));
				add_action('edit_link', array($this, 'auto_clear_cache'));
				add_action('delete_link', array($this, 'auto_clear_cache'));

				add_filter('enable_live_network_counts', array($this, 'update_blog_paths'));

				add_filter('plugin_action_links_'.plugin_basename($this->file), array($this, 'add_settings_link'));

				/* -------------------------------------------------------------- */

				add_filter('cron_schedules', array($this, 'extend_cron_schedules'));

				if((integer)$this->options['crons_setup'] < 1382523750)
				{
					wp_clear_scheduled_hook('_cron_'.__NAMESPACE__.'_cleanup');
					wp_schedule_event(time() + 60, 'daily', '_cron_'.__NAMESPACE__.'_cleanup');

					$this->options['crons_setup'] = (string)time();
					update_option(__NAMESPACE__.'_options', $this->options); // Blog-specific.
					if(is_multisite()) update_site_option(__NAMESPACE__.'_options', $this->options);
				}
				add_action('_cron_'.__NAMESPACE__.'_cleanup', array($this, 'purge_cache'));

				/* -------------------------------------------------------------- */

				$this->do_wp_action('after__'.__METHOD__, get_defined_vars());
				$this->do_wp_action(__METHOD__.'_complete', get_defined_vars());
			}

			/**
			 * WordPress database instance.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return \wpdb Reference for IDEs.
			 */
			public function wpdb() // Shortcut for other routines.
			{
				return $GLOBALS['wpdb'];
			}

			/**
			 * Plugin activation hook.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to {@link \register_activation_hook()}
			 */
			public function activate()
			{
				$this->setup(); // Setup routines.

				if(!$this->options['enable'])
					return; // Nothing to do.

				$this->add_wp_cache_to_wp_config();
				$this->add_advanced_cache();
				$this->update_blog_paths();
				$this->auto_clear_cache();
			}

			/**
			 * Check current plugin version that installed in WP.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to `admin_init` hook.
			 */
			public function check_version()
			{
				$current_version = $prev_version = $this->options['version'];
				if(version_compare($current_version, $this->version, '>='))
					return; // Nothing to do; we've already upgraded them.

				$current_version = $this->options['version'] = $this->version;
				update_option(__NAMESPACE__.'_options', $this->options); // Updates version.
				if(is_multisite()) update_site_option(__NAMESPACE__.'_options', $this->options);

				require_once dirname(__FILE__).'/includes/version-specific-upgrade.php';
				new version_specific_upgrade($prev_version);

				if($this->options['enable']) // Recompile.
				{
					$this->add_wp_cache_to_wp_config();
					$this->add_advanced_cache();
					$this->update_blog_paths();
				}
				$this->wipe_cache(); // Always wipe the cache; no exceptions.

				$this->enqueue_notice(sprintf(__('<strong>%1$s:</strong> detected a new version of itself. Recompiling w/ latest version... wiping the cache... all done :-)', $this->text_domain), esc_html($this->name)), '', TRUE);
			}

			/**
			 * Plugin deactivation hook.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to {@link \register_deactivation_hook()}
			 */
			public function deactivate()
			{
				$this->setup(); // Setup routines.

				$this->remove_wp_cache_from_wp_config();
				$this->remove_advanced_cache();
				$this->clear_cache();
			}

			/**
			 * Plugin uninstall hook.
			 *
			 * @since 140829 Adding uninstall handler.
			 *
			 * @attaches-to {@link \register_uninstall_hook()} ~ via {@link uninstall()}
			 */
			public function uninstall()
			{
				$this->setup(); // Setup routines.

				if(!defined('WP_UNINSTALL_PLUGIN'))
					return; // Disallow.

				if(empty($GLOBALS[__NAMESPACE__.'_uninstalling']))
					return; // Not uninstalling.

				if(!class_exists('\\'.__NAMESPACE__.'\\uninstall'))
					return; // Expecting the uninstall class.

				if(!current_user_can($this->uninstall_cap))
					return; // Extra layer of security.

				$this->remove_wp_cache_from_wp_config();
				$this->remove_advanced_cache();
				$this->wipe_cache();

				if(!$this->options['uninstall_on_deletion'])
					return; // Nothing to do here.

				$this->delete_advanced_cache();
				$this->remove_base_dir();

				delete_option(__NAMESPACE__.'_options');
				if(is_multisite()) // Delete network options too.
					delete_site_option(__NAMESPACE__.'_options');

				delete_option(__NAMESPACE__.'_notices');
				delete_option(__NAMESPACE__.'_errors');

				wp_clear_scheduled_hook('_cron_'.__NAMESPACE__.'_cleanup');
			}

			/**
			 * Current request is for a pro version preview?
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean TRUE if the current request is for a pro preview.
			 */
			public function is_pro_preview()
			{
				static $is;
				if(isset($is)) return $is;

				if(!empty($_REQUEST[__NAMESPACE__.'_pro_preview']))
					return ($is = TRUE);

				return ($is = FALSE);
			}

			/**
			 * URL to a ZenCache plugin file.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string $file Optional file path; relative to plugin directory.
			 * @param string $scheme Optional URL scheme; defaults to the current scheme.
			 *
			 * @return string URL to plugin directory; or to the specified `$file` if applicable.
			 */
			public function url($file = '', $scheme = '')
			{
				if(!isset(static::$static[__FUNCTION__]['plugin_dir']))
					static::$static[__FUNCTION__]['plugin_dir'] = rtrim(plugin_dir_url($this->file), '/');
				$plugin_dir =& static::$static[__FUNCTION__]['plugin_dir'];

				$url = $plugin_dir.(string)$file;

				if($scheme) // A specific URL scheme?
					$url = set_url_scheme($url, (string)$scheme);

				return $this->apply_wp_filters(__METHOD__, $url, get_defined_vars());
			}

			/**
			 * Plugin action handler.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to `wp_loaded` hook.
			 */
			public function actions()
			{
				if(!empty($_REQUEST[__NAMESPACE__]))
					require_once dirname(__FILE__).'/includes/actions.php';
			}

			/**
			 * Adds CSS for administrative menu pages.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to `admin_enqueue_scripts` hook.
			 */
			public function enqueue_admin_styles()
			{
				if(empty($_GET['page']) || strpos($_GET['page'], __NAMESPACE__) !== 0)
					return; // Nothing to do; NOT a plugin page in the administrative area.

				$deps = array(); // Plugin dependencies.

				wp_enqueue_style(__NAMESPACE__, $this->url('/client-s/css/menu-pages.min.css'), $deps, $this->version, 'all');
			}

			/**
			 * Adds JS for administrative menu pages.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to `admin_enqueue_scripts` hook.
			 */
			public function enqueue_admin_scripts()
			{
				if(empty($_GET['page']) || strpos($_GET['page'], __NAMESPACE__) !== 0)
					return; // Nothing to do; NOT a plugin page in the administrative area.

				$deps = array('jquery'); // Plugin dependencies.

				wp_enqueue_script(__NAMESPACE__, $this->url('/client-s/js/menu-pages.min.js'), $deps, $this->version, TRUE);
			}

			/**
			 * Creates network admin menu pages.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to `network_admin_menu` hook.
			 */
			public function add_network_menu_pages()
			{
				$icon = file_get_contents(dirname(__FILE__).'/client-s/images/inline-icon.svg');
				$icon = 'data:image/svg+xml;base64,'.base64_encode($this->color_svg_menu_icon($icon));

				add_menu_page($this->name, $this->name, $this->network_cap, __NAMESPACE__, array($this, 'menu_page_options'), $icon);
			}

			/**
			 * Creates admin menu pages.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to `admin_menu` hook.
			 */
			public function add_menu_pages()
			{
				if(is_multisite()) return; // Multisite networks MUST use network admin area.

				$icon = file_get_contents(dirname(__FILE__).'/client-s/images/inline-icon.svg');
				$icon = 'data:image/svg+xml;base64,'.base64_encode($this->color_svg_menu_icon($icon));

				add_menu_page($this->name, $this->name, $this->cap, __NAMESPACE__, array($this, 'menu_page_options'), $icon);
			}

			/**
			 * Adds link(s) to ZenCache row on the WP plugins page.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to `plugin_action_links_'.plugin_basename($this->file)` filter.
			 *
			 * @param array $links An array of the existing links provided by WordPress.
			 *
			 * @return array Revised array of links.
			 */
			public function add_settings_link($links)
			{
				$links[] = '<a href="'.esc_attr(add_query_arg(urlencode_deep(array('page' => __NAMESPACE__)), self_admin_url('/admin.php'))).'">'.__('Settings', $this->text_domain).'</a>';
				$links[] = '<br/><a href="'.esc_attr(add_query_arg(urlencode_deep(array('page' => __NAMESPACE__, __NAMESPACE__.'_pro_preview' => '1')), self_admin_url('/admin.php'))).'">'.__('Preview Pro Features', $this->text_domain).'</a>';
				$links[] = '<a href="'.esc_attr('https://zencache.com/').'" target="_blank">'.__('Upgrade', $this->text_domain).'</a>';

				return $this->apply_wp_filters(__METHOD__, $links, get_defined_vars());
			}

			/**
			 * Fills menu page inline SVG icon color.
			 *
			 * @since 150409 Fixing bug in SVG icons.
			 *
			 * @param string $svg Inline SVG icon markup.
			 *
			 * @return string Inline SVG icon markup.
			 */
			public function color_svg_menu_icon($svg)
			{
				if(!($color = get_user_option('admin_color')))
					$color = 'fresh'; // Default color scheme.

				if(empty($this->wp_admin_icon_colors[$color]))
					return $svg; // Not possible.

				$icon_colors         = $this->wp_admin_icon_colors[$color];
				$use_icon_fill_color = $icon_colors['base']; // Default base.

				$current_pagenow = !empty($GLOBALS['pagenow']) ? $GLOBALS['pagenow'] : '';
				$current_page    = !empty($_REQUEST['page']) ? $_REQUEST['page'] : '';

				if(strpos($current_pagenow, __NAMESPACE__) === 0 || strpos($current_page, __NAMESPACE__) === 0)
					$use_icon_fill_color = $icon_colors['current'];

				return str_replace(' fill="currentColor"', ' fill="'.esc_attr($use_icon_fill_color).'"', $svg);
			}

			/**
			 * WordPress admin icon color schemes.
			 *
			 * @since 150409 Fixing bug in SVG icons.
			 *
			 * @var array WP admin icon colors.
			 *
			 * @note These must be hard-coded, because they don't become available
			 *    in core until `admin_init`; i.e., too late for `admin_menu`.
			 */
			public $wp_admin_icon_colors = array(
				'fresh'     => array('base' => '#999999', 'focus' => '#2EA2CC', 'current' => '#FFFFFF'),
				'light'     => array('base' => '#999999', 'focus' => '#CCCCCC', 'current' => '#CCCCCC'),
				'blue'      => array('base' => '#E5F8FF', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'),
				'midnight'  => array('base' => '#F1F2F3', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'),
				'sunrise'   => array('base' => '#F3F1F1', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'),
				'ectoplasm' => array('base' => '#ECE6F6', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'),
				'ocean'     => array('base' => '#F2FCFF', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'),
				'coffee'    => array('base' => '#F3F2F1', 'focus' => '#FFFFFF', 'current' => '#FFFFFF'),
			);

			/**
			 * Loads the admin menu page options.
			 *
			 * @since 140422 First documented version.
			 *
			 * @see add_network_menu_pages()
			 * @see add_menu_pages()
			 */
			public function menu_page_options()
			{
				require_once dirname(__FILE__).'/includes/menu-pages.php';
				$menu_pages = new menu_pages();
				$menu_pages->options();
			}

			/**
			 * Render admin notices; across all admin dashboard views.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to `all_admin_notices` hook.
			 */
			public function all_admin_notices()
			{
				if(($notices = (is_array($notices = get_option(__NAMESPACE__.'_notices'))) ? $notices : array()))
				{
					$notices = $updated_notices = array_unique($notices); // De-dupe.

					foreach(array_keys($updated_notices) as $_key) if(strpos($_key, 'persistent-') !== 0)
						unset($updated_notices[$_key]); // Leave persistent notices; ditch others.
					unset($_key); // Housekeeping after updating notices.

					update_option(__NAMESPACE__.'_notices', $updated_notices);
				}
				if(current_user_can($this->cap)) foreach($notices as $_key => $_notice)
				{
					$_dismiss = ''; // Initialize empty string; e.g. reset value on each pass.
					if(strpos($_key, 'persistent-') === 0) // A dismissal link is needed in this case?
					{
						$_dismiss_css = 'display:inline-block; float:right; margin:0 0 0 15px; text-decoration:none; font-weight:bold;';
						$_dismiss     = add_query_arg(urlencode_deep(array(__NAMESPACE__ => array('dismiss_notice' => array('key' => $_key)), '_wpnonce' => wp_create_nonce())));
						$_dismiss     = '<a style="'.esc_attr($_dismiss_css).'" href="'.esc_attr($_dismiss).'">'.__('dismiss &times;', $this->text_domain).'</a>';
					}
					if(strpos($_key, 'class-update-nag') !== FALSE)
						$_class = 'update-nag';
					else if(strpos($_key, 'class-error') !== FALSE)
						$_class = 'error';
					else
						$_class = 'updated';
					echo $this->apply_wp_filters(__METHOD__.'__notice', '<div class="'.$_class.'"><p>'.$_notice.$_dismiss.'</p></div>', get_defined_vars());
				}
				unset($_key, $_notice, $_dismiss_css, $_dismiss); // Housekeeping.
			}

			/**
			 * Enqueue an administrative notice.
			 *
			 * @since 140605 Adding enqueue notice/error methods.
			 *
			 * @param string  $notice HTML markup containing the notice itself.
			 *
			 * @param string  $persistent_key Optional. A unique key which identifies a particular type of persistent notice.
			 *    This defaults to an empty string. If this is passed, the notice is persistent; i.e. it continues to be displayed until dismissed by the site owner.
			 *
			 * @param boolean $push_to_top Optional. Defaults to a `FALSE` value.
			 *    If `TRUE`, the notice is pushed to the top of the stack; i.e. displayed above any others.
			 */
			public function enqueue_notice($notice, $persistent_key = '', $push_to_top = FALSE)
			{
				$notice         = (string)$notice;
				$persistent_key = (string)$persistent_key;

				$notices = get_option(__NAMESPACE__.'_notices');
				if(!is_array($notices)) $notices = array();

				if($persistent_key) // A persistent notice?
				{
					if(strpos($persistent_key, 'persistent-') !== 0)
						$persistent_key = 'persistent-'.$persistent_key;

					if($push_to_top) // Push this notice to the top?
						$notices = array($persistent_key => $notice) + $notices;
					else $notices[$persistent_key] = $notice;
				}
				else if($push_to_top) // Push to the top?
					array_unshift($notices, $notice);

				else $notices[] = $notice; // Default behavior.

				update_option(__NAMESPACE__.'_notices', $notices);
			}

			/**
			 * Render admin errors; across all admin dashboard views.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to `all_admin_notices` hook.
			 */
			public function all_admin_errors()
			{
				if(($errors = (is_array($errors = get_option(__NAMESPACE__.'_errors'))) ? $errors : array()))
				{
					$errors = $updated_errors = array_unique($errors); // De-dupe.

					foreach(array_keys($updated_errors) as $_key) if(strpos($_key, 'persistent-') !== 0)
						unset($updated_errors[$_key]); // Leave persistent errors; ditch others.
					unset($_key); // Housekeeping after updating notices.

					update_option(__NAMESPACE__.'_errors', $updated_errors);
				}
				if(current_user_can($this->cap)) foreach($errors as $_key => $_error)
				{
					$_dismiss = ''; // Initialize empty string; e.g. reset value on each pass.
					if(strpos($_key, 'persistent-') === 0) // A dismissal link is needed in this case?
					{
						$_dismiss_css = 'display:inline-block; float:right; margin:0 0 0 15px; text-decoration:none; font-weight:bold;';
						$_dismiss     = add_query_arg(urlencode_deep(array(__NAMESPACE__ => array('dismiss_error' => array('key' => $_key)), '_wpnonce' => wp_create_nonce())));
						$_dismiss     = '<a style="'.esc_attr($_dismiss_css).'" href="'.esc_attr($_dismiss).'">'.__('dismiss &times;', $this->text_domain).'</a>';
					}
					echo $this->apply_wp_filters(__METHOD__.'__error', '<div class="error"><p>'.$_error.$_dismiss.'</p></div>', get_defined_vars());
				}
				unset($_key, $_error, $_dismiss_css, $_dismiss); // Housekeeping.
			}

			/**
			 * Enqueue an administrative error.
			 *
			 * @since 140605 Adding enqueue notice/error methods.
			 *
			 * @param string  $error HTML markup containing the error itself.
			 *
			 * @param string  $persistent_key Optional. A unique key which identifies a particular type of persistent error.
			 *    This defaults to an empty string. If this is passed, the error is persistent; i.e. it continues to be displayed until dismissed by the site owner.
			 *
			 * @param boolean $push_to_top Optional. Defaults to a `FALSE` value.
			 *    If `TRUE`, the error is pushed to the top of the stack; i.e. displayed above any others.
			 */
			public function enqueue_error($error, $persistent_key = '', $push_to_top = FALSE)
			{
				$error          = (string)$error;
				$persistent_key = (string)$persistent_key;

				$errors = get_option(__NAMESPACE__.'_errors');
				if(!is_array($errors)) $errors = array();

				if($persistent_key) // A persistent notice?
				{
					if(strpos($persistent_key, 'persistent-') !== 0)
						$persistent_key = 'persistent-'.$persistent_key;

					if($push_to_top) // Push this notice to the top?
						$errors = array($persistent_key => $error) + $errors;
					else $errors[$persistent_key] = $error;
				}
				else if($push_to_top) // Push to the top?
					array_unshift($errors, $error);

				else $errors[] = $error; // Default behavior.

				update_option(__NAMESPACE__.'_errors', $errors);
			}

			/**
			 * Extends WP-Cron schedules.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to `cron_schedules` filter.
			 *
			 * @param array $schedules An array of the current schedules.
			 *
			 * @return array Revised array of WP-Cron schedules.
			 */
			public function extend_cron_schedules($schedules)
			{
				$schedules['every15m'] = array('interval' => 900, 'display' => __('Every 15 Minutes', $this->text_domain));

				return $this->apply_wp_filters(__METHOD__, $schedules, get_defined_vars());
			}

			/**
			 * Wipes out all cache files in the cache directory.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param boolean $manually Defaults to a `FALSE` value.
			 *    Pass as TRUE if the wipe is done manually by the site owner.
			 *
			 * @param string  $also_wipe_dir Defaults to an empty string.
			 *    By default (i.e. when this is empty) we only wipe {@link $cache_sub_dir} files.
			 *
			 *    WARNING: If this is passed, EVERYTHING inside this directory is deleted recursively;
			 *       in addition to deleting all of the {@link $cache_sub_dir} files.
			 *
			 *    SECURITY: This directory MUST be located inside the `/wp-content/` directory.
			 *    Also, it MUST be a sub-directory of `/wp-content/`, NOT the directory itself.
			 *    Also, it cannot be: `mu-plugins`, `themes`, or `plugins`.
			 *
			 * @return integer Total files wiped by this routine (if any).
			 *
			 * @throws \exception If a wipe failure occurs.
			 */
			public function wipe_cache($manually = FALSE, $also_wipe_dir = '')
			{
				$counter = 0; // Initialize.

				$also_wipe_dir = trim((string)$also_wipe_dir);

				@set_time_limit(1800); // @TODO When disabled, display a warning.

				if(is_dir($cache_dir = $this->cache_dir()))
					$counter += $this->delete_all_files_dirs_in($cache_dir);

				if($also_wipe_dir && is_dir($also_wipe_dir)) // Also wipe another directory?
					// This is called w/ version-specific upgrades. That's the only use at this time.
					$counter += $this->delete_all_files_dirs_in($also_wipe_dir);

				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Clears cache files for the current host|blog.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param boolean $manually Defaults to a `FALSE` value.
			 *    Pass as TRUE if the clearing is done manually by the site owner.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @throws \exception If a clearing failure occurs.
			 */
			public function clear_cache($manually = FALSE)
			{
				$counter = 0; // Initialize.

				if(!is_dir($cache_dir = $this->cache_dir()))
					return $counter; // Nothing to do.

				@set_time_limit(1800); // @TODO When disabled, display a warning.

				$regex = $this->build_host_cache_path_regex('', '.+');
				$counter += $this->clear_files_from_host_cache_dir($regex);

				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Purges expired cache files for the current host|blog.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param boolean $manually Defaults to a `FALSE` value.
			 *    Pass as TRUE if the purging is done manually by the site owner.
			 *
			 * @return integer Total files purged by this routine (if any).
			 *
			 * @attaches-to `'_cron_'.__NAMESPACE__.'_cleanup'` via CRON job.
			 *
			 * @throws \exception If a purge failure occurs.
			 */
			public function purge_cache($manually = FALSE)
			{
				$counter = 0; // Initialize.

				if(!is_dir($cache_dir = $this->cache_dir()))
					return $counter; // Nothing to do.

				@set_time_limit(1800); // @TODO When disabled, display a warning.

				$regex = $this->build_host_cache_path_regex('', '.+');
				$counter += $this->purge_files_from_host_cache_dir($regex);

				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Automatically wipes out all cache files in the cache directory.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return integer Total files wiped by this routine (if any).
			 *
			 * @note Unlike many of the other `auto_` methods, this one is NOT currently attached to any hooks.
			 *    This is called upon whenever QC options are saved and/or restored though.
			 */
			public function auto_wipe_cache()
			{
				$counter = 0; // Initialize.

				if(isset($this->cache[__FUNCTION__]))
					return $counter; // Already did this.
				$this->cache[__FUNCTION__] = -1;

				if(!$this->options['enable'])
					return $counter; // Nothing to do.

				$counter += $this->wipe_cache();

				if($counter && is_admin() /* && $this->options['change_notifications_enable'] */)
				{
					$this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/wipe.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
					                      sprintf(__('<strong>%1$s:</strong> detected significant changes. Found %2$s in the cache; auto-wiping.', $this->text_domain),
					                              esc_html($this->name), esc_html($this->i18n_files($counter))));
				}
				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Automatically clears all cache files for the current blog.
			 *
			 * @attaches-to `switch_theme` hook.
			 *
			 * @attaches-to `wp_create_nav_menu` hook.
			 * @attaches-to `wp_update_nav_menu` hook.
			 * @attaches-to `wp_delete_nav_menu` hook.
			 *
			 * @attaches-to `create_term` hook.
			 * @attaches-to `edit_terms` hook.
			 * @attaches-to `delete_term` hook.
			 *
			 * @attaches-to `add_link` hook.
			 * @attaches-to `edit_link` hook.
			 * @attaches-to `delete_link` hook.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @note This is also called upon during plugin activation.
			 */
			public function auto_clear_cache()
			{
				$counter = 0; // Initialize.

				if(isset($this->cache[__FUNCTION__]))
					return $counter; // Already did this.
				$this->cache[__FUNCTION__] = -1;

				if(!$this->options['enable'])
					return $counter; // Nothing to do.

				$counter += $this->clear_cache();

				if($counter && is_admin() /* && $this->options['change_notifications_enable'] */)
					$this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
					                      sprintf(__('<strong>%1$s:</strong> detected important site changes. Found %2$s in the cache for this site; auto-clearing.', $this->text_domain),
					                              esc_html($this->name), esc_html($this->i18n_files($counter))));

				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Automatically clears cache files for a particular post.
			 *
			 * @attaches-to `save_post` hook.
			 * @attaches-to `delete_post` hook.
			 * @attaches-to `clean_post_cache` hook.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param integer $post_id A WordPress post ID.
			 *
			 * @param bool    $force Defaults to a `FALSE` value.
			 *    Pass as TRUE if clearing should be done for `draft`, `pending`,
			 *    `future`, or `trash` post statuses.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @throws \exception If a clear failure occurs.
			 *
			 * @note This is also called upon by other routines which listen for
			 *    events that are indirectly associated with a post ID.
			 *
			 * @see auto_clear_comment_post_cache()
			 * @see auto_clear_post_cache_transition()
			 */
			public function auto_clear_post_cache($post_id, $force = FALSE)
			{
				$counter = 0; // Initialize.

				if(!($post_id = (integer)$post_id))
					return $counter; // Nothing to do.

				if(isset($this->cache[__FUNCTION__][$post_id][(integer)$force]))
					return $counter; // Already did this.
				$this->cache[__FUNCTION__][$post_id][(integer)$force] = -1;

				if(isset(static::$static['___allow_auto_clear_post_cache']) && static::$static['___allow_auto_clear_post_cache'] === FALSE)
				{
					static::$static['___allow_auto_clear_post_cache'] = TRUE; // Reset state.
					return $counter; // Nothing to do.
				}
				if(!$this->options['enable'])
					return $counter; // Nothing to do.

				if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
					return $counter; // Nothing to do.

				if(!is_dir($cache_dir = $this->cache_dir()))
					return $counter; // Nothing to do.

				if(!empty($this->pre_post_update_post_permalink[$post_id]) && ($permalink = $this->pre_post_update_post_permalink[$post_id]))
					$this->pre_post_update_post_permalink[$post_id] = ''; // Reset; only used for post status transitions
				elseif(!($permalink = get_permalink($post_id)))
					return $counter; // Nothing we can do.

				if(!($post_status = get_post_status($post_id)))
					return $counter; // Nothing to do.

				if($post_status === 'auto-draft')
					return $counter; // Nothing to do.

				if($post_status === 'draft' && !$force)
					return $counter; // Nothing to do.

				if($post_status === 'pending' && !$force)
					return $counter; // Nothing to do.

				if($post_status === 'future' && !$force)
					return $counter; // Nothing to do.

				if($post_status === 'trash' && !$force)
					return $counter; // Nothing to do.

				if(($type = get_post_type($post_id)) && ($type = get_post_type_object($type)) && !empty($type->labels->singular_name))
					$type_singular_name = $type->labels->singular_name; // Singular name for the post type.
				else $type_singular_name = __('Post', $this->text_domain); // Default value.

				$regex = $this->build_host_cache_path_regex($permalink);
				$counter += $this->clear_files_from_host_cache_dir($regex);

				if($counter && is_admin() /* && $this->options['change_notifications_enable'] */)
				{
					$this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
					                      sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for %3$s ID: <code>%4$s</code>; auto-clearing.', $this->text_domain),
					                              esc_html($this->name), esc_html($this->i18n_files($counter)), esc_html($type_singular_name), esc_html($post_id)));
				}
				$counter += $this->auto_clear_xml_feeds_cache('blog');
				$counter += $this->auto_clear_xml_feeds_cache('post-terms', $post_id);
				$counter += $this->auto_clear_xml_feeds_cache('post-authors', $post_id);

				$counter += $this->auto_clear_xml_sitemaps_cache();
				$counter += $this->auto_clear_home_page_cache();
				$counter += $this->auto_clear_posts_page_cache();
				$counter += $this->auto_clear_post_terms_cache($post_id, $force);

				// Also clear a possible custom post type archive view.
				$counter += $this->auto_clear_custom_post_type_archive_cache($post_id);

				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Automatically clears cache files for a particular post when transitioning
			 *    from `publish` or `private` post status to `draft`, `future`, `private`, or `trash`.
			 *
			 * @attaches-to `pre_post_update` hook.
			 *
			 * @since 140605 First documented version.
			 *
			 * @param int   $post_ID Post ID.
			 * @param array $data    Array of unslashed post data.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @throws \exception If a clear failure occurs.
			 *
			 * @note This is also called upon by other routines which listen for
			 *    events that are indirectly associated with a post ID.
			 *
			 * @see auto_clear_post_cache()
			 */
			public function auto_clear_post_cache_transition($post_ID, $data)
			{
				$old_status = (string)get_post_status($post_ID);
				$new_status = (string)$data['post_status'];

				/*
				 * When a post has a status of `pending` or `draft`, the `get_permalink()` function
				 * does not return a friendly permalink and therefore `auto_clear_post_cache()` will
				 * have no way of building a path to the cache file that should be cleared as part of
				 * this post status transition. To get around this, we temporarily store the permalink
				 * in $this->pre_post_update_post_permalink for `auto_clear_post_cache()` to use.
				 *
				 * See also: https://github.com/websharks/zencache/issues/441
				 */
				if($old_status === 'publish' && in_array($data['post_status'], array('pending', 'draft', TRUE)))
					$this->pre_post_update_post_permalink[$post_ID] = get_permalink($post_ID);

				$counter = 0; // Initialize.

				if(isset($this->cache[__FUNCTION__][$new_status][$old_status][$post_ID]))
					return $counter; // Already did this.
				$this->cache[__FUNCTION__][$new_status][$old_status][$post_ID] = -1;

				if(!$this->options['enable'])
					return $counter; // Nothing to do.

				if($old_status !== 'publish' && $old_status !== 'private')
					return $counter; // Nothing to do. We MUST be transitioning FROM one of these statuses.

				if(in_array($new_status, array('draft', 'future', 'pending', 'private', 'trash'), TRUE))
					$counter = $this->auto_clear_post_cache($post_ID, TRUE);

				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Automatically clears cache files related to XML feeds.
			 *
			 * @since 140829 Working to improve compatibility with feeds.
			 *
			 * @param string  $type Type of feed(s) to auto-clear.
			 * @param integer $post_id A Post ID (when applicable).
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @throws \exception If a clear failure occurs.
			 *
			 * @note Unlike many of the other `auto_` methods, this one is NOT currently
			 *    attached to any hooks. However, it is called upon by other routines attached to hooks.
			 */
			public function auto_clear_xml_feeds_cache($type, $post_id = 0)
			{
				$counter = 0; // Initialize.

				if(!($type = (string)$type))
					return $counter; // Nothing we can do.
				$post_id = (integer)$post_id; // Force integer.

				if(isset($this->cache[__FUNCTION__][$type][$post_id]))
					return $counter; // Already did this.
				$this->cache[__FUNCTION__][$type][$post_id] = -1;

				if(!$this->options['enable'])
					return $counter; // Nothing to do.

				if(!$this->options['feeds_enable'])
					return $counter; // Nothing to do.

				if(!$this->options['cache_clear_xml_feeds_enable'])
					return $counter; // Nothing to do.

				if(!is_dir($cache_dir = $this->cache_dir()))
					return $counter; // Nothing to do.

				$variations = $variation_regex_frags = array(); // Initialize.
				require_once dirname(__FILE__).'/includes/utils-feed.php';
				$utils = new utils_feed(); // Feed utilities.

				switch($type) // Handle clearing based on the `$type`.
				{
					case 'blog': // The blog feed; i.e. `/feed/` on most WP installs.

						$variations = array_merge($variations, $utils->feed_link_variations());
						break; // Break switch handler.

					case 'blog-comments': // The blog comments feed; i.e. `/comments/feed/` on most WP installs.

						$variations = array_merge($variations, $utils->feed_link_variations('comments_'));
						break; // Break switch handler.

					case 'post-comments': // Feeds related to comments that a post has.

						if(!$post_id) break; // Nothing to do.
						if(!($post = get_post($post_id))) break;
						$variations = array_merge($variations, $utils->post_comments_feed_link_variations($post));
						break; // Break switch handler.

					case 'post-authors': // Feeds related to authors that a post has.

						if(!$post_id) break; // Nothing to do.
						if(!($post = get_post($post_id))) break;
						$variations = array_merge($variations, $utils->post_author_feed_link_variations($post));
						break; // Break switch handler.

					case 'post-terms': // Feeds related to terms that a post has.

						if(!$post_id) break; // Nothing to do.
						if(!($post = get_post($post_id))) break;
						$variations = array_merge($variations, $utils->post_term_feed_link_variations($post, TRUE));
						break; // Break switch handler.

					case 'custom-post-type': // Feeds related to a custom post type archive view.

						if(!$post_id) break; // Nothing to do.
						if(!($post = get_post($post_id))) break;
						$variations = array_merge($variations, $utils->post_type_archive_link_variations($post));
						break; // Break switch handler.

					// @TODO Possibly consider search-related feeds in the future.
					//    See: <http://codex.wordpress.org/WordPress_Feeds#Categories_and_Tags>
				}
				$variation_regex_frags = $utils->convert_variations_to_host_cache_path_regex_frags($variations);

				if(!$variation_regex_frags // Have regex pattern variations?
				   || !($variation_regex_frags = array_unique($variation_regex_frags))
				) return $counter; // Nothing to do here.

				$in_sets_of = $this->apply_wp_filters(__METHOD__.'__in_sets_of', 10, get_defined_vars());
				for($_i = 0; $_i < count($variation_regex_frags); $_i = $_i + $in_sets_of)
				{
					$_variation_regex_frags = array_slice($variation_regex_frags, $_i, $in_sets_of);
					$_regex                 = '/^\/(?:'.implode('|', $_variation_regex_frags).')\./i';
					$counter += $this->clear_files_from_host_cache_dir($_regex);
				}
				unset($_i, $_variation_regex_frags, $_regex); // Housekeeping.

				if($counter && is_admin() /* && $this->options['change_notifications_enable'] */)
				{
					$this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
					                      sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache, for XML feeds of type: <code>%3$s</code>; auto-clearing.', $this->text_domain),
					                              esc_html($this->name), esc_html($this->i18n_files($counter)), esc_html($type)));
				}
				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Automatically clears cache files related to XML sitemaps.
			 *
			 * @since 140725 Working to improve compatibility with sitemaps.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @throws \exception If a clear failure occurs.
			 *
			 * @note Unlike many of the other `auto_` methods, this one is NOT currently
			 *    attached to any hooks. However, it is called upon by {@link auto_clear_post_cache()}.
			 *
			 * @see auto_clear_post_cache()
			 */
			public function auto_clear_xml_sitemaps_cache()
			{
				$counter = 0; // Initialize.

				if(isset($this->cache[__FUNCTION__]))
					return $counter; // Already did this.
				$this->cache[__FUNCTION__] = -1;

				if(!$this->options['enable'])
					return $counter; // Nothing to do.

				if(!$this->options['cache_clear_xml_sitemaps_enable'])
					return $counter; // Nothing to do.

				if(!$this->options['cache_clear_xml_sitemap_patterns'])
					return $counter; // Nothing to do.

				if(!is_dir($cache_dir = $this->cache_dir()))
					return $counter; // Nothing to do.

				if(!($regex_frags = $this->build_host_cache_path_regex_frags_from_wc_uris($this->options['cache_clear_xml_sitemap_patterns'], '')))
					return $counter; // There are no patterns to look for.

				$regex = $this->build_host_cache_path_regex('', '\/'.$regex_frags.'\.');
				$counter += $this->clear_files_from_host_cache_dir($regex);

				if($counter && is_admin() /* && $this->options['change_notifications_enable'] */)
				{
					$this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
					                      sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for XML sitemaps; auto-clearing.', $this->text_domain),
					                              esc_html($this->name), esc_html($this->i18n_files($counter))));
				}
				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Automatically clears cache files for the home page.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @throws \exception If a clear failure occurs.
			 *
			 * @note Unlike many of the other `auto_` methods, this one is NOT currently
			 *    attached to any hooks. However, it is called upon by {@link auto_clear_post_cache()}.
			 *
			 * @see auto_clear_post_cache()
			 */
			public function auto_clear_home_page_cache()
			{
				$counter = 0; // Initialize.

				if(isset($this->cache[__FUNCTION__]))
					return $counter; // Already did this.
				$this->cache[__FUNCTION__] = -1;

				if(!$this->options['enable'])
					return $counter; // Nothing to do.

				if(!$this->options['cache_clear_home_page_enable'])
					return $counter; // Nothing to do.

				if(!is_dir($cache_dir = $this->cache_dir()))
					return $counter; // Nothing to do.

				$regex = $this->build_host_cache_path_regex(home_url('/'));
				$counter += $this->clear_files_from_host_cache_dir($regex);

				if($counter && is_admin() /* && $this->options['change_notifications_enable'] */)
				{
					$this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
					                      sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for the designated "Home Page"; auto-clearing.', $this->text_domain),
					                              esc_html($this->name), esc_html($this->i18n_files($counter))));
				}
				$counter += $this->auto_clear_xml_feeds_cache('blog');

				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Automatically clears cache files for the posts page.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @throws \exception If a clear failure occurs.
			 *
			 * @note Unlike many of the other `auto_` methods, this one is NOT currently
			 *    attached to any hooks. However, it is called upon by {@link auto_clear_post_cache()}.
			 *
			 * @see auto_clear_post_cache()
			 */
			public function auto_clear_posts_page_cache()
			{
				$counter = 0; // Initialize.

				if(isset($this->cache[__FUNCTION__]))
					return $counter; // Already did this.
				$this->cache[__FUNCTION__] = -1;

				if(!$this->options['enable'])
					return $counter; // Nothing to do.

				if(!$this->options['cache_clear_posts_page_enable'])
					return $counter; // Nothing to do.

				if(!is_dir($cache_dir = $this->cache_dir()))
					return $counter; // Nothing to do.

				$show_on_front  = get_option('show_on_front');
				$page_for_posts = get_option('page_for_posts');

				if(!in_array($show_on_front, array('posts', 'page'), TRUE))
					return $counter; // Nothing we can do in this case.

				if($show_on_front === 'page' && !$page_for_posts)
					return $counter; // Nothing we can do.

				if($show_on_front === 'posts') $posts_page = home_url('/');
				else if($show_on_front === 'page') $posts_page = get_permalink($page_for_posts);
				if(empty($posts_page)) return $counter; // Nothing we can do.

				$regex = $this->build_host_cache_path_regex($posts_page);
				$counter += $this->clear_files_from_host_cache_dir($regex);

				if($counter && is_admin() /* && $this->options['change_notifications_enable'] */)
				{
					$this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
					                      sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for the designated "Posts Page"; auto-clearing.', $this->text_domain),
					                              esc_html($this->name), esc_html($this->i18n_files($counter))));
				}
				$counter += $this->auto_clear_xml_feeds_cache('blog');

				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Automatically clears cache files for a custom post type archive view.
			 *
			 * @since 140918 First documented version.
			 *
			 * @param integer $post_id A WordPress post ID.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @throws \exception If a clear failure occurs.
			 *
			 * @note Unlike many of the other `auto_` methods, this one is NOT currently
			 *    attached to any hooks. However, it is called upon by {@link auto_clear_post_cache()}.
			 *
			 * @see auto_clear_post_cache()
			 */
			public function auto_clear_custom_post_type_archive_cache($post_id)
			{
				$counter = 0; // Initialize.

				if(!($post_id = (integer)$post_id))
					return $counter; // Nothing to do.

				if(isset($this->cache[__FUNCTION__][$post_id]))
					return $counter; // Already did this.
				$this->cache[__FUNCTION__][$post_id] = -1;

				if(!$this->options['enable'])
					return $counter; // Nothing to do.

				if(!$this->options['cache_clear_custom_post_type_enable'])
					return $counter; // Nothing to do.

				if(!is_dir($cache_dir = $this->cache_dir()))
					return $counter; // Nothing to do.

				if(!($post_type = get_post_type($post_id)))
					return $counter; // Nothing to do.

				if(!($all_custom_post_types = get_post_types(array('_builtin' => FALSE))))
					return $counter; // No custom post types.

				if(!in_array($post_type, array_keys($all_custom_post_types), TRUE))
					return $counter; // This is NOT a custom post type.

				if(!($custom_post_type = get_post_type_object($post_type)))
					return $counter; // Unable to retrieve post type.

				if(empty($custom_post_type->labels->name)
				   || !($custom_post_type_name = $custom_post_type->labels->name)
				) $custom_post_type_name = __('Untitled', $this->text_domain);

				if(!($custom_post_type_archive_link = get_post_type_archive_link($post_type)))
					return $counter; // Nothing to do; no link to work from in this case.

				$regex = $this->build_host_cache_path_regex($custom_post_type_archive_link);
				$counter += $this->clear_files_from_host_cache_dir($regex);

				if($counter && is_admin() /* && $this->options['change_notifications_enable'] */)
				{
					$this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
					                      sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for Custom Post Type: <code>%3$s</code>; auto-clearing.', $this->text_domain),
					                              esc_html($this->name), esc_html($this->i18n_files($counter)), esc_html($custom_post_type_name)));
				}
				$counter += $this->auto_clear_xml_feeds_cache('custom-post-type', $post_id);

				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Automatically clears cache files for the author page(s).
			 *
			 * @attaches-to `post_updated` hook.
			 *
			 * @since 140605 First documented version.
			 *
			 * @param integer  $post_id A WordPress post ID.
			 * @param \WP_Post $post_after WP_Post object following the update.
			 * @param \WP_Post $post_before WP_Post object before the update.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @throws \exception If a clear failure occurs.
			 *
			 * @note If the author for the post is being changed, both the previous author
			 *       and current author pages are cleared, if the post status is applicable.
			 */
			public function auto_clear_author_page_cache($post_id, \WP_Post $post_after, \WP_Post $post_before)
			{
				$counter          = 0; // Initialize.
				$enqueued_notices = 0; // Initialize.
				$authors          = array(); // Initialize.
				$authors_to_clear = array(); // Initialize.

				if(!($post_id = (integer)$post_id))
					return $counter; // Nothing to do.

				if(isset($this->cache[__FUNCTION__][$post_id][$post_after->ID][$post_before->ID]))
					return $counter; // Already did this.
				$this->cache[__FUNCTION__][$post_id][$post_after->ID][$post_before->ID] = -1;

				if(!$this->options['enable'])
					return $counter; // Nothing to do.

				if(!$this->options['cache_clear_author_page_enable'])
					return $counter; // Nothing to do.

				if(!is_dir($cache_dir = $this->cache_dir()))
					return $counter; // Nothing to do.
				/*
				 * If we're changing the post author AND
				 *    the previous post status was either 'published' or 'private'
				 * then clear the author page for both authors.
				 *
				 * Else if the old post status was 'published' or 'private' OR
				 *    the new post status is 'published' or 'private'
				 * then clear the author page for the current author.
				 *
				 * Else return the counter; post status does not warrant clearing author page cache.
				 */
				if($post_after->post_author !== $post_before->post_author &&
				   ($post_before->post_status === 'publish' || $post_before->post_status === 'private')
				) // Clear both authors in this case.
				{
					$authors[] = (integer)$post_before->post_author;
					$authors[] = (integer)$post_after->post_author;
				}
				else if(($post_before->post_status === 'publish' || $post_before->post_status === 'private') ||
				        ($post_after->post_status === 'publish' || $post_after->post_status === 'private')
				)
					$authors[] = (integer)$post_after->post_author;

				if(!$authors) // Have no authors to clear?
					return $counter; // Nothing to do.

				foreach($authors as $_author_id) // Get author posts URL and display name.
				{
					$authors_to_clear[$_author_id]['posts_url']    = get_author_posts_url($_author_id);
					$authors_to_clear[$_author_id]['display_name'] = get_the_author_meta('display_name', $_author_id);
				}
				unset($_author_id); // Housekeeping.

				foreach($authors_to_clear as $_author)
				{
					$_author_regex   = $this->build_host_cache_path_regex($_author['posts_url']);
					$_author_counter = $this->clear_files_from_host_cache_dir($_author_regex);
					$counter += $_author_counter; // Add to overall counter.

					if($_author_counter && $enqueued_notices < 100 && is_admin() /* && $this->options['change_notifications_enable'] */)
					{
						$this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
						                      sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for Author Page: <code>%3$s</code>; auto-clearing.', $this->text_domain),
						                              esc_html($this->name), esc_html($this->i18n_files($_author_counter)), esc_html($_author['display_name'])));
						$enqueued_notices++; // Increment enqueued notices counter.
					}
				}
				unset($_author, $_author_regex, $_author_counter); // Housekeeping.

				$counter += $this->auto_clear_xml_feeds_cache('blog');
				$counter += $this->auto_clear_xml_feeds_cache('post-authors', $post_id);

				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Automatically clears cache files for terms associated with a post.
			 *
			 * @attaches-to `added_term_relationship` hook.
			 * @attaches-to `delete_term_relationships` hook.
			 *
			 * @since 140605 First documented version.
			 *
			 * @param integer $post_id A WordPress post ID.
			 *
			 * @param bool    $force Defaults to a `FALSE` value.
			 *    Pass as TRUE if clearing should be done for `draft`, `pending`,
			 *    or `future` post statuses.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @throws \exception If a clear failure occurs.
			 *
			 * @note In addition to the hooks this is attached to, it is also
			 *    called upon by {@link auto_clear_post_cache()}.
			 *
			 * @see auto_clear_post_cache()
			 */
			public function auto_clear_post_terms_cache($post_id, $force = FALSE)
			{
				$counter          = 0; // Initialize.
				$enqueued_notices = 0; // Initialize.

				if(!($post_id = (integer)$post_id))
					return $counter; // Nothing to do.

				if(isset($this->cache[__FUNCTION__][$post_id][(integer)$force]))
					return $counter; // Already did this.
				$this->cache[__FUNCTION__][$post_id][(integer)$force] = -1;

				if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
					return $counter; // Nothing to do.

				if(!$this->options['enable'])
					return $counter; // Nothing to do.

				if(!$this->options['cache_clear_term_category_enable'] &&
				   !$this->options['cache_clear_term_post_tag_enable'] &&
				   !$this->options['cache_clear_term_other_enable']
				) return $counter; // Nothing to do.

				if(!is_dir($cache_dir = $this->cache_dir()))
					return $counter; // Nothing to do.

				$post_status = get_post_status($post_id); // Cache this.

				if($post_status === 'draft' && isset($GLOBALS['pagenow'], $_POST['publish'])
				   && is_admin() && $GLOBALS['pagenow'] === 'post.php' && current_user_can('publish_posts')
				   && strpos(wp_get_referer(), '/post-new.php') !== FALSE
				) $post_status = 'publish'; // A new post being published now.

				if($post_status === 'auto-draft')
					return $counter; // Nothing to do.

				if($post_status === 'draft' && !$force)
					return $counter; // Nothing to do.

				if($post_status === 'pending' && !$force)
					return $counter; // Nothing to do.

				if($post_status === 'future' && !$force)
					return $counter; // Nothing to do.
				/*
				 * Build an array of available taxonomies for this post (as taxonomy objects).
				 */
				$taxonomies = get_object_taxonomies(get_post($post_id), 'objects');

				if(!is_array($taxonomies)) // No taxonomies?
					return $counter; // Nothing to do.
				/*
				 * Build an array of terms associated with this post for each taxonomy.
				 * Also save taxonomy label information for Dashboard messaging later.
				 */
				$terms           = array();
				$taxonomy_labels = array();

				foreach($taxonomies as $_taxonomy)
				{
					if( // Check if this is a taxonomy/term that we should clear.
						($_taxonomy->name === 'category' && !$this->options['cache_clear_term_category_enable'])
						|| ($_taxonomy->name === 'post_tag' && !$this->options['cache_clear_term_post_tag_enable'])
						|| ($_taxonomy->name !== 'category' && $_taxonomy->name !== 'post_tag' && !$this->options['cache_clear_term_other_enable'])
					) continue; // Continue; nothing to do for this taxonomy.

					if(is_array($_terms = wp_get_post_terms($post_id, $_taxonomy->name)))
					{
						$terms = array_merge($terms, $_terms);

						// Improve Dashboard messaging by getting the Taxonomy label (e.g., "Tag" instead of "post_tag")
						// If we don't have a Singular Name for this taxonomy, use the taxonomy name itself
						if(empty($_taxonomy->labels->singular_name) || $_taxonomy->labels->singular_name === '')
							$taxonomy_labels[$_taxonomy->name] = $_taxonomy->name;
						else
							$taxonomy_labels[$_taxonomy->name] = $_taxonomy->labels->singular_name;
					}
				}
				unset($_taxonomy, $_terms);

				if(empty($terms)) // No taxonomy terms?
					return $counter; // Nothing to do.
				/*
				 * Build an array of terms with term names,
				 * permalinks, and associated taxonomy labels.
				 */
				$terms_to_clear = array();
				$_i             = 0;

				foreach($terms as $_term)
				{
					if(($_link = get_term_link($_term)))
					{
						$terms_to_clear[$_i]['permalink'] = $_link; // E.g., "http://jason.websharks-inc.net/category/uncategorized/"
						$terms_to_clear[$_i]['term_name'] = $_term->name; // E.g., "Uncategorized"
						if(!empty($taxonomy_labels[$_term->taxonomy])) // E.g., "Tag" or "Category"
							$terms_to_clear[$_i]['taxonomy_label'] = $taxonomy_labels[$_term->taxonomy];
						else
							$terms_to_clear[$_i]['taxonomy_label'] = $_term->taxonomy; // e.g., "post_tag" or "category"
					}
					$_i++; // Array index counter.
				}
				unset($_term, $_link, $_i);

				if(empty($terms_to_clear))
					return $counter; // Nothing to do.

				foreach($terms_to_clear as $_term)
				{
					$_term_regex   = $this->build_host_cache_path_regex($_term['permalink']);
					$_term_counter = $this->clear_files_from_host_cache_dir($_term_regex);
					$counter += $_term_counter; // Add to overall counter.

					if($_term_counter && $enqueued_notices < 100 && is_admin() /* && $this->options['change_notifications_enable'] */)
					{
						$this->enqueue_notice('<img src="'.esc_attr($this->url('/client-s/images/clear.png')).'" style="float:left; margin:0 10px 0 0; border:0;" />'.
						                      sprintf(__('<strong>%1$s:</strong> detected changes. Found %2$s in the cache for %3$s: <code>%4$s</code>; auto-clearing.', $this->text_domain),
						                              esc_html($this->name), esc_html($this->i18n_files($_term_counter)), esc_html($_term['taxonomy_label']), esc_html($_term['term_name'])));
						$enqueued_notices++; // Increment enqueued notices counter.
					}
				}
				unset($_term, $_term_regex, $_term_counter); // Housekeeping.

				$counter += $this->auto_clear_xml_feeds_cache('post-terms', $post_id);

				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Automatically clears cache files for a post associated with a particular comment.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to `trackback_post` hook.
			 * @attaches-to `pingback_post` hook.
			 * @attaches-to `comment_post` hook.
			 *
			 * @param integer $comment_id A WordPress comment ID.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @see auto_clear_post_cache()
			 */
			public function auto_clear_comment_post_cache($comment_id)
			{
				$counter = 0; // Initialize.

				if(!($comment_id = (integer)$comment_id))
					return $counter; // Nothing to do.

				if(isset($this->cache[__FUNCTION__][$comment_id]))
					return $counter; // Already did this.
				$this->cache[__FUNCTION__][$comment_id] = -1;

				if(!$this->options['enable'])
					return $counter; // Nothing to do.

				if(!is_object($comment = get_comment($comment_id)))
					return $counter; // Nothing we can do.

				if(empty($comment->comment_post_ID))
					return $counter; // Nothing we can do.

				if($comment->comment_approved === 'spam' || $comment->comment_approved === '0')
					// Don't allow next `auto_clear_post_cache()` call to clear post cache.
					// Also, don't allow spam to clear cache.
				{
					static::$static['___allow_auto_clear_post_cache'] = FALSE;
					return $counter; // Nothing to do here.
				}
				$counter += $this->auto_clear_xml_feeds_cache('blog-comments');
				$counter += $this->auto_clear_xml_feeds_cache('post-comments', $comment->comment_post_ID);
				$counter += $this->auto_clear_post_cache($comment->comment_post_ID);

				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Automatically clears cache files for a post associated with a particular comment.
			 *
			 * @since 140711 First documented version.
			 *
			 * @attaches-to `transition_comment_status` hook.
			 *
			 * @param string   $new_status New comment status.
			 * @param string   $old_status Old comment status.
			 * @param \WP_Post $comment Comment object.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @throws \exception If a clear failure occurs.
			 *
			 * @note This is also called upon by other routines which listen for
			 *    events that are indirectly associated with a comment ID.
			 *
			 * @see auto_clear_comment_post_cache()
			 */
			public function auto_clear_comment_transition($new_status, $old_status, $comment)
			{
				$counter = 0; // Initialize.

				if(!$this->options['enable'])
					return $counter; // Nothing to do.

				if(!is_object($comment))
					return $counter; // Nothing we can do.

				if(empty($comment->comment_post_ID))
					return $counter; // Nothing we can do.

				if(!($old_status === 'approved' || ($old_status === 'unapproved' && $new_status === 'approved')))
					// If excluded here, don't allow next `auto_clear_post_cache()` call to clear post cache.
				{
					static::$static['___allow_auto_clear_post_cache'] = FALSE;
					return $counter; // Nothing to do here.
				}
				$counter += $this->auto_clear_xml_feeds_cache('blog-comments');
				$counter += $this->auto_clear_xml_feeds_cache('post-comments', $comment->comment_post_ID);
				$counter += $this->auto_clear_post_cache($comment->comment_post_ID);

				return $this->apply_wp_filters(__METHOD__, $counter, get_defined_vars());
			}

			/**
			 * Automatically clears all cache files for current blog under various conditions;
			 *    used to check for conditions that don't have a hook that we can attach to.
			 *
			 * @since 140922 First documented version.
			 *
			 * @attaches-to `admin_init` hook.
			 *
			 * @see auto_clear_cache()
			 */
			public function maybe_auto_clear_cache()
			{
				$_pagenow = $GLOBALS['pagenow'];
				if(isset($this->cache[__FUNCTION__][$_pagenow]))
					return; // Already did this.
				$this->cache[__FUNCTION__][$_pagenow] = -1;

				// If Dashboard → Settings → General options are updated
				if($GLOBALS['pagenow'] === 'options-general.php' && !empty($_REQUEST['settings-updated']))
					$this->auto_clear_cache();

				// If Dashboard → Settings → Reading options are updated
				if($GLOBALS['pagenow'] === 'options-reading.php' && !empty($_REQUEST['settings-updated']))
					$this->auto_clear_cache();

				// If Dashboard → Settings → Discussion options are updated
				if($GLOBALS['pagenow'] === 'options-discussion.php' && !empty($_REQUEST['settings-updated']))
					$this->auto_clear_cache();

				// If Dashboard → Settings → Permalink options are updated
				if($GLOBALS['pagenow'] === 'options-permalink.php' && !empty($_REQUEST['settings-updated']))
					$this->auto_clear_cache();
			}

			/**
			 * Automatically clears all cache files for current blog when JetPack Custom CSS is saved.
			 *
			 * @since 140919 First documented version.
			 *
			 * @attaches-to `safecss_save_pre` hook.
			 *
			 * @param array $args Args passed in by hook.
			 *
			 * @see auto_clear_cache()
			 */
			public function jetpack_custom_css($args)
			{
				if(class_exists('\\Jetpack') && empty($args['is_preview']))
					$this->auto_clear_cache();
			}

			/**
			 * Automatically clears all cache files for current blog when WordPress core, or an active component, is upgraded.
			 *
			 * @since 141001 Clearing the cache on WP upgrades.
			 *
			 * @attaches-to `upgrader_process_complete` hook.
			 *
			 * @param \WP_Upgrader $upgrader_instance An instance of \WP_Upgrader.
			 *    Or, any class that extends \WP_Upgrader.
			 *
			 * @param array        $data Array of bulk item update data.
			 *
			 *    This array may include one or more of the following keys:
			 *
			 *       - `string` `$action` Type of action. Default 'update'.
			 *       - `string` `$type` Type of update process; e.g. 'plugin', 'theme', 'core'.
			 *       - `boolean` `$bulk` Whether the update process is a bulk update. Default true.
			 *       - `array` `$packages` Array of plugin, theme, or core packages to update.
			 *
			 * @see auto_clear_cache()
			 */
			public function upgrader_process_complete(\WP_Upgrader $upgrader_instance, array $data)
			{
				switch(!empty($data['type']) ? $data['type'] : '')
				{
					case 'plugin': // Plugin upgrade.

						/** @var $skin \Plugin_Upgrader_Skin * */
						$skin                    = $upgrader_instance->skin;
						$multi_plugin_update     = $single_plugin_update = FALSE;
						$upgrading_active_plugin = FALSE; // Initialize.

						if(!empty($data['bulk']) && !empty($data['plugins']) && is_array($data['plugins']))
							$multi_plugin_update = TRUE;

						else if(!empty($data['plugin']) && is_string($data['plugin']))
							$single_plugin_update = TRUE;

						if($multi_plugin_update)
						{
							foreach($data['plugins'] as $_plugin)
								if($_plugin && is_string($_plugin) && is_plugin_active($_plugin))
								{
									$upgrading_active_plugin = TRUE;
									break; // Got what we need here.
								}
							unset($_plugin); // Housekeeping.
						}
						else if($single_plugin_update && $skin->plugin_active == TRUE)
							$upgrading_active_plugin = TRUE;

						if($upgrading_active_plugin)
							$this->auto_clear_cache(); // Yes, clear the cache.

						break; // Break switch.

					case 'theme': // Theme upgrade.

						$current_active_theme          = wp_get_theme();
						$current_active_theme_parent   = $current_active_theme->parent();
						$multi_theme_update            = $single_theme_update = FALSE;
						$upgrading_active_parent_theme = $upgrading_active_theme = FALSE;

						if(!empty($data['bulk']) && !empty($data['themes']) && is_array($data['themes']))
							$multi_theme_update = TRUE;

						else if(!empty($data['theme']) && is_string($data['theme']))
							$single_theme_update = TRUE;

						if($multi_theme_update)
						{
							foreach($data['themes'] as $_theme)
							{
								if(!$_theme || !is_string($_theme) || !($_theme_obj = wp_get_theme($_theme)))
									continue; // Unable to acquire theme object instance.

								if($current_active_theme_parent && $current_active_theme_parent->get_stylesheet() === $_theme_obj->get_stylesheet())
								{
									$upgrading_active_parent_theme = TRUE;
									break; // Got what we needed here.
								}
								else if($current_active_theme->get_stylesheet() === $_theme_obj->get_stylesheet())
								{
									$upgrading_active_theme = TRUE;
									break; // Got what we needed here.
								}
							}
							unset($_theme, $_theme_obj); // Housekeeping.
						}
						else if($single_theme_update && ($_theme_obj = wp_get_theme($data['theme'])))
						{
							if($current_active_theme_parent && $current_active_theme_parent->get_stylesheet() === $_theme_obj->get_stylesheet())
								$upgrading_active_parent_theme = TRUE;

							else if($current_active_theme->get_stylesheet() === $_theme_obj->get_stylesheet())
								$upgrading_active_theme = TRUE;
						}
						unset($_theme_obj); // Housekeeping.

						if($upgrading_active_theme || $upgrading_active_parent_theme)
							$this->auto_clear_cache(); // Yes, clear the cache.

						break; // Break switch.

					case 'core': // Core upgrade.
					default: // Or any other sort of upgrade.

						$this->auto_clear_cache(); // Yes, clear the cache.

						break; // Break switch.
				}
			}

			/**
			 * This constructs an absolute server directory path (no trailing slashes);
			 *    which is always nested into {@link \WP_CONTENT_DIR} and the configured `base_dir` option value.
			 *
			 * @since 140605 Moving to a base directory structure.
			 *
			 * @param string $rel_dir_file A sub-directory or file; relative location please.
			 *
			 * @return string The full absolute server path to `$rel_dir_file`.
			 *
			 * @throws \exception If `base_dir` is empty when this method is called upon;
			 *    i.e. if you attempt to call upon this method before {@link setup()} runs.
			 */
			public function wp_content_base_dir_to($rel_dir_file)
			{
				$rel_dir_file = trim((string)$rel_dir_file, '\\/'." \t\n\r\0\x0B");

				if(empty($this->options) || !is_array($this->options) || empty($this->options['base_dir']))
					throw new \exception(__('Doing it wrong! Missing `base_dir` option value. MUST call this method after `setup()`.', $this->text_domain));

				$wp_content_base_dir_to = WP_CONTENT_DIR.'/'.$this->options['base_dir'];

				if(isset($rel_dir_file[0])) // Do we have this also?
					$wp_content_base_dir_to .= '/'.$rel_dir_file;

				return $this->apply_wp_filters(__METHOD__, $wp_content_base_dir_to, get_defined_vars());
			}

			/**
			 * This constructs a relative/base directory path (no leading/trailing slashes).
			 *    Always relative to {@link \WP_CONTENT_DIR}. Depends on the configured `base_dir` option value.
			 *
			 * @since 140605 Moving to a base directory structure.
			 *
			 * @param string $rel_dir_file A sub-directory or file; relative location please.
			 *
			 * @return string The relative/base directory path to `$rel_dir_file`.
			 *
			 * @throws \exception If `base_dir` is empty when this method is called upon;
			 *    i.e. if you attempt to call upon this method before {@link setup()} runs.
			 */
			public function base_path_to($rel_dir_file)
			{
				$rel_dir_file = trim((string)$rel_dir_file, '\\/'." \t\n\r\0\x0B");

				if(empty($this->options) || !is_array($this->options) || empty($this->options['base_dir']))
					throw new \exception(__('Doing it wrong! Missing `base_dir` option value. MUST call this method after `setup()`.', $this->text_domain));

				$base_path_to = $this->options['base_dir'];

				if(isset($rel_dir_file[0])) // Do we have this also?
					$base_path_to .= '/'.$rel_dir_file;

				return $this->apply_wp_filters(__METHOD__, $base_path_to, get_defined_vars());
			}

			/**
			 * Adds `define('WP_CACHE', TRUE);` to the `/wp-config.php` file.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return string The new contents of the updated `/wp-config.php` file;
			 *    else an empty string if unable to add the `WP_CACHE` constant.
			 */
			public function add_wp_cache_to_wp_config()
			{
				if(!$this->options['enable'])
					return ''; // Nothing to do.

				if(!($wp_config_file = $this->find_wp_config_file()))
					return ''; // Unable to find `/wp-config.php`.

				if(!is_readable($wp_config_file)) return ''; // Not possible.
				if(!($wp_config_file_contents = file_get_contents($wp_config_file)))
					return ''; // Failure; could not read file.

				if(preg_match('/define\s*\(\s*([\'"])WP_CACHE\\1\s*,\s*(?:\-?[1-9][0-9\.]*|TRUE|([\'"])(?:[^0\'"]|[^\'"]{2,})\\2)\s*\)\s*;/i', $wp_config_file_contents))
					return $wp_config_file_contents; // It's already in there; no need to modify this file.

				if(!($wp_config_file_contents = $this->remove_wp_cache_from_wp_config()))
					return ''; // Unable to remove previous value.

				if(!($wp_config_file_contents = preg_replace('/^\s*(\<\?php|\<\?)\s+/i', '${1}'."\n"."define('WP_CACHE', TRUE);"."\n", $wp_config_file_contents, 1)))
					return ''; // Failure; something went terribly wrong here.

				if(strpos($wp_config_file_contents, "define('WP_CACHE', TRUE);") === FALSE)
					return ''; // Failure; unable to add; unexpected PHP code.

				if(defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS)
					return ''; // We may NOT edit any files.

				if(!is_writable($wp_config_file)) return ''; // Not possible.
				if(!file_put_contents($wp_config_file, $wp_config_file_contents))
					return ''; // Failure; could not write changes.

				return $this->apply_wp_filters(__METHOD__, $wp_config_file_contents, get_defined_vars());
			}

			/**
			 * Removes `define('WP_CACHE', TRUE);` from the `/wp-config.php` file.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return string The new contents of the updated `/wp-config.php` file;
			 *    else an empty string if unable to remove the `WP_CACHE` constant.
			 */
			public function remove_wp_cache_from_wp_config()
			{
				if(!($wp_config_file = $this->find_wp_config_file()))
					return ''; // Unable to find `/wp-config.php`.

				if(!is_readable($wp_config_file)) return ''; // Not possible.
				if(!($wp_config_file_contents = file_get_contents($wp_config_file)))
					return ''; // Failure; could not read file.

				if(!preg_match('/([\'"])WP_CACHE\\1/i', $wp_config_file_contents))
					return $wp_config_file_contents; // Already gone.

				if(preg_match('/define\s*\(\s*([\'"])WP_CACHE\\1\s*,\s*(?:0|FALSE|NULL|([\'"])0?\\2)\s*\)\s*;/i', $wp_config_file_contents))
					return $wp_config_file_contents; // It's already disabled; no need to modify this file.

				if(!($wp_config_file_contents = preg_replace('/define\s*\(\s*([\'"])WP_CACHE\\1\s*,\s*(?:\-?[0-9\.]+|TRUE|FALSE|NULL|([\'"])[^\'"]*\\2)\s*\)\s*;/i', '', $wp_config_file_contents)))
					return ''; // Failure; something went terribly wrong here.

				if(preg_match('/([\'"])WP_CACHE\\1/i', $wp_config_file_contents))
					return ''; // Failure; perhaps the `/wp-config.php` file contains syntax we cannot remove safely.

				if(defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS)
					return ''; // We may NOT edit any files.

				if(!is_writable($wp_config_file)) return ''; // Not possible.
				if(!file_put_contents($wp_config_file, $wp_config_file_contents))
					return ''; // Failure; could not write changes.

				return $this->apply_wp_filters(__METHOD__, $wp_config_file_contents, get_defined_vars());
			}

			/**
			 * Checks to make sure the `zc-advanced-cache` file still exists;
			 *    and if it doesn't, the `advanced-cache.php` is regenerated automatically.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to `init` hook.
			 *
			 * @note This runs so that remote deployments which completely wipe out an
			 *    existing set of website files (like the AWS Elastic Beanstalk does) will NOT cause ZenCache
			 *    to stop functioning due to the lack of an `advanced-cache.php` file, which is generated by ZenCache.
			 *
			 *    For instance, if you have a Git repo with all of your site files; when you push those files
			 *    to your website to deploy them, you most likely do NOT have the `advanced-cache.php` file.
			 *    ZenCache creates this file on its own. Thus, if it's missing (and QC is active)
			 *    we simply regenerate the file automatically to keep ZenCache running.
			 */
			public function check_advanced_cache()
			{
				if(!$this->options['enable'])
					return; // Nothing to do.

				if(!empty($_REQUEST[__NAMESPACE__]))
					return; // Skip on plugin actions.

				$cache_dir = $this->cache_dir(); // Current cache directory.
				$advanced_cache_file = WP_CONTENT_DIR.'/advanced-cache.php';

				// Fixes zero-byte advanced-cache.php bug related to migrating from Quick Cache
				// See https://github.com/websharks/zencache/issues/432
				// Also fixes a missing define('WP_CACHE', TRUE) bug related to migrating from Quick Cache
				// See https://github.com/websharks/zencache/issues/450
				if(!is_file($cache_dir.'/zc-advanced-cache')
				   || !is_file($advanced_cache_file) || filesize($advanced_cache_file) === 0
				)
				{
					$this->add_advanced_cache();
					$this->add_wp_cache_to_wp_config();
				}
			}

			/**
			 * Creates and adds the `advanced-cache.php` file.
			 *
			 * @since 140422 First documented version.
			 *
			 * @note Many of the ZenCache option values become PHP Constants in the `advanced-cache.php` file.
			 *    We take an option key (e.g. `version_salt`) and prefix it with `zencache_`.
			 *    Then we convert it to uppercase (e.g. `ZENCACHE_VERSION_SALT`) and wrap
			 *    it with double percent signs to form a replacement codes.
			 *    ex: `%%ZENCACHE_VERSION_SALT%%`
			 *
			 * @note There are a few special options considered by this routine which actually
			 *    get converted to regex patterns before they become replacement codes.
			 *
			 * @note In the case of a version salt, a PHP syntax is performed also.
			 *
			 * @return boolean|null `TRUE` on success. `FALSE` or `NULL` on failure.
			 *    A special `NULL` return value indicates success with a single failure
			 *    that is specifically related to the `zc-advanced-cache` file.
			 */
			public function add_advanced_cache()
			{
				if(!$this->remove_advanced_cache())
					return FALSE; // Still exists.

				$cache_dir               = $this->cache_dir();
				$advanced_cache_file     = WP_CONTENT_DIR.'/advanced-cache.php';
				$advanced_cache_template = dirname(__FILE__).'/includes/advanced-cache.tpl.php';

				if(is_file($advanced_cache_file) && !is_writable($advanced_cache_file))
					return FALSE; // Not possible to create.

				if(!is_file($advanced_cache_file) && !is_writable(dirname($advanced_cache_file)))
					return FALSE; // Not possible to create.

				if(!is_file($advanced_cache_template) || !is_readable($advanced_cache_template))
					return FALSE; // Template file is missing; or not readable.

				if(!($advanced_cache_contents = file_get_contents($advanced_cache_template)))
					return FALSE; // Template file is missing; or is not readable.

				$possible_advanced_cache_constant_key_values = array_merge(
					$this->options, // The following additional keys are dynamic.
					array('cache_dir' => $this->base_path_to($this->cache_sub_dir)
					));
				foreach($possible_advanced_cache_constant_key_values as $_option => $_value)
				{
					$_value = (string)$_value; // Force string.

					switch($_option) // Some values need tranformations.
					{
						case 'exclude_uris': // Converts to regex (caSe insensitive).
						case 'exclude_refs': // Converts to regex (caSe insensitive).
						case 'exclude_agents': // Converts to regex (caSe insensitive).

							if(($_values = preg_split('/['."\r\n".']+/', $_value, NULL, PREG_SPLIT_NO_EMPTY)))
							{
								$_value = '/(?:'.implode('|', array_map(function ($string)
									{
										$string = preg_quote($string, '/'); // Escape.
										return preg_replace('/\\\\\*/', '.*?', $string); // Wildcards.

									}, $_values)).')/i';
							}
							$_value = "'".$this->esc_sq($_value)."'";

							break; // Break switch handler.

						default: // Default case handler.

							$_value = "'".$this->esc_sq($_value)."'";

							break; // Break switch handler.
					}
					$advanced_cache_contents = // Fill replacement codes.
						str_ireplace(array("'%%".__NAMESPACE__.'_'.$_option."%%'",
						                   "'%%".__NAMESPACE__.'_'.preg_replace('/^cache_/i', '', $_option)."%%'"),
						             $_value, $advanced_cache_contents);
				}
				unset($_option, $_value, $_values, $_response); // Housekeeping.

				if(strpos($this->file, WP_CONTENT_DIR) === 0)
					$plugin_file = "WP_CONTENT_DIR.'".$this->esc_sq(str_replace(WP_CONTENT_DIR, '', $this->file))."'";
				else $plugin_file = "'".$this->esc_sq($this->file)."'"; // Else use full absolute path.
				// Make it possible for the `advanced-cache.php` handler to find the plugin directory reliably.
				$advanced_cache_contents = str_ireplace("'%%".__NAMESPACE__."_PLUGIN_FILE%%'", $plugin_file, $advanced_cache_contents);

				// Ignore; this is created by ZenCache; and we don't need to obey in this case.
				#if(defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS)
				#	return FALSE; // We may NOT edit any files.

				if(!file_put_contents($advanced_cache_file, $advanced_cache_contents))
					return FALSE; // Failure; could not write file.

				$cache_lock = $this->cache_lock(); // Lock cache.

				if(!is_dir($cache_dir)) mkdir($cache_dir, 0775, TRUE);

				if(is_writable($cache_dir) && !is_file($cache_dir.'/.htaccess'))
					file_put_contents($cache_dir.'/.htaccess', $this->htaccess_deny);

				if(!is_dir($cache_dir) || !is_writable($cache_dir) || !is_file($cache_dir.'/.htaccess') || !file_put_contents($cache_dir.'/zc-advanced-cache', time()))
				{
					$this->cache_unlock($cache_lock); // Unlock cache.
					return NULL; // Special return value (NULL) in this case.
				}
				$this->cache_unlock($cache_lock); // Unlock cache.

				return TRUE; // Success!
			}

			/**
			 * Removes the `advanced-cache.php` file.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean `TRUE` on success. `FALSE` on failure.
			 *
			 * @note The `advanced-cache.php` file is NOT actually deleted by this routine.
			 *    Instead of deleting the file, we simply empty it out so that it's `0` bytes in size.
			 *
			 *    The reason for this is to preserve any file permissions set by the site owner.
			 *    If the site owner previously allowed this specific file to become writable, we don't want to
			 *    lose that permission by deleting the file; forcing the site owner to do it all over again later.
			 *
			 *    An example of where this is useful is when a site owner deactivates the QC plugin,
			 *    but later they decide that QC really is the most awesome plugin in the world and they turn it back on.
			 *
			 * @see delete_advanced_cache()
			 */
			public function remove_advanced_cache()
			{
				$advanced_cache_file = WP_CONTENT_DIR.'/advanced-cache.php';

				if(!is_file($advanced_cache_file)) return TRUE; // Already gone.

				if(is_readable($advanced_cache_file) && filesize($advanced_cache_file) === 0)
					return TRUE; // Already gone; i.e. it's empty already.

				if(!is_writable($advanced_cache_file)) return FALSE; // Not possible.

				// Ignore; this is created by ZenCache; and we don't need to obey in this case.
				#if(defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS)
				#	return FALSE; // We may NOT edit any files.

				/* Empty the file only. This way permissions are NOT lost in cases where
					a site owner makes this specific file writable for ZenCache. */
				if(file_put_contents($advanced_cache_file, '') !== 0)
					return FALSE; // Failure.

				return TRUE; // Removal success.
			}

			/**
			 * Deletes the `advanced-cache.php` file.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean `TRUE` on success. `FALSE` on failure.
			 *
			 * @note The `advanced-cache.php` file is deleted by this routine.
			 *
			 * @see remove_advanced_cache()
			 */
			public function delete_advanced_cache()
			{
				$advanced_cache_file = WP_CONTENT_DIR.'/advanced-cache.php';

				if(!is_file($advanced_cache_file)) return TRUE; // Already gone.

				// Ignore; this is created by ZenCache; and we don't need to obey in this case.
				#if(defined('DISALLOW_FILE_MODS') && DISALLOW_FILE_MODS)
				#	return FALSE; // We may NOT edit any files.

				if(!is_writable($advanced_cache_file) || !unlink($advanced_cache_file))
					return FALSE; // Not possible; or outright failure.

				return TRUE; // Deletion success.
			}

			/**
			 * Checks to make sure the `zc-blog-paths` file still exists;
			 *    and if it doesn't, the `zc-blog-paths` file is regenerated automatically.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to `init` hook.
			 *
			 * @note This runs so that remote deployments which completely wipe out an
			 *    existing set of website files (like the AWS Elastic Beanstalk does) will NOT cause ZenCache
			 *    to stop functioning due to the lack of a `zc-blog-paths` file, which is generated by ZenCache.
			 *
			 *    For instance, if you have a Git repo with all of your site files; when you push those files
			 *    to your website to deploy them, you most likely do NOT have the `zc-blog-paths` file.
			 *    ZenCache creates this file on its own. Thus, if it's missing (and QC is active)
			 *    we simply regenerate the file automatically to keep ZenCache running.
			 */
			public function check_blog_paths()
			{
				if(!$this->options['enable'])
					return; // Nothing to do.

				if(!is_multisite()) return; // N/A.

				if(!empty($_REQUEST[__NAMESPACE__]))
					return; // Skip on plugin actions.

				$cache_dir = $this->cache_dir(); // Current cache directory.

				if(!is_file($cache_dir.'/zc-blog-paths'))
					$this->update_blog_paths();
			}

			/**
			 * Creates and/or updates the `zc-blog-paths` file.
			 *
			 * @since 140422 First documented version.
			 *
			 * @attaches-to `enable_live_network_counts` filter.
			 *
			 * @param mixed $enable_live_network_counts Optional, defaults to a `NULL` value.
			 *
			 * @return mixed The value of `$enable_live_network_counts` (passes through).
			 *
			 * @note While this routine is attached to a WP filter, we also call upon it directly at times.
			 */
			public function update_blog_paths($enable_live_network_counts = NULL)
			{
				$value = // This hook actually rides on a filter.
					$enable_live_network_counts; // Filter value.

				if(!$this->options['enable'])
					return $value; // Nothing to do.

				if(!is_multisite()) return $value; // N/A.

				$cache_dir  = $this->cache_dir(); // Cache dir.
				$cache_lock = $this->cache_lock(); // Lock.

				if(!is_dir($cache_dir)) mkdir($cache_dir, 0775, TRUE);

				if(is_writable($cache_dir) && !is_file($cache_dir.'/.htaccess'))
					file_put_contents($cache_dir.'/.htaccess', $this->htaccess_deny);

				if(is_dir($cache_dir) && is_writable($cache_dir))
				{
					$paths = // Collect child blog paths from the WordPress database.
						$this->wpdb()->get_col("SELECT `path` FROM `".esc_sql($this->wpdb()->blogs)."` WHERE `deleted` <= '0'");

					foreach($paths as &$_path) // Strip base; these need to match `$host_dir_token`.
						$_path = '/'.ltrim(preg_replace('/^'.preg_quote($this->host_base_token(), '/').'/', '', $_path), '/');
					unset($_path); // Housekeeping.

					file_put_contents($cache_dir.'/zc-blog-paths', serialize($paths));
				}
				$this->cache_unlock($cache_lock); // Unlock cache directory.

				return $value; // Pass through untouched (always).
			}

			/**
			 * Removes the entire base directory.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return integer Total files removed by this routine (if any).
			 */
			public function remove_base_dir()
			{
				$counter = 0; // Initialize.

				@set_time_limit(1800); // @TODO When disabled, display a warning.

				return ($counter += $this->delete_all_files_dirs_in($this->wp_content_base_dir_to(''), TRUE));
			}
		}

		/**
		 * Used internally by other ZenCache classes as an easy way to reference
		 *    the core {@link plugin} class instance for ZenCache.
		 *
		 * @since 140422 First documented version.
		 *
		 * @return plugin Class instance.
		 */
		function plugin() // Easy reference.
		{
			return $GLOBALS[__NAMESPACE__];
		}

		/**
		 * A global reference to the ZenCache plugin.
		 *
		 * @since 140422 First documented version.
		 *
		 * @var plugin Main plugin class.
		 */
		if(!isset($GLOBALS[__NAMESPACE__.'_autoload_plugin']) || $GLOBALS[__NAMESPACE__.'_autoload_plugin'])
			$GLOBALS[__NAMESPACE__] = $GLOBALS['quick_cache'] = new plugin(); // Load plugin automatically.
		require_once dirname(__FILE__).'/includes/api-class.php'; // API class.
	}
	else if(!empty($GLOBALS[__NAMESPACE__.'_conflicting_plugin']) && empty($GLOBALS[__NAMESPACE__.'_uninstalling'])) add_action('all_admin_notices', function ()
	{
		if(!empty($GLOBALS[__NAMESPACE__.'_conflicting_plugin_lite_pro']))
			return; // Already detected this in one plugin or the other.

		$construct_name          = function ($slug_or_ns)
		{
			$slug_or_ns = trim(strtolower((string)$slug_or_ns));

			if(preg_match('/^'.preg_quote(__NAMESPACE__, '/').'[_\-]pro$/', $slug_or_ns))
				$slug_or_ns = strtolower(__NAMESPACE__); // Strip `-pro` suffix.

			$name = preg_replace('/[^a-z0-9]/', ' ', $slug_or_ns);
			$name = str_replace('cache', 'Cache', ucwords($name));

			return $name; // e.g. `x-cache` becomes `X Cache`.
		};
		$text_domain             = str_replace('_', '-', __NAMESPACE__);
		$conflicting_plugin_name = $construct_name($GLOBALS[__NAMESPACE__.'_conflicting_plugin']);
		$plugin_name             = $construct_name(__NAMESPACE__); // e.g. `zencache` becomes `ZenCache`.

		if(strcasecmp($conflicting_plugin_name, $plugin_name) === 0) // Conflict between lite/pro editions?
		{
			$conflicting_plugin_name = $plugin_name.' '.__('Lite', $text_domain);
			$plugin_name             = $plugin_name.' '.__('Pro', $text_domain);

			$GLOBALS[__NAMESPACE__.'_conflicting_plugin_lite_pro'] = TRUE;
		}
		echo '<div class="error">'.
		     '   <p>'. // Running multiple versions of this plugin at same time.
		     '      '.sprintf(__('<strong>%1$s</strong> is NOT running. A conflicting plugin, <strong>%2$s</strong>, is currently active. Please deactivate the %2$s plugin to clear this message.', $text_domain), esc_html($plugin_name), esc_html($conflicting_plugin_name)).
		     '   </p>'.
		     '</div>';
	});
}

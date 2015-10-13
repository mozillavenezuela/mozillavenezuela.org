<?php
namespace zencache // Root namespace.
{
	if(!defined('WPINC')) // MUST have WordPress.
		exit('Do NOT access this file directly: '.basename(__FILE__));

	if(!class_exists('\\'.__NAMESPACE__.'\\share'))
	{
		/**
		 * ZenCache (Shared Methods)
		 *
		 * @package zencache\share
		 * @since 140725 Reorganizing class members.
		 */
		abstract class share // Shared between {@link advanced_cache} and {@link plugin}.
		{
			/* --------------------------------------------------------------------------------------
			 * Class properties.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Name of this plugin.
			 *
			 * @since 150218 Refactoring.
			 *
			 * @var string Plugin name.
			 */
			public $name = 'ZenCache';

			/**
			 * Short name for this plugin.
			 *
			 * @since 150218 Refactoring.
			 *
			 * @var string Short name for this plugin.
			 */
			public $short_name = 'ZC';

			/**
			 * Domain name for this plugin.
			 *
			 * @since 150218 Refactoring.
			 *
			 * @var string Domain name for this plugin.
			 */
			public $domain = 'zencache.com';

			/**
			 * Identifies pro version of ZenCache.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var boolean `TRUE` for ZenCache Pro.
			 */
			public $is_pro = FALSE;

			/**
			 * Version string in YYMMDD[+build] format.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var string Current version of the software.
			 */
			public $version = '150930';

			/**
			 * Plugin slug; based on `__NAMESPACE__`.
			 *
			 * @since 150218 Refactoring.
			 *
			 * @var string Plugin slug; based on `__NAMESPACE__`.
			 */
			public $slug = '';

			/**
			 * Text domain for translations; based on `__NAMESPACE__`.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var string Defined by class constructor; for translations.
			 */
			public $text_domain = '';

			/**
			 * An instance-based cache for class members.
			 *
			 * @since 140725 Reorganizing class members.
			 *
			 * @var array An instance-based cache for class members.
			 */
			public $cache = array();

			/**
			 * A global static cache for class members.
			 *
			 * @since 140725 Reorganizing class members.
			 *
			 * @var array Global static cache for class members.
			 */
			public static $static = array();

			/**
			 * Array of hooks added by plugins.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var array An array of any hooks added by plugins.
			 */
			public $hooks = array();

			/**
			 * Flag indicating the current user login cookie is expired or invalid.
			 *
			 * @since 140429 Improving user cache handlers.
			 *
			 * @var boolean `TRUE` if current user login cookie is expired or invalid.
			 *    See also {@link user_token()} and {@link advanced_cache::maybe_start_ob_when_logged_in_postload()}.
			 */
			public $user_login_cookie_expired_or_invalid = FALSE;

			/* --------------------------------------------------------------------------------------
			 * Cache path class constants.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Exclude scheme from cache path.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var integer Part of a bitmask.
			 */
			const CACHE_PATH_NO_SCHEME = 1;

			/**
			 * Exclude host (i.e. domain name) from cache path.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var integer Part of a bitmask.
			 */
			const CACHE_PATH_NO_HOST = 2;

			/**
			 * Exclude path from cache path.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var integer Part of a bitmask.
			 */
			const CACHE_PATH_NO_PATH = 4;

			/**
			 * Exclude path index (i.e. no default `index`) from cache path.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var integer Part of a bitmask.
			 */
			const CACHE_PATH_NO_PATH_INDEX = 8;

			/**
			 * Exclude query, user & version salt from cache path.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var integer Part of a bitmask.
			 */
			const CACHE_PATH_NO_QUV = 16;

			/**
			 * Exclude query string from cache path.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var integer Part of a bitmask.
			 */
			const CACHE_PATH_NO_QUERY = 32;

			/**
			 * Exclude user token from cache path.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var integer Part of a bitmask.
			 */
			const CACHE_PATH_NO_USER = 64;

			/**
			 * Exclude version salt from cache path.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var integer Part of a bitmask.
			 */
			const CACHE_PATH_NO_VSALT = 128;

			/**
			 * Exclude extension from cache path.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var integer Part of a bitmask.
			 */
			const CACHE_PATH_NO_EXT = 256;

			/**
			 * Allow wildcards in the cache path.
			 *
			 * @since 140725 Improving XML Sitemap support.
			 *
			 * @var integer Part of a bitmask.
			 */
			const CACHE_PATH_ALLOW_WILDCARDS = 512;

			/**
			 * Default cache path regex suffix frag.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @var string Default regex suffix frag used in cache path patterns.
			 */
			const CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG = '(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])';

			/* --------------------------------------------------------------------------------------
			 * Shared constructor.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Class constructor.
			 *
			 * @since 140422 First documented version.
			 */
			public function __construct()
			{
				if(strpos(__NAMESPACE__, '\\') !== FALSE) // Sanity check.
					throw new \exception('Not a root namespace: `'.__NAMESPACE__.'`.');

				$this->slug = $this->text_domain = str_replace('_', '-', __NAMESPACE__);
			}

			/* --------------------------------------------------------------------------------------
			 * Cache directory/path/URL utilities.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Absolute server path to the cache directory.
			 *
			 * @since 140725 Reorganizing class members.
			 *
			 * @param string $rel_path Optional; a relative path within the cache directory.
			 *
			 * @return string Absolute server path to the cache directory.
			 *
			 * @throws \exception If unable to determine the cache directory location.
			 */
			public function cache_dir($rel_path = '')
			{
				if(method_exists($this, 'wp_content_base_dir_to') && isset($this->cache_sub_dir))
					$cache_dir = $this->wp_content_base_dir_to($this->cache_sub_dir);

				else if(defined('ZENCACHE_DIR') && ZENCACHE_DIR)
					$cache_dir = ZENCACHE_DIR; // Global constant.

				if(empty($cache_dir)) // No cache directory?
					throw new \exception(__('Unable to determine cache directory location.', 'zencache'));

				return $cache_dir.($rel_path ? '/'.ltrim((string)$rel_path) : '');
			}

			/**
			 * Converts a URL into a relative `cache/path`; i.e. relative to the cache directory.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string  $url The input URL to convert.
			 * @param string  $with_user_token Optional user token (if applicable).
			 * @param string  $with_version_salt Optional version salt (if applicable).
			 * @param integer $flags Optional flags; a bitmask provided by `CACHE_PATH_*` constants.
			 *
			 * @return string The resulting relative `cache/path` based on the input `$url`; i.e. relative to the cache directory.
			 */
			public function build_cache_path($url, $with_user_token = '', $with_version_salt = '', $flags = 0)
			{
				$cache_path        = ''; // Initialize.
				$url               = trim((string)$url);
				$with_user_token   = trim((string)$with_user_token);
				$with_version_salt = trim((string)$with_version_salt);

				if($url && strpos($url, '://') === FALSE)
					$url = '//'.ltrim($url, '/');

				if($url && strpos($url, '&amp;') !== FALSE)
					$url = str_replace('&amp;', '&', $url);

				if(!$url || !($url = parse_url($url)))
					return ''; // Invalid URL.

				if(!($flags & $this::CACHE_PATH_NO_SCHEME))
				{
					if(!empty($url['scheme']))
						$cache_path .= $url['scheme'].'/';
					else $cache_path .= $this->is_ssl() ? 'https/' : 'http/';
				}
				if(!($flags & $this::CACHE_PATH_NO_HOST))
				{
					if(!empty($url['host']))
						$cache_path .= $url['host'].'/';
					else $cache_path .= $_SERVER['HTTP_HOST'].'/';
				}
				if(!($flags & $this::CACHE_PATH_NO_PATH))
				{
					if(isset($url['path'][201])) // Extremely long?
					{
						$url['_path_tmp'] = '/'; // Initialize tmp path.
						foreach(explode('/', $url['path']) as $_path_component)
						{
							if(!isset($_path_component[0]))
								continue; // Empty.

							if(isset($_path_component[201]))
								$_path_component = 'lpc-'.sha1($_path_component);
							$url['_path_tmp'] .= $_path_component.'/';
						}
						$url['path'] = $url['_path_tmp']; // Shorter components.
						unset($_path_component, $url['_path_tmp']); // Housekeeping.

						if(isset($url['path'][2001])) // Overall path length is very long?
							$url['path'] = '/lp-'.sha1($url['path']).'/';
					}
					if(!empty($url['path']) && strlen($url['path'] = trim($url['path'], '\\/'." \t\n\r\0\x0B")))
						$cache_path .= $url['path'].'/'; // Has a path, let's use it :-)
					else if(!($flags & $this::CACHE_PATH_NO_PATH_INDEX)) $cache_path .= 'index/';
				}
				if($this->is_extension_loaded('mbstring') && mb_check_encoding($cache_path, 'UTF-8'))
					$cache_path = mb_strtolower($cache_path, 'UTF-8');
				$cache_path = str_replace('.', '-', strtolower($cache_path));

				if(!($flags & $this::CACHE_PATH_NO_QUV))
				{
					if(!($flags & $this::CACHE_PATH_NO_QUERY))
						if(isset($url['query']) && $url['query'] !== '')
							$cache_path = rtrim($cache_path, '/').'.q/'.md5($url['query']).'/';

					if(!($flags & $this::CACHE_PATH_NO_USER))
						if($with_user_token !== '') // Allow a `0` value if desirable.
							$cache_path = rtrim($cache_path, '/').'.u/'.str_replace(array('/', '\\'), '-', $with_user_token).'/';

					if(!($flags & $this::CACHE_PATH_NO_VSALT))
						if($with_version_salt !== '') // Allow a `0` value if desirable.
							$cache_path = rtrim($cache_path, '/').'.v/'.str_replace(array('/', '\\'), '-', $with_version_salt).'/';
				}
				$cache_path = trim(preg_replace(array('/\/+/', '/\.+/'), array('/', '.'), $cache_path), '/');

				if($flags & $this::CACHE_PATH_ALLOW_WILDCARDS) // Allow `*`?
					$cache_path = preg_replace('/[^a-z0-9\/.*]/i', '-', $cache_path);
				else $cache_path = preg_replace('/[^a-z0-9\/.]/i', '-', $cache_path);

				if(!($flags & $this::CACHE_PATH_NO_EXT))
					$cache_path .= '.html';

				return $cache_path; // Do not filter.
			}

			/**
			 * Variation of {@link build_cache_path()} for relative regex.
			 *
			 * This converts a URL into a relative `cache/path`; i.e. relative to the cache directory,
			 *    and then converts that into a regex pattern w/ an optional custom `$regex_suffix_frag`.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string $url The input URL to convert. This CAN be left empty when necessary.
			 *    If empty, the final regex pattern will be `/^'.$regex_suffix_frag.'/i`.
			 *    If empty, it's a good idea to start `$regex_suffix_frag` with `.*?`.
			 *
			 * @param string $regex_suffix_frag Regex fragment to come after the relative cache/path.
			 *    Defaults to: `(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])`.
			 *    Note: this should NOT have delimiters; i.e. do NOT start or end with `/`.
			 *    See also: {@link CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG}.
			 *
			 * @return string The resulting relative `cache/path` based on the input `$url`; converted to regex pattern.
			 *    Note that `http://` or `https://` is automatically converted to `\/https?\/` here.
			 *       This allows the pattern to pick up either scheme.
			 *
			 * @note This variation of {@link build_cache_path()} automatically forces the following flags.
			 *
			 *       - {@link CACHE_PATH_NO_PATH_INDEX}
			 *       - {@link CACHE_PATH_NO_QUV}
			 *       - {@link CACHE_PATH_NO_EXT}
			 *
			 * @see CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG
			 */
			public function build_cache_path_regex($url, $regex_suffix_frag = self::CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG)
			{
				$url                           = trim((string)$url);
				$regex_suffix_frag             = (string)$regex_suffix_frag;
				$abs_relative_cache_path_regex = ''; // Initialize.

				if($url) // Only necessary if there is a URL to deal with here.
				{
					$flags = $this::CACHE_PATH_NO_PATH_INDEX
					         | $this::CACHE_PATH_NO_QUV | $this::CACHE_PATH_NO_EXT;

					$relative_cache_path           = $this->build_cache_path($url, '', '', $flags);
					$abs_relative_cache_path       = isset($relative_cache_path[0]) ? '/'.$relative_cache_path : '';
					$abs_relative_cache_path_regex = preg_quote($abs_relative_cache_path, '/');

					if($abs_relative_cache_path_regex) $abs_relative_cache_path_regex = // `http` and `https` schemes.
						preg_replace('/^\\\\\/https?\\\\\//i', '\/https?\/', $abs_relative_cache_path_regex);
				}
				return '/^'.$abs_relative_cache_path_regex.$regex_suffix_frag.'/i';
			}

			/**
			 * Variation of {@link build_cache_path()} for relative regex.
			 *
			 * This converts a URL into a relative `cache/path`; i.e. relative to the current host|blog directory,
			 *    and then converts that into a regex pattern w/ an optional custom `$regex_suffix_frag`.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string $url The input URL to convert. This CAN be left empty when necessary.
			 *    If empty, the final regex pattern will be `/^'.$regex_suffix_frag.'/i`.
			 *    If empty, it's a good idea to start `$regex_suffix_frag` with `.*?`.
			 *
			 * @param string $regex_suffix_frag Regex fragment to come after the relative cache/path.
			 *    Defaults to: `(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])`.
			 *    Note: this should NOT have delimiters; i.e. do NOT start or end with `/`.
			 *    See also: {@link CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG}.
			 *
			 * @return string The resulting relative `cache/path` based on the input `$url`; converted to regex pattern.
			 *
			 * @note This variation of {@link build_cache_path()} automatically forces the following flags.
			 *
			 *       - {@link CACHE_PATH_NO_SCHEME}
			 *       - {@link CACHE_PATH_NO_HOST}
			 *       - {@link CACHE_PATH_NO_PATH_INDEX}
			 *       - {@link CACHE_PATH_NO_QUV}
			 *       - {@link CACHE_PATH_NO_EXT}
			 *
			 * @see CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG
			 */
			public function build_host_cache_path_regex($url, $regex_suffix_frag = self::CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG)
			{
				$url                           = trim((string)$url);
				$regex_suffix_frag             = (string)$regex_suffix_frag;
				$abs_relative_cache_path_regex = ''; // Initialize.

				if($url) // Only necessary if there is a URL to deal with here.
				{
					$flags = $this::CACHE_PATH_NO_SCHEME | $this::CACHE_PATH_NO_HOST | $this::CACHE_PATH_NO_PATH_INDEX
					         | $this::CACHE_PATH_NO_QUV | $this::CACHE_PATH_NO_EXT;

					$host                 = $_SERVER['HTTP_HOST'];
					$host_base_dir_tokens = $this->host_base_dir_tokens();
					$host_url             = rtrim('http://'.$host.$host_base_dir_tokens, '/');
					$host_cache_path      = $this->build_cache_path($host_url, '', '', $flags);

					$cache_path                    = $this->build_cache_path($url, '', '', $flags);
					$relative_cache_path           = preg_replace('/^'.preg_quote($host_cache_path, '/').'(?:\/|$)/i', '', $cache_path);
					$abs_relative_cache_path       = isset($relative_cache_path[0]) ? '/'.$relative_cache_path : '';
					$abs_relative_cache_path_regex = preg_quote($abs_relative_cache_path, '/');
				}
				return '/^'.$abs_relative_cache_path_regex.$regex_suffix_frag.'/i';
			}

			/**
			 * Variation of {@link build_cache_path()} for relative regex fragments.
			 *
			 * This converts URIs into relative `cache/paths`; i.e. relative to the current host|blog directory,
			 *    and then converts those into `(?:regex|fragments)` with piped `|` alternatives.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string $uris A line-delimited list of URIs. These may contain `*` wildcards also.
			 *
			 * @param string $regex_suffix_frag Regex fragment to come after each relative cache/path.
			 *    Defaults to: `(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])`.
			 *    Note: this should NOT have delimiters; i.e. do NOT start or end with `/`.
			 *    See also: {@link CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG}.
			 *
			 * @return string The resulting `cache/paths` based on the input `$uris`; converted to `(?:regex|fragments)`.
			 *
			 * @note This variation of {@link build_cache_path()} automatically forces the following flags.
			 *
			 *       - {@link CACHE_PATH_ALLOW_WILDCARDS}
			 *       - {@link CACHE_PATH_NO_SCHEME}
			 *       - {@link CACHE_PATH_NO_HOST}
			 *       - {@link CACHE_PATH_NO_PATH_INDEX}
			 *       - {@link CACHE_PATH_NO_QUV}
			 *       - {@link CACHE_PATH_NO_EXT}
			 */
			public function build_host_cache_path_regex_frags_from_wc_uris($uris, $regex_suffix_frag = self::CACHE_PATH_REGEX_DEFAULT_SUFFIX_FRAG)
			{
				if(!($uris = trim((string)$uris)))
					return ''; // Nothing to do.

				$_this             = $this; // Reference for the closure below.
				$regex_suffix_frag = (string)$regex_suffix_frag; // Force a string value.
				$flags             = $this::CACHE_PATH_ALLOW_WILDCARDS | $this::CACHE_PATH_NO_SCHEME | $this::CACHE_PATH_NO_HOST
				                     | $this::CACHE_PATH_NO_PATH_INDEX | $this::CACHE_PATH_NO_QUV | $this::CACHE_PATH_NO_EXT;

				$host                 = $_SERVER['HTTP_HOST'];
				$host_base_dir_tokens = $this->host_base_dir_tokens();
				$host_url             = rtrim('http://'.$host.$host_base_dir_tokens, '/');
				$host_cache_path      = $this->build_cache_path($host_url, '', '', $flags);

				return '(?:'.implode('|', array_map(function ($pattern) use ($_this, $regex_suffix_frag, $flags, $host_url, $host_cache_path)
				{
					$cache_path          = $_this->build_cache_path($host_url.'/'.trim($pattern, '/'), '', '', $flags);
					$relative_cache_path = preg_replace('/^'.preg_quote($host_cache_path, '/').'(?:\/|$)/i', '', $cache_path);

					return preg_replace('/\\\\\*/', '.*?', preg_quote($relative_cache_path, '/')).$regex_suffix_frag; #

				}, preg_split('/['."\r\n".']+/', $uris, NULL, PREG_SPLIT_NO_EMPTY))).')';
			}

			/* --------------------------------------------------------------------------------------
			 * Token generation utilities.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Produces a token based on the current `$_SERVER['HTTP_HOST']`.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param boolean $dashify Optional, defaults to a `FALSE` value.
			 *    If `TRUE`, the token is returned with dashes in place of `[^a-z0-9\/]`.
			 *
			 * @return string Token based on the current `$_SERVER['HTTP_HOST']`.
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 */
			public function host_token($dashify = FALSE)
			{
				$dashify = (integer)$dashify;

				if(isset(static::$static[__FUNCTION__][$dashify]))
					return static::$static[__FUNCTION__][$dashify];

				$host        = strtolower($_SERVER['HTTP_HOST']);
				$token_value = $dashify ? trim(preg_replace('/[^a-z0-9\/]/i', '-', $host), '-') : $host;

				return (static::$static[__FUNCTION__][$dashify] = $token_value);
			}

			/**
			 * Produces a token based on the current site's base directory.
			 *
			 * @since 140605 First documented version.
			 *
			 * @param boolean $dashify Optional, defaults to a `FALSE` value.
			 *    If `TRUE`, the token is returned with dashes in place of `[^a-z0-9\/]`.
			 *
			 * @return string Produces a token based on the current site's base directory;
			 *    (i.e. in the case of a sub-directory multisite network).
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 *
			 * @see plugin::clear_cache()
			 * @see plugin::update_blog_paths()
			 */
			public function host_base_token($dashify = FALSE)
			{
				$dashify = (integer)$dashify;

				if(isset(static::$static[__FUNCTION__][$dashify]))
					return static::$static[__FUNCTION__][$dashify];

				$host_base_token = '/'; // Assume NOT multisite; or own domain.

				if(is_multisite() && (!defined('SUBDOMAIN_INSTALL') || !SUBDOMAIN_INSTALL))
				{ // Multisite w/ sub-directories; need a valid sub-directory token.

					if(defined('PATH_CURRENT_SITE'))
						$host_base_token = PATH_CURRENT_SITE;

					else if(!empty($GLOBALS['base']))
						$host_base_token = $GLOBALS['base'];

					$host_base_token = trim($host_base_token, '\\/'." \t\n\r\0\x0B");
					$host_base_token = isset($host_base_token[0]) ? '/'.$host_base_token.'/' : '/';
				}
				$token_value = $dashify ? trim(preg_replace('/[^a-z0-9\/]/i', '-', $host_base_token), '-') : $host_base_token;

				return (static::$static[__FUNCTION__][$dashify] = $token_value);
			}

			/**
			 * Produces a token based on the current blog's sub-directory.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param boolean $dashify Optional, defaults to a `FALSE` value.
			 *    If `TRUE`, the token is returned with dashes in place of `[^a-z0-9\/]`.
			 *
			 * @return string Produces a token based on the current blog sub-directory
			 *    (i.e. in the case of a sub-directory multisite network).
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 *
			 * @see plugin::clear_cache()
			 * @see plugin::update_blog_paths()
			 */
			public function host_dir_token($dashify = FALSE)
			{
				$dashify = (integer)$dashify;

				if(isset(static::$static[__FUNCTION__][$dashify]))
					return static::$static[__FUNCTION__][$dashify];

				$host_dir_token = '/'; // Assume NOT multisite; or own domain.

				if(is_multisite() && (!defined('SUBDOMAIN_INSTALL') || !SUBDOMAIN_INSTALL))
				{ // Multisite w/ sub-directories; need a valid sub-directory token.

					$uri_minus_base = // Supports `/sub-dir/child-blog-sub-dir/` also.
						preg_replace('/^'.preg_quote($this->host_base_token(), '/').'/', '', $_SERVER['REQUEST_URI']);

					list($host_dir_token) = explode('/', trim($uri_minus_base, '/'));
					$host_dir_token = isset($host_dir_token[0]) ? '/'.$host_dir_token.'/' : '/';

					if($host_dir_token !== '/' // Perhaps NOT the main site?
					   && (!is_file(($cache_dir = $this->cache_dir()).'/zc-blog-paths') // NOT a read/valid blog path?
					       || !in_array($host_dir_token, unserialize(file_get_contents($cache_dir.'/zc-blog-paths')), TRUE))
					) $host_dir_token = '/'; // Main site; e.g. this is NOT a real/valid child blog path.
				}
				$token_value = $dashify ? trim(preg_replace('/[^a-z0-9\/]/i', '-', $host_dir_token), '-') : $host_dir_token;

				return (static::$static[__FUNCTION__][$dashify] = $token_value);
			}

			/**
			 * Produces tokens for the current site's base directory & current blog's sub-directory.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param boolean $dashify Optional, defaults to a `FALSE` value.
			 *    If `TRUE`, the tokens are returned with dashes in place of `[^a-z0-9\/]`.
			 *
			 * @return string Tokens for the current site's base directory & current blog's sub-directory.
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 *
			 * @see plugin::clear_cache()
			 * @see plugin::update_blog_paths()
			 */
			public function host_base_dir_tokens($dashify = FALSE)
			{
				$dashify = (integer)$dashify;

				if(isset(static::$static[__FUNCTION__][$dashify]))
					return static::$static[__FUNCTION__][$dashify];

				$tokens = preg_replace('/\/{2,}/', '/', $this->host_base_token($dashify).$this->host_dir_token($dashify));

				return (static::$static[__FUNCTION__][$dashify] = $tokens);
			}

			/**
			 * Produces a token based on the current user.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return string Produces a token based on the current user;
			 *    else an empty string if that's not possible to do.
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 *
			 * @note This routine may trigger a flag which indicates that the current user was logged-in at some point,
			 *    but now the login cookie can no longer be validated by WordPress; i.e. they are NOT actually logged in any longer.
			 *    See {@link $user_login_cookie_expired_or_invalid}
			 *
			 * @warning Do NOT call upon this method until WordPress reaches it's cache postload phase.
			 */
			public function user_token() // When/if possible.
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				$wp_validate_auth_cookie_possible = $this->function_is_possible('wp_validate_auth_cookie');
				if($wp_validate_auth_cookie_possible && ($user_id = (integer)wp_validate_auth_cookie('', 'logged_in')))
					return (static::$static[__FUNCTION__] = $user_id); // A real user in this case.

				else if(!empty($_COOKIE['comment_author_email_'.COOKIEHASH]) && is_string($_COOKIE['comment_author_email_'.COOKIEHASH]))
					return (static::$static[__FUNCTION__] = md5(strtolower(stripslashes($_COOKIE['comment_author_email_'.COOKIEHASH]))));

				else if(!empty($_COOKIE['wp-postpass_'.COOKIEHASH]) && is_string($_COOKIE['wp-postpass_'.COOKIEHASH]))
					return (static::$static[__FUNCTION__] = md5(stripslashes($_COOKIE['wp-postpass_'.COOKIEHASH])));

				else if(defined('SID') && SID) return (static::$static[__FUNCTION__] = preg_replace('/[^a-z0-9]/i', '', SID));

				if($wp_validate_auth_cookie_possible // We were unable to validate the login cookie?
				   && !empty($_COOKIE['wordpress_logged_in_'.COOKIEHASH]) && is_string($_COOKIE['wordpress_logged_in_'.COOKIEHASH])
				) $this->user_login_cookie_expired_or_invalid = TRUE; // Flag as `TRUE`.

				return (static::$static[__FUNCTION__] = '');
			}

			/* --------------------------------------------------------------------------------------
			 * Conditional utilities.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Does the current request include a query string?
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean `TRUE` if request includes a query string.
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 */
			public function is_get_request_w_query()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				if(!empty($_GET) || isset($_SERVER['QUERY_STRING'][0]))
					if(!(isset($_GET['zcABC']) && count($_GET) === 1)) // Special case.
						return (static::$static[__FUNCTION__] = TRUE);

				return (static::$static[__FUNCTION__] = FALSE);
			}

			/**
			 * Is the current request method `POST`, `PUT` or `DELETE`?
			 *
			 * @since 150218 Updating `is_uncacheable_request_method()` and restoring this one.
			 *
			 * @return boolean `TRUE` if current request method is `POST`, `PUT` or `DELETE`.
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 */
			public function is_post_put_delete_request()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				if(!empty($_POST)) // Being thorough.
					return (static::$static[__FUNCTION__] = TRUE);

				if(!empty($_SERVER['REQUEST_METHOD']))
					if(in_array(strtoupper($_SERVER['REQUEST_METHOD']), array('POST', 'PUT', 'DELETE'), TRUE))
						return (static::$static[__FUNCTION__] = TRUE);

				return (static::$static[__FUNCTION__] = FALSE);
			}

			/**
			 * Is the current request method is uncacheable?
			 *
			 * @since 150218 Reversing logic; only allow `GET` requests to be cached.
			 * @since 140725 Adding HEAD/OPTIONS/TRACE/CONNECT to the list of uncacheables.
			 *
			 * @return boolean `TRUE` if current request method is uncacheable.
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 */
			public function is_uncacheable_request_method()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				if(!empty($_POST)) // Being thorough.
					return (static::$static[__FUNCTION__] = TRUE);

				if(!empty($_SERVER['REQUEST_METHOD']))
					if(!in_array(strtoupper($_SERVER['REQUEST_METHOD']), array('GET'), TRUE))
						return (static::$static[__FUNCTION__] = TRUE);

				return (static::$static[__FUNCTION__] = FALSE);
			}

			/**
			 * Should the current user should be considered a logged-in user?
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean `TRUE` if current user should be considered a logged-in user.
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 */
			public function is_like_user_logged_in()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				if(defined('SID') && SID) // Session ID.
					return (static::$static[__FUNCTION__] = TRUE);

				$logged_in_cookies[] = 'comment_author_'; // Comment (and/or reply) authors.
				$logged_in_cookies[] = 'wp-postpass_'; // Password access to protected posts.

				$logged_in_cookies[] = (defined('AUTH_COOKIE')) ? AUTH_COOKIE : 'wordpress_';
				$logged_in_cookies[] = (defined('SECURE_AUTH_COOKIE')) ? SECURE_AUTH_COOKIE : 'wordpress_sec_';
				$logged_in_cookies[] = (defined('LOGGED_IN_COOKIE')) ? LOGGED_IN_COOKIE : 'wordpress_logged_in_';
				$logged_in_cookies   = '/^(?:'.implode('|', array_map(function ($logged_in_cookie)
					{
						return preg_quote($logged_in_cookie, '/'); // Escape.

					}, $logged_in_cookies)).')/';
				$test_cookie         = (defined('TEST_COOKIE')) ? TEST_COOKIE : 'wordpress_test_cookie';

				foreach($_COOKIE as $_key => $_value) if($_key !== $test_cookie)
					if(preg_match($logged_in_cookies, $_key) && $_value)
						return (static::$static[__FUNCTION__] = TRUE);
				unset($_key, $_value); // Housekeeping.

				return (static::$static[__FUNCTION__] = FALSE);
			}

			/**
			 * Are we in a LOCALHOST environment?
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean `TRUE` if we are in a LOCALHOST environment.
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 */
			public function is_localhost()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				if(defined('LOCALHOST') && LOCALHOST)
					return (static::$static[__FUNCTION__] = TRUE);

				if(!defined('LOCALHOST') && !empty($_SERVER['HTTP_HOST']))
					if(preg_match('/localhost|127\.0\.0\.1/i', $_SERVER['HTTP_HOST']))
						return (static::$static[__FUNCTION__] = TRUE);

				return (static::$static[__FUNCTION__] = FALSE);
			}

			/**
			 * Is the current request for the Auto-Cache Engine?
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean `TRUE` if the current request is for the Auto-Cache Engine.
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 */
			public function is_auto_cache_engine()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				if(!empty($_SERVER['HTTP_USER_AGENT']))
					if(stripos($_SERVER['HTTP_USER_AGENT'], __NAMESPACE__) !== FALSE)
						return (static::$static[__FUNCTION__] = TRUE);

				return (static::$static[__FUNCTION__] = FALSE);
			}

			/**
			 * Is the current request for a feed?
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean `TRUE` if the current request is for a feed.
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 */
			public function is_feed()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				if(preg_match('/\/feed(?:[\/?]|$)/', $_SERVER['REQUEST_URI']))
					return (static::$static[__FUNCTION__] = TRUE);

				if(isset($_REQUEST['feed'])) // Query var?
					return (static::$static[__FUNCTION__] = TRUE);

				return (static::$static[__FUNCTION__] = FALSE);
			}

			/**
			 * Is the current request over SSL?
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean `TRUE` if the current request is over SSL.
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 */
			public function is_ssl()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				if(!empty($_SERVER['SERVER_PORT']))
					if($_SERVER['SERVER_PORT'] === '443')
						return (static::$static[__FUNCTION__] = TRUE);

				if(!empty($_SERVER['HTTPS']))
					if($_SERVER['HTTPS'] === '1' || strcasecmp($_SERVER['HTTPS'], 'on') === 0)
						return (static::$static[__FUNCTION__] = TRUE);

				if(!empty($_SERVER['HTTP_X_FORWARDED_PROTO']))
					if(strcasecmp($_SERVER['HTTP_X_FORWARDED_PROTO'], 'https') === 0)
						return (static::$static[__FUNCTION__] = TRUE);

				return (static::$static[__FUNCTION__] = FALSE);
			}

			/**
			 * Is a document/string an HTML/XML doc; or no?
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string $doc Input string/document to check.
			 *
			 * @return boolean `TRUE` if `$doc` is an HTML/XML doc type.
			 */
			public function is_html_xml_doc($doc)
			{
				if(($doc = (string)$doc))
					if(stripos($doc, '</html>') !== FALSE || stripos($doc, '<?xml') === 0)
						return TRUE;
				return FALSE; // Not an HTML/XML document.
			}

			/**
			 * Does the current request have a cacheable content type?
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean `TRUE` if the current request has a cacheable content type.
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 *
			 * @warning Do NOT call upon this method until the end of a script execution.
			 */
			public function has_a_cacheable_content_type()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				foreach($this->headers_list() as $_header)
					if(stripos($_header, 'Content-Type:') === 0)
						$content_type = $_header; // Last one.
				unset($_header); // Just a little housekeeping.

				if(isset($content_type[0]) && stripos($content_type, 'html') === FALSE && stripos($content_type, 'xml') === FALSE && stripos($content_type, __NAMESPACE__) === FALSE)
					return (static::$static[__FUNCTION__] = FALSE); // Do NOT cache data sent by scripts serving other MIME types.

				return (static::$static[__FUNCTION__] = TRUE); // Assume that it is by default.
			}

			/**
			 * Does the current request have a cacheable HTTP status code?
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean `TRUE` if the current request has a cacheable HTTP status code.
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 *
			 * @warning Do NOT call upon this method until the end of a script execution.
			 */
			public function has_a_cacheable_status()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				if(($http_status = (string)$this->http_status()) && $http_status[0] !== '2' && $http_status !== '404')
					return (static::$static[__FUNCTION__] = FALSE); // A non-2xx & non-404 status code.

				foreach($this->headers_list() as $_header)
					if(preg_match('/^(?:Retry\-After\:\s+(?P<retry>.+)|Status\:\s+(?P<status>[0-9]+)|HTTP\/[0-9]+\.[0-9]+\s+(?P<http_status>[0-9]+))/i', $_header, $_m))
						if(!empty($_m['retry']) || (!empty($_m['status']) && $_m['status'][0] !== '2' && $_m['status'] !== '404')
						   || (!empty($_m['http_status']) && $_m['http_status'][0] !== '2' && $_m['http_status'] !== '404')
						) return (static::$static[__FUNCTION__] = FALSE); // Not a cacheable status.
				unset($_header); // Just a little housekeeping.

				return (static::$static[__FUNCTION__] = TRUE); // Assume that it is by default.
			}

			/* --------------------------------------------------------------------------------------
			 * IP address utilities.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Get the current visitor's real IP address.
			 *
			 * @return string Real IP address, else `unknown` on failure.
			 *
			 * @note This supports both IPv4 and IPv6 addresses.
			 * @note See my tests against this here: http://3v4l.org/fVWUp
			 */
			public function current_ip()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				static::$static[__FUNCTION__] = ''; // Initialize.
				$ip                           = &static::$static[__FUNCTION__];

				if(!empty($_SERVER['REMOTE_ADDR']) && $this->apply_filters(__METHOD__.'_prioritize_remote_addr', FALSE))
					if(($_valid_public_ip = $this->valid_public_ip($_SERVER['REMOTE_ADDR'])))
						return ($ip = $_valid_public_ip);

				$sources = array(
					'HTTP_CF_CONNECTING_IP',
					'HTTP_CLIENT_IP',
					'HTTP_X_FORWARDED_FOR',
					'HTTP_X_FORWARDED',
					'HTTP_X_CLUSTER_CLIENT_IP',
					'HTTP_FORWARDED_FOR',
					'HTTP_FORWARDED',
					'HTTP_VIA',
					'REMOTE_ADDR',
				);
				$sources = $this->apply_filters(__METHOD__.'_sources', $sources);

				foreach($sources as $_source) // Try each of these; in order.
				{
					if(!empty($_SERVER[$_source])) // Does the source key exist at all?
						if(($_valid_public_ip = $this->valid_public_ip($_SERVER[$_source])))
							return ($ip = $_valid_public_ip); // A valid public IPv4 or IPv6 address.
				}
				unset($_source, $_valid_public_ip); // Housekeeping.

				if(!empty($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR']))
					return ($ip = strtolower($_SERVER['REMOTE_ADDR']));

				return ($ip = 'unknown'); // Not possible.
			}

			/**
			 * Gets a valid/public IP address.
			 *
			 * @param string $list_of_possible_ips A single IP, or a comma-delimited list of IPs.
			 *
			 * @return string A valid/public IP address (if one is found), else an empty string.
			 *
			 * @note This supports both IPv4 and IPv6 addresses.
			 * @note See my tests against this here: http://3v4l.org/fVWUp
			 */
			public function valid_public_ip($list_of_possible_ips)
			{
				if(!$list_of_possible_ips || !is_string($list_of_possible_ips))
					return ''; // Empty or invalid data.

				if(!($list_of_possible_ips = trim($list_of_possible_ips)))
					return ''; // Not possible; i.e., empty string.

				foreach(preg_split('/[\s;,]+/', $list_of_possible_ips, NULL, PREG_SPLIT_NO_EMPTY) as $_possible_ip)
					if(($_valid_public_ip = filter_var(strtolower($_possible_ip), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)))
						return $_valid_public_ip; // A valid public IPv4 or IPv6 address.
				unset($_possible_ip, $_valid_public_ip); // Housekeeping.

				return ''; // Default return value.
			}

			/* --------------------------------------------------------------------------------------
			 * Function/extension utilities.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Checks if a PHP extension is loaded up.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string $extension A PHP extension slug (i.e. extension name).
			 *
			 * @return boolean `TRUE` if the extension is loaded.
			 *
			 * @note The return value of this function is cached to reduce overhead on repeat calls.
			 */
			public function is_extension_loaded($extension)
			{
				if(isset(static::$static[__FUNCTION__][$extension]))
					return static::$static[__FUNCTION__][$extension];
				return (static::$static[__FUNCTION__][$extension] = extension_loaded($extension));
			}

			/**
			 * Is a particular function possible in every way?
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string $function A PHP function (or user function) to check.
			 *
			 * @return string `TRUE` if the function is possible.
			 *
			 * @note This checks (among other things) if the function exists and that it's callable.
			 *    It also checks the currently configured `disable_functions` and `suhosin.executor.func.blacklist`.
			 */
			public function function_is_possible($function)
			{
				if(isset(static::$static[__FUNCTION__][$function]))
					return static::$static[__FUNCTION__][$function];

				if(isset(static::$static[__FUNCTION__]['___disabled_functions']))
					$disabled_functions =& static::$static[__FUNCTION__]['___disabled_functions'];

				else // We need to collect the disabled functions and cache them now.
				{
					static::$static[__FUNCTION__]['___disabled_functions'] = array(); // `$disabled_functions` =& reference.
					$disabled_functions                                    =& static::$static[__FUNCTION__]['___disabled_functions'];

					if(function_exists('ini_get')) // Only if {@link ini_get()} is possible itself.
					{
						if(($disable_functions = trim(ini_get('disable_functions'))))
							$disabled_functions = array_merge($disabled_functions, preg_split('/[\s;,]+/', strtolower($disable_functions), NULL, PREG_SPLIT_NO_EMPTY));

						if(($blacklist_functions = trim(ini_get('suhosin.executor.func.blacklist'))))
							$disabled_functions = array_merge($disabled_functions, preg_split('/[\s;,]+/', strtolower($blacklist_functions), NULL, PREG_SPLIT_NO_EMPTY));
					}
				}
				$possible = TRUE; // Assume it is.. (intialize).

				if(!function_exists($function) || !is_callable($function)
				   || ($disabled_functions && in_array(strtolower($function), $disabled_functions, TRUE))
				) $possible = FALSE; // Not possible.

				return (static::$static[__FUNCTION__][$function] = $possible);
			}

			/* --------------------------------------------------------------------------------------
			 * HTTP protocol/status utility methods.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Current HTTP protocol; i.e. `HTTP/1.0` or `HTTP/1.1`.
			 *
			 * @since 140725 Correcting 404 cache response status code.
			 *
			 * @return string Current HTTP protocol; i.e. `HTTP/1.0` or `HTTP/1.1`.
			 */
			public function http_protocol()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				$protocol = !empty($_SERVER['SERVER_PROTOCOL'])
					? strtoupper((string)$_SERVER['SERVER_PROTOCOL']) : 'HTTP/1.0';

				if($protocol !== 'HTTP/1.1' && $protocol !== 'HTTP/1.0')
					$protocol = 'HTTP/1.0'; // Default value.

				return (static::$static[__FUNCTION__] = $protocol);
			}

			/**
			 * An array of all headers sent via PHP; and the current HTTP status header too.
			 *
			 * @since 140725 Correcting 404 cache response status code.
			 *
			 * @return array PHP {@link headers_list()} supplemented with
			 *    HTTP status code when possible.
			 *
			 * @warning Do NOT call upon this method until the end of a script execution.
			 */
			public function headers_list()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				$headers_list = headers_list(); // Lacks HTTP status header.

				if(($http_status = (string)$this->http_status()))
					$headers_list[] = $this->http_protocol().' '.$http_status;

				return (static::$static[__FUNCTION__] = $headers_list);
			}

			/**
			 * An array of all cacheable/safe headers sent via PHP; and the current HTTP status header too.
			 *
			 * @since 140829 Correcting security issue related to headers with cookies.
			 *
			 * @return array PHP {@link headers_list()} supplemented with
			 *    HTTP status code when possible.
			 *
			 * @warning Do NOT call upon this method until the end of a script execution.
			 *
			 * @see http://www.websharks-inc.com/r/wikipedia-http-header-response-fields/
			 */
			public function cacheable_headers_list()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				$headers_list = headers_list(); // Lacks HTTP status header.

				$cacheable_headers = array(
					'Access-Control-Allow-Origin',
					'Accept-Ranges',
					'Age',
					'Allow',
					'Cache-Control',
					'Connection',
					'Content-Encoding',
					'Content-Language',
					'Content-Length',
					'Content-Location',
					'Content-MD5',
					'Content-Disposition',
					'Content-Range',
					'Content-Type',
					'Date',
					'ETag',
					'Expires',
					'Last-Modified',
					'Link',
					'Location',
					'P3P',
					'Pragma',
					'Proxy-Authenticate',
					'Refresh',
					'Retry-After',
					'Server',
					'Status',
					'Strict-Transport-Security',
					'Trailer',
					'Transfer-Encoding',
					'Upgrade',
					'Vary',
					'Via',
					'Warning',
					'WWW-Authenticate',
					'X-Frame-Options',
					'Public-Key-Pins',
					'X-XSS-Protection',
					'Content-Security-Policy',
					'X-Content-Security-Policy',
					'X-WebKit-CSP',
					'X-Content-Type-Options',
					'X-Powered-By',
					'X-UA-Compatible',
				);
				$cacheable_headers = array_map('strtolower', $cacheable_headers);

				foreach($headers_list as $_key => $_header)
				{
					$_header = strtolower((string)strstr($_header, ':', TRUE));
					if(!$_header || !in_array($_header, $cacheable_headers, TRUE))
						unset($headers_list[$_key]);
				}
				unset($_key, $_header); // Housekeeping.

				if(($http_status = (string)$this->http_status()))
					array_unshift($headers_list, $this->http_protocol().' '.$http_status);

				return (static::$static[__FUNCTION__] = $headers_list);
			}

			/**
			 * HTTP status code if at all possible.
			 *
			 * @since 140725 Correcting 404 cache response status code.
			 *
			 * @return integer HTTP status code if at all possible; else `0`.
			 *
			 * @warning Do NOT call upon this method until the end of a script execution.
			 *
			 * @note Calling this method will automatically update HTTP status-related flags.
			 */
			public function http_status()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				$http_status               = 0; // Initialize.
				$has_property__is_404      = property_exists($this, 'is_404');
				$has_property__http_status = property_exists($this, 'http_status');

				// Determine current HTTP status code.

				if($has_property__is_404 && $this->{'is_404'})
					$http_status = 404; // WordPress said so.

				else if($this->function_is_possible('http_response_code') && ($http_response_code = (integer)http_response_code()))
					$http_status = $http_response_code; // {@link \http_response_code()} available since PHP v5.4.

				else if($has_property__http_status && (integer)$this->{'http_status'})
					$http_status = (integer)$this->{'http_status'}; // {@link \status_header()} filter.

				// Dynamically update class property flags related to the HTTP status code.

				if($http_status && $has_property__http_status) // Update {@link $http_status}?
					$this->{'http_status'} = $http_status; // Prefer over {@link status_header()}.

				if($http_status === 404 && $has_property__is_404) // Update {@link $is_404}?
					$this->{'is_404'} = TRUE; // Prefer over {@link is_404()}.

				return (static::$static[__FUNCTION__] = $http_status);
			}

			/* --------------------------------------------------------------------------------------
			 * Misc. utility methods.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Escape single quotes.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string  $string Input string to escape.
			 * @param integer $times Optional. Defaults to one escape char; e.g. `\'`.
			 *    If you need to escape more than once, set this to something > `1`.
			 *
			 * @return string Escaped string; e.g. `Raam\'s the lead developer`.
			 */
			public function esc_sq($string, $times = 1)
			{
				return str_replace("'", str_repeat('\\', abs($times))."'", (string)$string);
			}

			/**
			 * String replace ONE time.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string  $needle A string to search/replace.
			 * @param string  $replace What to replace `$needle` with.
			 * @param string  $haystack The string/haystack to search in.
			 *
			 * @param boolean $caSe_insensitive Defaults to a `FALSE` value.
			 *    Pass this as `TRUE` to a caSe-insensitive search/replace.
			 *
			 * @return string The `$haystack`, with `$needle` replaced with `$replace` ONE time only.
			 */
			public function str_replace_once($needle, $replace, $haystack, $caSe_insensitive = FALSE)
			{
				$needle      = (string)$needle;
				$replace     = (string)$replace;
				$haystack    = (string)$haystack;
				$caSe_strpos = $caSe_insensitive ? 'stripos' : 'strpos';

				if(($needle_strpos = $caSe_strpos($haystack, $needle)) === FALSE)
					return $haystack; // Nothing to replace.

				return (string)substr_replace($haystack, $replace, $needle_strpos, strlen($needle));
			}

			/**
			 * String replace ONE time (caSe-insensitive).
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string $needle A string to search/replace.
			 * @param string $replace What to replace `$needle` with.
			 * @param string $haystack The string/haystack to search in.
			 *
			 * @return string The `$haystack`, with `$needle` replaced with `$replace` ONE time only.
			 */
			public function str_ireplace_once($needle, $replace, $haystack)
			{
				return $this->str_replace_once($needle, $replace, $haystack, TRUE);
			}

			/**
			 * Normalizes directory/file separators.
			 *
			 * @since 140829 Implementing XML/RSS feed clearing.
			 *
			 * @param string  $dir_file Directory/file path.
			 *
			 * @param boolean $allow_trailing_slash Defaults to FALSE.
			 *    If TRUE; and `$dir_file` contains a trailing slash; we'll leave it there.
			 *
			 * @return string Normalized directory/file path.
			 */
			public function n_dir_seps($dir_file, $allow_trailing_slash = FALSE)
			{
				$dir_file = (string)$dir_file; // Force string value.
				if(!isset($dir_file[0])) return ''; // Catch empty string.

				if(strpos($dir_file, '://' !== FALSE))  // A possible stream wrapper?
				{
					if(preg_match('/^(?P<stream_wrapper>[a-zA-Z0-9]+)\:\/\//', $dir_file, $stream_wrapper))
						$dir_file = preg_replace('/^(?P<stream_wrapper>[a-zA-Z0-9]+)\:\/\//', '', $dir_file);
				}
				if(strpos($dir_file, ':' !== FALSE))  // Might have a Windows® drive letter?
				{
					if(preg_match('/^(?P<drive_letter>[a-zA-Z])\:[\/\\\\]/', $dir_file)) // It has a Windows® drive letter?
						$dir_file = preg_replace_callback('/^(?P<drive_letter>[a-zA-Z])\:[\/\\\\]/', create_function('$m', 'return strtoupper($m[0]);'), $dir_file);
				}
				$dir_file = preg_replace('/\/+/', '/', str_replace(array(DIRECTORY_SEPARATOR, '\\', '/'), '/', $dir_file));
				$dir_file = ($allow_trailing_slash) ? $dir_file : rtrim($dir_file, '/'); // Strip trailing slashes.

				if(!empty($stream_wrapper[0])) // Stream wrapper (force lowercase).
					$dir_file = strtolower($stream_wrapper[0]).$dir_file;

				return $dir_file; // Normalized now.
			}

			/**
			 * Adds a tmp name suffix to a directory/file path.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string $dir_file An input directory or file path.
			 *
			 * @return string The original `$dir_file` with a tmp name suffix.
			 */
			public function add_tmp_suffix($dir_file)
			{
				return (string)rtrim($dir_file, DIRECTORY_SEPARATOR.'\\/').'-'.str_replace('.', '', uniqid('', TRUE)).'-tmp';
			}

			/**
			 * Acquires system tmp directory path.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @return string System tmp directory path; else an empty string.
			 */
			public function get_tmp_dir()
			{
				if(isset(static::$static[__FUNCTION__]))
					return static::$static[__FUNCTION__];

				static::$static[__FUNCTION__] = ''; // Initialize.
				$tmp_dir                      = &static::$static[__FUNCTION__];

				if(defined('WP_TEMP_DIR'))
					$possible_tmp_dirs[] = WP_TEMP_DIR;

				if($this->function_is_possible('sys_get_temp_dir'))
					$possible_tmp_dirs[] = sys_get_temp_dir();

				if($this->function_is_possible('ini_get'))
					$possible_tmp_dirs[] = ini_get('upload_tmp_dir');

				if(!empty($_SERVER['TEMP']))
					$possible_tmp_dirs[] = $_SERVER['TEMP'];

				if(!empty($_SERVER['TMPDIR']))
					$possible_tmp_dirs[] = $_SERVER['TMPDIR'];

				if(!empty($_SERVER['TMP']))
					$possible_tmp_dirs[] = $_SERVER['TMP'];

				if(stripos(PHP_OS, 'win') === 0)
					$possible_tmp_dirs[] = 'C:/Temp';

				if(stripos(PHP_OS, 'win') !== 0)
					$possible_tmp_dirs[] = '/tmp';

				if(defined('WP_CONTENT_DIR'))
					$possible_tmp_dirs[] = WP_CONTENT_DIR;

				if(!empty($possible_tmp_dirs)) foreach($possible_tmp_dirs as $_tmp_dir)
					if(($_tmp_dir = trim((string)$_tmp_dir)) && @is_dir($_tmp_dir) && @is_writable($_tmp_dir))
						return ($tmp_dir = $this->n_dir_seps($_tmp_dir));
				unset($_tmp_dir); // Housekeeping.

				return ($tmp_dir = ''); // Failed to locate.
			}

			/**
			 * Finds absolute server path to `/wp-config.php` file.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return string Absolute server path to `/wp-config.php` file;
			 *    else an empty string if unable to locate the file.
			 */
			public function find_wp_config_file()
			{
				if(is_file($abspath_wp_config = ABSPATH.'wp-config.php'))
					$wp_config_file = $abspath_wp_config;

				else if(is_file($dirname_abspath_wp_config = dirname(ABSPATH).'/wp-config.php'))
					$wp_config_file = $dirname_abspath_wp_config;

				else $wp_config_file = ''; // Unable to find `/wp-config.php` file.

				return $wp_config_file;
			}

			/* --------------------------------------------------------------------------------------
			 * File/directory iteration utilities for ZenCache.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Recursive directory iterator based on a regex pattern.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string $dir An absolute server directory path.
			 * @param string $regex A regex pattern; compares to each full file path.
			 *
			 * @return \RegexIterator Navigable with {@link \foreach()}; where each item
			 *    is a {@link \RecursiveDirectoryIterator}.
			 */
			public function dir_regex_iteration($dir, $regex)
			{
				$dir_iterator      = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::KEY_AS_PATHNAME | \FilesystemIterator::CURRENT_AS_SELF | \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::UNIX_PATHS);
				$iterator_iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::CHILD_FIRST);
				$regex_iterator    = new \RegexIterator($iterator_iterator, $regex, \RegexIterator::MATCH, \RegexIterator::USE_KEY);

				return $regex_iterator;
			}

			/**
			 * Clear files from the cache directory (for all hosts/blogs);
			 *    i.e. those that match a specific regex pattern.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string $regex A regex pattern; see {@link delete_files_from_cache_dir()}.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @see delete_files_from_cache_dir()
			 */
			public function clear_files_from_cache_dir($regex)
			{
				return $this->delete_files_from_cache_dir($regex);
			}

			/**
			 * Clear files from the cache directory (for the current host);
			 *    i.e. those that match a specific regex pattern.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string $regex A regex pattern; see {@link delete_files_from_host_cache_dir()}.
			 *
			 * @return integer Total files cleared by this routine (if any).
			 *
			 * @see delete_files_from_host_cache_dir()
			 */
			public function clear_files_from_host_cache_dir($regex)
			{
				return $this->delete_files_from_host_cache_dir($regex);
			}

			/**
			 * Purge files from the cache directory (for all hosts/blogs);
			 *    i.e. those that match a specific regex pattern.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string $regex A regex pattern; see {@link delete_files_from_cache_dir()}.
			 *
			 * @return integer Total files purged by this routine (if any).
			 *
			 * @see delete_files_from_cache_dir()
			 */
			public function purge_files_from_cache_dir($regex)
			{
				return $this->delete_files_from_cache_dir($regex, TRUE);
			}

			/**
			 * Purge files from the cache directory (for the current host);
			 *    i.e. those that match a specific regex pattern.
			 *
			 * @since 150218 Refactoring cache clear/purge routines.
			 *
			 * @param string $regex A regex pattern; see {@link delete_files_from_host_cache_dir()}.
			 *
			 * @return integer Total files purged by this routine (if any).
			 *
			 * @see delete_files_from_host_cache_dir()
			 */
			public function purge_files_from_host_cache_dir($regex)
			{
				return $this->delete_files_from_host_cache_dir($regex, TRUE);
			}

			/**
			 * Delete files from the cache directory (for all hosts/blogs);
			 *    i.e. those that match a specific regex pattern.
			 *
			 * @since 141110 Refactoring cache clear/purge routines.
			 *
			 * @param string  $regex A `/[regex pattern]/`; relative to the cache directory.
			 *    e.g. `/^http\/example\.com\/my\-slug(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])/`
			 *
			 *    Or, this can also be a full/absolute regex pattern against an absolute path;
			 *    provided that it always starts with `/^`; including the full absolute cache/host directory path.
			 *    e.g. `/^\/cache\/dir\/http\/example\.com\/my\-slug(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])/`
			 *
			 *    NOTE: Paths used in any/all regex patterns should be generated with {@link build_cache_path()}.
			 *       Recommended flags to {@link build_cache_path()} include the following.
			 *
			 *       - {@link CACHE_PATH_NO_PATH_INDEX}
			 *       - {@link CACHE_PATH_NO_QUV}
			 *       - {@link CACHE_PATH_NO_EXT}
			 *
			 *    **TIP:** There is a variation of {@link build_cache_path()} to assist with this.
			 *    Please see: {@link build_cache_path_regex()}. It is much easier to work with :-)
			 *
			 * @param boolean $check_max_age Check max age? i.e. use purge behavior?
			 *
			 * @return integer Total files deleted by this routine (if any).
			 *
			 * @throws \exception If unable to delete a file for any reason.
			 */
			public function delete_files_from_cache_dir($regex, $check_max_age = FALSE)
			{
				$counter = 0; // Initialize.

				if(!($regex = (string)$regex))
					return $counter; // Nothing to do.

				if(!is_dir($cache_dir = $this->cache_dir()))
					return $counter; // Nothing to do.

				$cache_dir = $this->n_dir_seps($cache_dir);

				if($check_max_age && (empty($this->options) || !is_array($this->options) || !isset($this->options['cache_max_age'])))
					throw new \exception(__('The `options` property w/ a `cache_max_age` key is not defined in this class.', 'zencache'));

				if($check_max_age && !($max_age = strtotime('-'.$this->options['cache_max_age'])))
					return $counter; // Invalid cache expiration time.

				/* ------- Begin lock state... ----------- */

				$cache_lock = $this->cache_lock(); // Lock cache writes.

				clearstatcache(); // Clear stat cache to be sure we have a fresh start below.

				$cache_dir_tmp       = $this->add_tmp_suffix($cache_dir); // Temporary directory.
				$cache_dir_tmp_regex = $regex; // Initialize host-specific regex pattern for the tmp directory.

				$cache_dir_tmp_regex = '\\/'.ltrim($cache_dir_tmp_regex, '^\\/'); // Make sure it begins with an escaped `/`.
				$cache_dir_tmp_regex = $this->str_ireplace_once(preg_quote($cache_dir.'/', '/'), '', $cache_dir_tmp_regex);

				$cache_dir_tmp_regex = ltrim($cache_dir_tmp_regex, '^\\/');
				if(strpos($cache_dir_tmp_regex, '(?:\/') === 0 || strpos($cache_dir_tmp_regex, '(\/') === 0)
					$cache_dir_tmp_regex = '/^'.preg_quote($cache_dir_tmp, '/').$cache_dir_tmp_regex;
				else $cache_dir_tmp_regex = '/^'.preg_quote($cache_dir_tmp.'/', '/').$cache_dir_tmp_regex;

				# if(WP_DEBUG) file_put_contents(WP_CONTENT_DIR.'/zc-debug.log', print_r($regex, TRUE)."\n".print_r($cache_dir_tmp_regex, TRUE)."\n\n", FILE_APPEND);
				// Uncomment the above line to debug regex pattern matching used by this routine; and others that call upon it.

				if(!rename($cache_dir, $cache_dir_tmp)) // Work from tmp directory so deletions are atomic.
					throw new \exception(sprintf(__('Unable to delete files. Rename failure on directory: `%1$s`.', 'zencache'), $cache_dir));

				/** @var $_resource \RecursiveDirectoryIterator Regex iterator reference for IDEs. */
				foreach(($_dir_regex_iteration = $this->dir_regex_iteration($cache_dir_tmp, $cache_dir_tmp_regex)) as $_resource)
				{
					$_resource_type = $_resource->getType();
					$_sub_path_name = $_resource->getSubpathname();
					$_path_name     = $_resource->getPathname();

					if($_resource_type !== 'dir' && strpos($_sub_path_name, '/') === FALSE)
						continue; // Don't delete links/files in the immediate directory; e.g. `zc-advanced-cache` or `.htaccess`, etc.
					// Actual `http|https/...` cache links/files are nested. Links/files in the immediate directory are for other purposes.

					switch($_resource_type) // Based on type; i.e. `link`, `file`, `dir`.
					{
						case 'link': // Symbolic links; i.e. 404 errors.

							if($check_max_age && !empty($max_age) && is_file($_resource->getLinkTarget()))
								if(($_lstat = lstat($_path_name)) && !empty($_lstat['mtime']))
									if($_lstat['mtime'] >= $max_age) // Still valid?
										break; // Break switch handler.

							if(!unlink($_path_name)) // Throw exception if unable to delete.
								throw new \exception(sprintf(__('Unable to delete symlink: `%1$s`.', 'zencache'), $_path_name));
							$counter++; // Increment counter for each link we delete.

							break; // Break switch handler.

						case 'file': // Regular files; i.e. not symlinks.

							if($check_max_age && !empty($max_age)) // Should check max age?
								if($_resource->getMTime() >= $max_age) // Still valid?
									break; // Break switch handler.

							if(!unlink($_path_name)) // Throw exception if unable to delete.
								throw new \exception(sprintf(__('Unable to delete file: `%1$s`.', 'zencache'), $_path_name));
							$counter++; // Increment counter for each file we delete.

							break; // Break switch handler.

						case 'dir': // A regular directory; i.e. not a symlink.

							if($regex !== '/^.+/i') // Deleting everything?
								break; // Break switch handler. Not deleting everything.

							if($check_max_age && !empty($max_age)) // Should check max age?
								break; // Break switch handler. Not deleting everything in this case.

							if(!rmdir($_path_name)) // Throw exception if unable to delete the directory itself.
								throw new \exception(sprintf(__('Unable to delete dir: `%1$s`.', 'zencache'), $_path_name));
							# $counter++; // Increment counter for each directory we delete. ~ NO don't do that here.

							break; // Break switch handler.

						default: // Something else that is totally unexpected here.
							throw new \exception(sprintf(__('Unexpected resource type: `%1$s`.', 'zencache'), $_resource_type));
					}
				}
				unset($_dir_regex_iteration, $_resource, $_resource_type, $_sub_path_name, $_path_name, $_lstat); // Housekeeping.

				if(!rename($cache_dir_tmp, $cache_dir)) // Deletions are atomic; restore original directory now.
					throw new \exception(sprintf(__('Unable to delete files. Rename failure on tmp directory: `%1$s`.', 'zencache'), $cache_dir_tmp));

				/* ------- End lock state... ------------- */

				$this->cache_unlock($cache_lock); // Unlock cache directory.

				return $counter; // Total files deleted by this routine.
			}

			/**
			 * Delete files from the cache directory (for the current host);
			 *    i.e. those that match a specific regex pattern.
			 *
			 * @since 141110 Refactoring cache clear/purge routines.
			 *
			 * @param string  $regex A `/[regex pattern]/`; relative to the host cache directory.
			 *    e.g. `/^my\-slug(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])/`
			 *
			 *    Or, this can also be a full/absolute regex pattern against an absolute path;
			 *    provided that it always starts with `/^`; including the full absolute cache/host directory path.
			 *    e.g. `/^\/cache\/dir\/http\/example\.com\/my\-slug(?:\/index)?(?:\.|\/(?:page\/[0-9]+|comment\-page\-[0-9]+)[.\/])/`
			 *
			 *    NOTE: Paths used in any/all regex patterns should be generated with {@link build_cache_path()}.
			 *       Recommended flags to {@link build_cache_path()} include the following.
			 *
			 *       - {@link CACHE_PATH_NO_SCHEME}
			 *       - {@link CACHE_PATH_NO_HOST}
			 *       - {@link CACHE_PATH_NO_PATH_INDEX}
			 *       - {@link CACHE_PATH_NO_QUV}
			 *       - {@link CACHE_PATH_NO_EXT}
			 *
			 *    **TIP:** There is a variation of {@link build_cache_path()} to assist with this.
			 *    Please see: {@link build_host_cache_path_regex()}. It is much easier to work with :-)
			 *
			 * @param boolean $check_max_age Check max age? i.e. use purge behavior?
			 *
			 * @return integer Total files deleted by this routine (if any).
			 *
			 * @throws \exception If unable to delete a file for any reason.
			 */
			public function delete_files_from_host_cache_dir($regex, $check_max_age = FALSE)
			{
				$counter = 0; // Initialize.

				if(!($regex = (string)$regex))
					return $counter; // Nothing to do.

				if(!is_dir($cache_dir = $this->cache_dir()))
					return $counter; // Nothing to do.

				$host                 = $_SERVER['HTTP_HOST'];
				$host_base_dir_tokens = $this->host_base_dir_tokens();
				$cache_dir            = $this->n_dir_seps($cache_dir);

				if($check_max_age && (empty($this->options) || !is_array($this->options) || !isset($this->options['cache_max_age'])))
					throw new \exception(__('The `options` property w/ a `cache_max_age` key is not defined in this class.', 'zencache'));

				if($check_max_age && !($max_age = strtotime('-'.$this->options['cache_max_age'])))
					return $counter; // Invalid cache expiration time.

				/* ------- Begin lock state... ----------- */

				$cache_lock = $this->cache_lock(); // Lock cache writes.

				clearstatcache(); // Clear stat cache to be sure we have a fresh start below.

				foreach(array('http', 'https') as $_host_scheme) // Consider `http|https` schemes.

					/* This multi-scheme iteration could (alternatively) be accomplished via regex `\/https?\/`.
						HOWEVER, since this operation is supposed to impact only a single host in a network, and because
						we want to do atomic deletions, we iterate and rename `$_host_cache_dir` for each scheme.

						It's also worth noting that most high traffic sites will not be in the habit of serving
						pages over SSL all the time; so this really should not have a significant performance hit.
						In fact, it may improve performance since we are traversing each sub-directory separately;
						i.e. we don't need to glob both `http` and `https` traffic into a single directory scan. */
				{
					$_host_url              = $_host_scheme.'://'.$host.$host_base_dir_tokens; // Base URL for this host|blog.
					$_host_cache_path_flags = $this::CACHE_PATH_NO_PATH_INDEX | $this::CACHE_PATH_NO_QUV | $this::CACHE_PATH_NO_EXT;
					$_host_cache_path       = $this->build_cache_path($_host_url, '', '', $_host_cache_path_flags);
					$_host_cache_dir        = $this->n_dir_seps($cache_dir.'/'.$_host_cache_path); // Normalize.

					if(!$_host_cache_dir || !is_dir($_host_cache_dir)) continue; // Nothing to do.

					$_host_cache_dir_tmp       = $this->add_tmp_suffix($_host_cache_dir); // Temporary directory.
					$_host_cache_dir_tmp_regex = $regex; // Initialize host-specific regex pattern for the tmp directory.

					$_host_cache_dir_tmp_regex = '\\/'.ltrim($_host_cache_dir_tmp_regex, '^\\/'); // Make sure it begins with an escaped `/`.
					$_host_cache_dir_tmp_regex = $this->str_ireplace_once(preg_quote($_host_cache_path.'/', '/'), '', $_host_cache_dir_tmp_regex);
					$_host_cache_dir_tmp_regex = $this->str_ireplace_once(preg_quote($_host_cache_dir.'/', '/'), '', $_host_cache_dir_tmp_regex);

					$_host_cache_dir_tmp_regex = ltrim($_host_cache_dir_tmp_regex, '^\\/');
					if(strpos($_host_cache_dir_tmp_regex, '(?:\/') === 0 || strpos($_host_cache_dir_tmp_regex, '(\/') === 0)
						$_host_cache_dir_tmp_regex = '/^'.preg_quote($_host_cache_dir_tmp, '/').$_host_cache_dir_tmp_regex;
					else $_host_cache_dir_tmp_regex = '/^'.preg_quote($_host_cache_dir_tmp.'/', '/').$_host_cache_dir_tmp_regex;

					# if(WP_DEBUG) file_put_contents(WP_CONTENT_DIR.'/zc-debug.log', print_r($regex, TRUE)."\n".print_r($_host_cache_dir_tmp_regex, TRUE)."\n\n", FILE_APPEND);
					// Uncomment the above line to debug regex pattern matching used by this routine; and others that call upon it.

					if(!rename($_host_cache_dir, $_host_cache_dir_tmp)) // Work from tmp directory so deletions are atomic.
						throw new \exception(sprintf(__('Unable to delete files. Rename failure on tmp directory: `%1$s`.', 'zencache'), $_host_cache_dir));

					/** @var $_file_dir \RecursiveDirectoryIterator Regex iterator reference for IDEs. */
					foreach(($_dir_regex_iteration = $this->dir_regex_iteration($_host_cache_dir_tmp, $_host_cache_dir_tmp_regex)) as $_resource)
					{
						$_resource_type = $_resource->getType();
						$_sub_path_name = $_resource->getSubpathname();
						$_path_name     = $_resource->getPathname();

						if($_host_cache_dir === $cache_dir && $_resource_type !== 'dir' && strpos($_sub_path_name, '/') === FALSE)
							continue; // Don't delete links/files in the immediate directory; e.g. `zc-advanced-cache` or `.htaccess`, etc.
						// Actual `http|https/...` cache links/files are nested. Links/files in the immediate directory are for other purposes.

						switch($_resource_type) // Based on type; i.e. `link`, `file`, `dir`.
						{
							case 'link': // Symbolic links; i.e. 404 errors.

								if($check_max_age && !empty($max_age) && is_file($_resource->getLinkTarget()))
									if(($_lstat = lstat($_path_name)) && !empty($_lstat['mtime']))
										if($_lstat['mtime'] >= $max_age) // Still valid?
											break; // Break switch handler.

								if(!unlink($_path_name)) // Throw exception if unable to delete.
									throw new \exception(sprintf(__('Unable to delete symlink: `%1$s`.', 'zencache'), $_path_name));
								$counter++; // Increment counter for each link we delete.

								break; // Break switch handler.

							case 'file': // Regular files; i.e. not symlinks.

								if($check_max_age && !empty($max_age)) // Should check max age?
									if($_resource->getMTime() >= $max_age) // Still valid?
										break; // Break switch handler.

								if(!unlink($_path_name)) // Throw exception if unable to delete.
									throw new \exception(sprintf(__('Unable to delete file: `%1$s`.', 'zencache'), $_path_name));
								$counter++; // Increment counter for each file we delete.

								break; // Break switch handler.

							case 'dir': // A regular directory; i.e. not a symlink.

								if($regex !== '/^.+/i') // Deleting everything?
									break; // Break switch handler. Not deleting everything.

								if($check_max_age && !empty($max_age)) // Should check max age?
									break; // Break switch handler. Not deleting everything in this case.

								if(!rmdir($_path_name)) // Throw exception if unable to delete the directory itself.
									throw new \exception(sprintf(__('Unable to delete dir: `%1$s`.', 'zencache'), $_path_name));
								# $counter++; // Increment counter for each directory we delete. ~ NO don't do that here.

								break; // Break switch handler.

							default: // Something else that is totally unexpected here.
								throw new \exception(sprintf(__('Unexpected resource type: `%1$s`.', 'zencache'), $_resource_type));
						}
					}
					unset($_dir_regex_iteration, $_resource, $_resource_type, $_sub_path_name, $_path_name, $_lstat); // Housekeeping.

					if(!rename($_host_cache_dir_tmp, $_host_cache_dir)) // Deletions are atomic; restore original directory now.
						throw new \exception(sprintf(__('Unable to delete files. Rename failure on tmp directory: `%1$s`.', 'zencache'), $_host_cache_dir_tmp));
				}
				unset($_host_scheme, $_host_url, $_host_cache_path_flags, $_host_cache_path,
					$_host_cache_dir, $_host_cache_dir_tmp, $_host_cache_dir_tmp_regex); // Housekeeping.

				/* ------- End lock state... ------------- */

				$this->cache_unlock($cache_lock); // Unlock cache directory.

				return $counter; // Total files deleted by this routine.
			}

			/**
			 * Delete all files/dirs from a directory (for all schemes/hosts);
			 *    including `zc-` prefixed files; or anything else for that matter.
			 *
			 * @since 141110 Refactoring cache clear/purge routines.
			 *
			 * @param string  $dir The directory from which to delete files/dirs.
			 *
			 *    SECURITY: This directory MUST be located inside the `/wp-content/` directory.
			 *    Also, it MUST be a sub-directory of `/wp-content/`, NOT the directory itself.
			 *    Also, it cannot be: `mu-plugins`, `themes`, or `plugins`.
			 *
			 * @param boolean $delete_dir_too Delete parent? i.e. delete the `$dir` itself also?
			 *
			 * @return integer Total files/directories deleted by this routine (if any).
			 *
			 * @throws \exception If unable to delete a file/directory for any reason.
			 */
			public function delete_all_files_dirs_in($dir, $delete_dir_too = FALSE)
			{
				$counter = 0; // Initialize.

				if(!($dir = trim((string)$dir)) || !is_dir($dir))
					return $counter; // Nothing to do.

				$dir                  = $this->n_dir_seps($dir); // Normalize separators.
				$dir_temp             = $this->add_tmp_suffix($dir); // Temporary directory.
				$wp_content_dir_regex = preg_quote($this->n_dir_seps(WP_CONTENT_DIR), '/');

				if(!preg_match('/^'.$wp_content_dir_regex.'\/[^\/]+/i', $dir))
					return $counter; // Security flag; do nothing in this case.

				if(preg_match('/^'.$wp_content_dir_regex.'\/(?:mu\-plugins|themes|plugins)(?:\/|$)/i', $dir))
					return $counter; // Security flag; do nothing in this case.

				/* ------- Begin lock state... ----------- */

				$cache_lock = $this->cache_lock(); // Lock cache writes.

				clearstatcache(); // Clear stat cache to be sure we have a fresh start below.

				if(!rename($dir, $dir_temp)) // Work from tmp directory so deletions are atomic.
					throw new \exception(sprintf(__('Unable to delete all files/dirs. Rename failure on tmp directory: `%1$s`.', 'zencache'), $dir));

				/** @var $_file_dir \RecursiveDirectoryIterator for IDEs. */
				foreach(($_dir_regex_iteration = $this->dir_regex_iteration($dir_temp, '/.+/')) as $_resource)
				{
					$_resource_type = $_resource->getType();
					$_sub_path_name = $_resource->getSubpathname();
					$_path_name     = $_resource->getPathname();

					switch($_resource_type) // Based on type; i.e. `link`, `file`, `dir`.
					{
						case 'link': // Symbolic links; i.e. 404 errors.

							if(!unlink($_path_name)) // Throw exception if unable to delete.
								throw new \exception(sprintf(__('Unable to delete symlink: `%1$s`.', 'zencache'), $_path_name));
							$counter++; // Increment counter for each link we delete.

							break; // Break switch handler.

						case 'file': // Regular files; i.e. not symlinks.

							if(!unlink($_path_name)) // Throw exception if unable to delete.
								throw new \exception(sprintf(__('Unable to delete file: `%1$s`.', 'zencache'), $_path_name));
							$counter++; // Increment counter for each file we delete.

							break; // Break switch handler.

						case 'dir': // A regular directory; i.e. not a symlink.

							if(!rmdir($_path_name)) // Throw exception if unable to delete the directory itself.
								throw new \exception(sprintf(__('Unable to delete dir: `%1$s`.', 'zencache'), $_path_name));
							$counter++; // Increment counter for each directory we delete.

							break; // Break switch handler.

						default: // Something else that is totally unexpected here.
							throw new \exception(sprintf(__('Unexpected resource type: `%1$s`.', 'zencache'), $_resource_type));
					}
				}
				unset($_dir_regex_iteration, $_resource, $_resource_type, $_sub_path_name, $_path_name); // Housekeeping.

				if(!rename($dir_temp, $dir)) // Deletions are atomic; restore original directory now.
					throw new \exception(sprintf(__('Unable to delete all files/dirs. Rename failure on tmp directory: `%1$s`.', 'zencache'), $dir_temp));

				if($delete_dir_too) // Delete parent? i.e. delete the `$dir` itself also?
				{
					if(!rmdir($dir)) // Throw exception if unable to delete.
						throw new \exception(sprintf(__('Unable to delete directory: `%1$s`.', 'zencache'), $dir));
					$counter++; // Increment counter for each directory we delete.
				}
				/* ------- End lock state... ------------- */

				$this->cache_unlock($cache_lock); // Unlock cache directory.

				return $counter; // Total files deleted by this routine.
			}

			/* --------------------------------------------------------------------------------------
			 * Cache locking utilities.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Get an exclusive lock on the cache directory.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return array Lock type & resource handle needed to unlock later or FALSE if disabled by filter.
			 *
			 * @throws \exception If {@link \sem_get()} not available and there's
			 *    no writable tmp directory for {@link \flock()} either.
			 *
			 * @throws \exception If unable to obtain an exclusive lock by any available means.
			 *
			 * @note This call is blocking; i.e. it will not return a lock until a lock becomes possible.
			 *    In short, this will block the caller until such time as write access becomes possible.
			 */
			public function cache_lock()
			{
				if($this->apply_wp_filters(__CLASS__.'_disable_cache_locking', FALSE))
					return FALSE;

				if(!($wp_config_file = $this->find_wp_config_file()))
					throw new \exception(__('Unable to find the wp-config.php file.', 'zencache'));

				$locking_method = $this->apply_wp_filters(__METHOD__.'_lock_type', 'flock');

				if(!in_array($locking_method, array('flock', 'sem')))
					$locking_method = 'flock';

				if($locking_method === 'sem')
					if($this->function_is_possible('sem_get'))
						if(($ipc_key = ftok($wp_config_file, 'w')))
							if(($resource = sem_get($ipc_key, 1)) && sem_acquire($resource))
								return array('type' => 'sem', 'resource' => $resource);

				// Use `flock()` as a decent fallback when `sem_get()` is not not forced or is not possible.

				if(!($tmp_dir = $this->get_tmp_dir()))
					throw new \exception(__('No writable tmp directory.', 'zencache'));

				$inode_key = fileinode($wp_config_file);
				$mutex     = $tmp_dir.'/'.$this->slug.'-'.$inode_key.'.lock';
				if(!($resource = fopen($mutex, 'w')) || !flock($resource, LOCK_EX))
					throw new \exception(__('Unable to obtain an exclusive lock.', 'zencache'));

				return array('type' => 'flock', 'resource' => $resource);
			}

			/**
			 * Release an exclusive lock on the cache directory.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param array $lock Type & resource that we are unlocking.
			 */
			public function cache_unlock(array $lock)
			{
				if($this->apply_wp_filters(__CLASS__.'_disable_cache_locking', FALSE))
					return;

				if(!is_array($lock))
					return; // Not possible.

				if(empty($lock['type']) || empty($lock['resource']))
					return; // Not possible.

				if(!is_resource($lock['resource']))
					return; // Not possible.

				if($lock['type'] === 'sem')
					sem_release($lock['resource']);

				else if($lock['type'] === 'flock')
				{
					flock($lock['resource'], LOCK_UN);
					fclose($lock['resource']);
				}
			}

			/* --------------------------------------------------------------------------------------
			 * Translation utilities for ZenCache.
			 -------------------------------------------------------------------------------------- */

			/**
			 * `X file` or `X files`, translated w/ singlular/plural context.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param integer $counter Total files; i.e. the counter.
			 *
			 * @return string The phrase `X file` or `X files`.
			 */
			public function i18n_files($counter)
			{
				$counter = (integer)$counter; // Force integer.

				return sprintf(_n('%1$s file', '%1$s files', $counter, 'zencache'), $counter);
			}

			/**
			 * `X directory` or `X directories`, translated w/ singlular/plural context.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param integer $counter Total directories; i.e. the counter.
			 *
			 * @return string The phrase `X directory` or `X directories`.
			 */
			public function i18n_dirs($counter)
			{
				$counter = (integer)$counter; // Force integer.

				return sprintf(_n('%1$s directory', '%1$s directories', $counter, 'zencache'), $counter);
			}

			/**
			 * `X file/directory` or `X files/directories`, translated w/ singlular/plural context.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param integer $counter Total files/directories; i.e. the counter.
			 *
			 * @return string The phrase `X file/directory` or `X files/directories`.
			 */
			public function i18n_files_dirs($counter)
			{
				$counter = (integer)$counter; // Force integer.

				return sprintf(_n('%1$s file/directory', '%1$s files/directories', $counter, 'zencache'), $counter);
			}

			/* --------------------------------------------------------------------------------------
			 * Hook/filter API for ZenCache.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Assigns an ID to each callable attached to a hook/filter.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string|callable|mixed $function A string or a callable.
			 *
			 * @return string Hook ID for the given `$function`.
			 *
			 * @throws \exception If the hook/function is invalid (i.e. it's not possible to generate an ID).
			 */
			public function hook_id($function)
			{
				if(is_string($function))
					return $function;

				if(is_object($function)) // Closure.
					$function = array($function, '');
				else $function = (array)$function;

				if(is_object($function[0]))
					return spl_object_hash($function[0]).$function[1];

				else if(is_string($function[0]))
					return $function[0].'::'.$function[1];

				throw new \exception(__('Invalid hook.', 'zencache'));
			}

			/**
			 * Adds a new hook (works with both actions & filters).
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string                $hook The name of a hook to attach to.
			 * @param string|callable|mixed $function A string or a callable.
			 * @param integer               $priority Hook priority; defaults to `10`.
			 * @param integer               $accepted_args Max number of args that should be passed to the `$function`.
			 *
			 * @return boolean This always returns a `TRUE` value.
			 */
			public function add_hook($hook, $function, $priority = 10, $accepted_args = 1)
			{
				$this->hooks[$hook][$priority][$this->hook_id($function)]
					= array('function' => $function, 'accepted_args' => (integer)$accepted_args);
				return TRUE; // Always returns true.
			}

			/**
			 * Adds a new action hook.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean This always returns a `TRUE` value.
			 *
			 * @see add_hook()
			 */
			public function add_action() // Simple `add_hook()` alias.
			{
				return call_user_func_array(array($this, 'add_hook'), func_get_args());
			}

			/**
			 * Adds a new filter.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean This always returns a `TRUE` value.
			 *
			 * @see add_hook()
			 */
			public function add_filter() // Simple `add_hook()` alias.
			{
				return call_user_func_array(array($this, 'add_hook'), func_get_args());
			}

			/**
			 * Removes a hook (works with both actions & filters).
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string                $hook The name of a hook to remove.
			 * @param string|callable|mixed $function A string or a callable.
			 * @param integer               $priority Hook priority; defaults to `10`.
			 *
			 * @return boolean `TRUE` if removed; else `FALSE` if not removed for any reason.
			 */
			public function remove_hook($hook, $function, $priority = 10)
			{
				if(!isset($this->hooks[$hook][$priority][$this->hook_id($function)]))
					return FALSE; // Nothing to remove in this case.

				unset($this->hooks[$hook][$priority][$this->hook_id($function)]);
				if(!$this->hooks[$hook][$priority]) unset($this->hooks[$hook][$priority]);
				return TRUE; // Existed before it was removed in this case.
			}

			/**
			 * Removes an action.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean `TRUE` if removed; else `FALSE` if not removed for any reason.
			 *
			 * @see remove_hook()
			 */
			public function remove_action() // Simple `remove_hook()` alias.
			{
				return call_user_func_array(array($this, 'remove_hook'), func_get_args());
			}

			/**
			 * Removes a filter.
			 *
			 * @since 140422 First documented version.
			 *
			 * @return boolean `TRUE` if removed; else `FALSE` if not removed for any reason.
			 *
			 * @see remove_hook()
			 */
			public function remove_filter() // Simple `remove_hook()` alias.
			{
				return call_user_func_array(array($this, 'remove_hook'), func_get_args());
			}

			/**
			 * Runs any callables attached to an action.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string $hook The name of an action hook.
			 */
			public function do_action($hook)
			{
				if(empty($this->hooks[$hook]))
					return; // No hooks.

				$hook_actions = $this->hooks[$hook];
				ksort($hook_actions); // Sort by priority.

				$args = func_get_args(); // We'll need these below.
				foreach($hook_actions as $_hook_action) foreach($_hook_action as $_action)
				{
					if(!isset($_action['function'], $_action['accepted_args']))
						continue; // Not a valid filter in this case.

					call_user_func_array($_action['function'], array_slice($args, 1, $_action['accepted_args']));
				}
				unset($_hook_action, $_action); // Housekeeping.
			}

			/**
			 * Runs any callables attached to a filter.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string $hook The name of a filter hook.
			 * @param mixed  $value The value to filter.
			 *
			 * @return mixed The filtered `$value`.
			 */
			public function apply_filters($hook, $value)
			{
				if(empty($this->hooks[$hook]))
					return $value; // No hooks.

				$hook_filters = $this->hooks[$hook];
				ksort($hook_filters); // Sort by priority.

				$args = func_get_args(); // We'll need these below.
				foreach($hook_filters as $_hook_filter) foreach($_hook_filter as $_filter)
				{
					if(!isset($_filter['function'], $_filter['accepted_args']))
						continue; // Not a valid filter in this case.

					$args[1] = $value; // Continously update the argument `$value`.
					$value   = call_user_func_array($_filter['function'], array_slice($args, 1, $_filter['accepted_args']));
				}
				unset($_hook_filter, $_filter); // Housekeeping.

				return $value; // With applied filters.
			}

			/**
			 * Does an action w/ back compat. for Quick Cache.
			 *
			 * @since 150218 First documented version.
			 *
			 * @param string $hook The hook to apply.
			 */
			public function do_wp_action($hook)
			{
				$hook = (string)$hook; // Force string value.
				$args = func_get_args(); // Including `$hook`.
				call_user_func_array('do_action', $args);

				if(stripos($hook, __NAMESPACE__) === 0) // Do Quick Cache back compat?
				{
					$quick_cache_filter  = 'quick_cache'.substr($hook, strlen(__NAMESPACE__));
					$quick_cache_args    = $args; // Use a copy of the args.
					$quick_cache_args[0] = $quick_cache_filter;

					call_user_func_array('do_action', $quick_cache_args);
				}
			}

			/**
			 * Applies filters w/ back compat. for Quick Cache.
			 *
			 * @since 150218 First documented version.
			 *
			 * @param string $hook The hook to apply.
			 *
			 * @return mixed The filtered value.
			 */
			public function apply_wp_filters($hook)
			{
				$hook  = (string)$hook; // Force string value.
				$args  = func_get_args(); // Including `$hook`.
				$value = call_user_func_array('apply_filters', $args);

				if(stripos($hook, __NAMESPACE__) === 0) // Do Quick Cache back compat?
				{
					$quick_cache_hook    = 'quick_cache'.substr($hook, strlen(__NAMESPACE__));
					$quick_cache_args    = $args; // Use a copy of the args.
					$quick_cache_args[0] = $quick_cache_hook;
					$quick_cache_args[1] = $value;

					$value = call_user_func_array('apply_filters', $quick_cache_args);
				}
				return $value; // Filtered value.
			}

			/* --------------------------------------------------------------------------------------
			 * Misc. long property values.
			 -------------------------------------------------------------------------------------- */

			/**
			 * Apache `.htaccess` rules that deny public access to the contents of a directory.
			 *
			 * @since 140422 First documented version.
			 *
			 * @var string `.htaccess` fules.
			 */
			public $htaccess_deny = "<IfModule authz_core_module>\n\tRequire all denied\n</IfModule>\n<IfModule !authz_core_module>\n\tdeny from all\n</IfModule>";
		}

		if(!function_exists('\\'.__NAMESPACE__.'\\__'))
		{
			/**
			 * Polyfill for {@link \__()}.
			 *
			 * @since 140422 First documented version.
			 *
			 * @param string $string String to translate.
			 * @param string $text_domain Plugin text domain.
			 *
			 * @return string Possibly translated string.
			 */
			function __($string, $text_domain)
			{
				static $exists; // Static cache.

				if($exists || ($exists = function_exists('__')))
					return \__($string, $text_domain);

				return $string; // Not possible (yet).
			}
		}
	}
}

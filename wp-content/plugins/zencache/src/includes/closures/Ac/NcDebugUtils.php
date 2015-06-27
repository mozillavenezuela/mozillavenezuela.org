<?php
namespace WebSharks\ZenCache;

/*
 * An array of debug info.
 *
 * @since 150422 Rewrite.
 *
 * @type array An array of debug info; i.e. `reason_code` and `reason` (optional).
 */
$self->debug_info = array('reason_code' => '', 'reason' => '');

/*
 * Used to setup debug info (if enabled).
 *
 * @since 150422 Rewrite.
 *
 * @param string $reason_code One of the `NC_DEBUG_` constants.
 * @param string $reason      Optionally override the built-in description with a custom message.
 */
$self->maybeSetDebugInfo = function ($reason_code, $reason = '') use ($self) {
    if (!ZENCACHE_DEBUGGING_ENABLE) {
        return; // Nothing to do.
    }
    $reason = (string) $reason;
    if (!($reason_code = (string) $reason_code)) {
        return; // Not applicable.
    }
    $self->debug_info = array('reason_code' => $reason_code, 'reason' => $reason);
};

/*
 * Echoes `NC_DEBUG_` info in the WordPress `shutdown` phase (if applicable).
 *
 * @since 150422 Rewrite.
 *
 * @attaches-to `shutdown` hook in WordPress w/ a late priority.
 */
$self->maybeEchoNcDebugInfo = function () use ($self) {
    if (!ZENCACHE_DEBUGGING_ENABLE) {
        return; // Nothing to do.
    }
    if (is_admin()) {
        return; // Not applicable.
    }
    if (strcasecmp(PHP_SAPI, 'cli') === 0) {
        return; // Let's not run the risk here.
    }
    if ($self->debug_info && $self->hasACacheableContentType() && $self->is_a_wp_content_type) {
        echo (string) $self->maybeGetNcDebugInfo($self->debug_info['reason_code'], $self->debug_info['reason']);
    }
};

/*
 * Gets `NC_DEBUG_` info (if applicable).
 *
 * @since 150422 Rewrite.
 *
 * @param string $reason_code One of the `NC_DEBUG_` constants.
 * @param string $reason      Optional; to override the default description with a custom message.
 *
 * @return string The debug info; i.e. full description (if applicable).
 */
$self->maybeGetNcDebugInfo = function ($reason_code = '', $reason = '') use ($self) {
    if (!ZENCACHE_DEBUGGING_ENABLE) {
        return ''; // Not applicable.
    }
    $reason = (string) $reason;
    if (!($reason_code = (string) $reason_code)) {
        return ''; // Not applicable.
    }
    if (!$reason) {
        switch ($reason_code) {
            case NC_DEBUG_PHP_SAPI_CLI:
                $reason = __('because `PHP_SAPI` reports that you are currently running from the command line.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_QCAC_GET_VAR:
                $reason = __('because `$_GET[\'zcAC\']` is set to a boolean-ish FALSE value.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_NO_SERVER_HTTP_HOST:
                $reason = __('because `$_SERVER[\'HTTP_HOST\']` is missing from your server configuration.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_NO_SERVER_REQUEST_URI:
                $reason = __('because `$_SERVER[\'REQUEST_URI\']` is missing from your server configuration.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_ZENCACHE_ALLOWED_CONSTANT:
                $reason = __('because the PHP constant `ZENCACHE_ALLOWED` has been set to a boolean-ish `FALSE` value at runtime. Perhaps by WordPress itself, or by one of your themes/plugins. This usually means that you have a theme/plugin intentionally disabling the cache on this page; and it\'s usually for a very good reason.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_ZENCACHE_ALLOWED_SERVER_VAR:
                $reason = __('because the environment variable `$_SERVER[\'ZENCACHE_ALLOWED\']` has been set to a boolean-ish `FALSE` value at runtime. Perhaps by WordPress itself, or by one of your themes/plugins. This usually means that you have a theme/plugin intentionally disabling the cache on this page; and it\'s usually for a very good reason.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_DONOTCACHEPAGE_CONSTANT:
                $reason = __('because the PHP constant `DONOTCACHEPAGE` has been set at runtime. Perhaps by WordPress itself, or by one of your themes/plugins. This usually means that you have a theme/plugin intentionally disabling the cache on this page; and it\'s usually for a very good reason.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_DONOTCACHEPAGE_SERVER_VAR:
                $reason = __('because the environment variable `$_SERVER[\'DONOTCACHEPAGE\']` has been set at runtime. Perhaps by WordPress itself, or by one of your themes/plugins. This usually means that you have a theme/plugin intentionally disabling the cache on this page; and it\'s usually for a very good reason.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_UNCACHEABLE_REQUEST:
                $reason = __('because `$_SERVER[\'REQUEST_METHOD\']` is `POST`, `PUT`, `DELETE`, `HEAD`, `OPTIONS`, `TRACE` or `CONNECT`. These request methods should never (ever) be cached in any way.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_SELF_SERVE_REQUEST:
                $reason = __('because `[current IP address]` === `$_SERVER[\'SERVER_ADDR\']`; i.e. a self-serve request. DEVELOPER TIP: if you are testing on a localhost installation, please add `define(\'LOCALHOST\', TRUE);` to your `/wp-config.php` file while you run tests :-) Remove it (or set it to a `FALSE` value) once you go live on the web.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_FEED_REQUEST:
                $reason = __('because `$_SERVER[\'REQUEST_URI\']` indicates this is a `/feed`; and the configuration of this site says not to cache XML-based feeds.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_WP_SYSTEMATICS:
                $reason = __('because `$_SERVER[\'REQUEST_URI\']` indicates this is a `wp-` or `xmlrpc` file; i.e. a WordPress systematic file. WordPress systematics are never (ever) cached in any way.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_WP_ADMIN:
                $reason = __('because `$_SERVER[\'REQUEST_URI\']` or the `is_admin()` function indicates this is an administrative area of the site.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_MS_FILES:
                $reason = __('because `$_SERVER[\'REQUEST_URI\']` indicates this is a Multisite Network; and this was a request for `/files/*`, not a page.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_IS_LOGGED_IN_USER:
            case NC_DEBUG_IS_LIKE_LOGGED_IN_USER:
                $reason = __('because the current user visiting this page (usually YOU), appears to be logged-in. The current configuration says NOT to cache pages for logged-in visitors. This message may also appear if you have an active PHP session on this site, or if you\'ve left (or replied to) a comment recently. If this message continues, please clear your cookies and try again.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_NO_USER_TOKEN:
                $reason = sprintf(__('because the current user appeared to be logged into the site (in one way or another); but %1$s was unable to formulate a User Token for them. Please report this as a possible bug.', SLUG_TD), NAME);
                break; // Break switch handler.

            case NC_DEBUG_GET_REQUEST_QUERIES:
                $reason = __('because `$_GET` contains query string data. The current configuration says NOT to cache GET requests with a query string.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_EXCLUDED_URIS:
                $reason = __('because `$_SERVER[\'REQUEST_URI\']` matches a configured URI Exclusion Pattern on this installation.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_EXCLUDED_AGENTS:
                $reason = __('because `$_SERVER[\'HTTP_USER_AGENT\']` matches a configured User-Agent Exclusion Pattern on this installation.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_EXCLUDED_REFS:
                $reason = __('because `$_SERVER[\'HTTP_REFERER\']` and/or `$_GET[\'_wp_http_referer\']` matches a configured HTTP Referrer Exclusion Pattern on this installation.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_404_REQUEST:
                $reason = __('because the WordPress `is_404()` Conditional Tag says the current page is a 404 error. The current configuration says NOT to cache 404 errors.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_MAINTENANCE_PLUGIN:
                $reason = __('because a plugin running on this installation says this page is in Maintenance Mode; i.e. is not available publicly at this time.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_OB_ZLIB_CODING_TYPE:
                $reason = sprintf(__('because %1$s is unable to cache already-compressed output. Please use `mod_deflate` w/ Apache; or use `zlib.output_compression` in your `php.ini` file. %1$s is NOT compatible with `ob_gzhandler()` and others like this.', SLUG_TD), NAME);
                break; // Break switch handler.

            case NC_DEBUG_WP_ERROR_PAGE:
                $reason = __('because the contents of this document contain `<body id="error-page">`, which indicates this is an auto-generated WordPress error message.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_UNCACHEABLE_CONTENT_TYPE:
                $reason = __('because a `Content-Type:` header was set via PHP at runtime. The header contains a MIME type which is NOT a variation of HTML or XML. This header might have been set by your hosting company, by WordPress itself; or by one of your themes/plugins.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_UNCACHEABLE_STATUS:
                $reason = __('because a `Status:` header (or an `HTTP/` header) was set via PHP at runtime. The header contains a non-`2xx` status code. This indicates the current page was not loaded successfully. This header might have been set by your hosting company, by WordPress itself; or by one of your themes/plugins.', SLUG_TD);
                break; // Break switch handler.

            case NC_DEBUG_1ST_TIME_404_SYMLINK:
                $reason = sprintf(__('because the WordPress `is_404()` Conditional Tag says the current page is a 404 error; and this is the first time it\'s happened on this page. Your current configuration says that 404 errors SHOULD be cached, so %1$s built a cached symlink which points future requests for this location to your already-cached 404 error document. If you reload this page (assuming you don\'t clear the cache before you do so); you should get a cached version of your 404 error document. This message occurs ONCE for each new/unique 404 error request.', SLUG_TD), NAME);
                break; // Break switch handler.

            case NC_DEBUG_EARLY_BUFFER_TERMINATION:
                $reason = sprintf(__('because %1$s detected an early output buffer termination. This may happen when a theme/plugin ends, cleans, or flushes all output buffers before reaching the PHP shutdown phase. It\'s not always a bad thing. Sometimes it is necessary for a theme/plugin to do this. However, in this scenario it is NOT possible to cache the output; since %1$s is effectively disabled at runtime when this occurs.', SLUG_TD), NAME);
                break; // Break switch handler.

            default: // Default case handler.
                $reason = __('due to an unexpected behavior in the application. Please report this as a bug!', SLUG_TD);
                break; // Break switch handler.
        }
    }
    return "\n".'<!-- '.htmlspecialchars(sprintf(__('%1$s is NOT caching this page, %2$s', SLUG_TD), NAME, $reason)).' -->';
};

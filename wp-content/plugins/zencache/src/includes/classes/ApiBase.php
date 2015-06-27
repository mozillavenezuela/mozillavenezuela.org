<?php
namespace WebSharks\ZenCache;

/**
 * API Base Class.
 *
 * @since 150422 Rewrite.
 */
class ApiBase
{
    /**
     * Current QC plugin instance.
     *
     * @since 150422 Rewrite.
     *
     * @return \zencache\plugin instance.
     */
    public static function plugin()
    {
        return $GLOBALS[GLOBAL_NS];
    }

    /**
     * Gives you the current version string.
     *
     * @since 150422 Rewrite.
     *
     * @return string Current version string.
     */
    public static function version()
    {
        return VERSION; // Via constant.
    }

    /**
     * Gives you the current array of configured options.
     *
     * @since 150422 Rewrite.
     *
     * @return array Current array of options.
     */
    public static function options()
    {
        return $GLOBALS[GLOBAL_NS]->options;
    }

    /**
     * Purges expired cache files, leaving all others intact.
     *
     * @since 150422 Rewrite.
     *
     * @note This occurs automatically over time via WP Cron;
     *    but this will force an immediate purge if you so desire.
     *
     * @return int Total files purged (if any).
     */
    public static function purge()
    {
        return $GLOBALS[GLOBAL_NS]->purgeCache();
    }

    /**
     * This erases the entire cache for the current blog.
     *
     * @since 150422 Rewrite.
     *
     * @note In a multisite network this impacts only the current blog,
     *    it does not clear the cache for other child blogs.
     *
     * @return int Total files cleared (if any).
     */
    public static function clear()
    {
        return $GLOBALS[GLOBAL_NS]->clearCache();
    }

    /**
     * This erases the cache for a specific post ID.
     *
     * @since 150626 Adding support for new API methods.
     *
     * @param int $post_id Post ID.
     *
     * @return int Total files cleared (if any).
     */
    public static function clearPost($post_id)
    {
        return $GLOBALS[GLOBAL_NS]->autoClearPostCache($post_id);
    }

    

    /**
     * This wipes out the entire cache.
     *
     * @since 150422 Rewrite.
     *
     * @note On a standard WP installation this is the same as zencache::clear();
     *    but on a multisite installation it impacts the entire network
     *    (i.e. wipes the cache for all blogs in the network).
     *
     * @return int Total files wiped (if any).
     */
    public static function wipe()
    {
        return $GLOBALS[GLOBAL_NS]->wipeCache();
    }
}

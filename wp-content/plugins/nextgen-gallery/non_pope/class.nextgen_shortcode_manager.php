<?php

class C_NextGen_Shortcode_Manager
{
	private static $_instance = NULL;
	private $_shortcodes = array();
    private $_runlevel = 0;
    private $_has_warned = FALSE;

	/**
	 * Gets an instance of the class
	 * @return C_NextGen_Shortcode_Manager
	 */
	static function get_instance()
	{
		if (is_null(self::$_instance)) {
			$klass = get_class();
			self::$_instance = new $klass;
		}
		return self::$_instance;
	}

	/**
	 * Adds a shortcode
	 * @param $name
	 * @param $callback
	 */
	static function add($name, $callback)
	{
		$manager = self::get_instance();
		$manager->add_shortcode($name, $callback);
	}

	/**
	 * Removes a previously added shortcode
	 * @param $name
	 */
	static function remove($name)
	{
		$manager = self::get_instance();
		$manager->remove_shortcode($name);
	}

	/**
	 * Constructor
	 */
	private function __construct()
	{
		add_filter('the_content', array(&$this, 'deactivate_all'), -(PHP_INT_MAX-1));
		add_filter('the_content', array(&$this, 'parse_content'), PHP_INT_MAX-1);
	}

	/**
	 * Deactivates all shortcodes
	 */
	function deactivate_all($content)
	{
        // There is a bug in Wordpress itself: when a hook recurses any hooks meant to execute after it are discarded.
        // For example the following code, despite expectations, will NOT display 'bar' as bar() is never executed.
        // See https://core.trac.wordpress.org/ticket/17817 for more information.
        /* function foo() {
         *     remove_action('foo', 'foo');
         * }
         * function bar() {
         *     echo('bar');
         * }
         * add_action('foo', 'foo');
         * add_action('foo', 'bar');
         * do_action('foo');
         */
        $this->_runlevel += 1;
        if ($this->_runlevel > 1 && defined('WP_DEBUG') && WP_DEBUG && !is_admin() && !$this->_has_warned)
        {
            $this->_has_warned = TRUE;
            error_log('Sorry, but recursing filters on "the_content" breaks NextGEN Gallery. Please see https://core.trac.wordpress.org/ticket/17817');
        }

		foreach (array_keys($this->_shortcodes) as $shortcode) {
			$this->deactivate($shortcode);
		}

		return $content;
	}

	/**
	 * Activates all registered shortcodes
	 */
	function activate_all()
	{
		foreach (array_keys($this->_shortcodes) as $shortcode) {
			$this->activate($shortcode);
		}
	}

	/**
	 * Parses the content for shortcodes and returns the substituted content
	 * @param $content
	 * @return string
	 */
	function parse_content($content)
	{
        $this->_runlevel--;
		$this->activate_all();
		$content = do_shortcode($content);
        $content = apply_filters('ngg_content', $content);

        return $content;
	}

	/**
	 * Adds a shortcode
	 * @param $name
	 * @param $callback
	 */
	function add_shortcode($name, $callback)
	{
		$this->_shortcodes[$name] = $callback;
		$this->activate($name);
	}

	/**
	 * Activates a particular shortcode
	 * @param $shortcode
	 */
	function activate($shortcode)
	{
		if (isset($this->_shortcodes[$shortcode])) {
			add_shortcode($shortcode, $this->_shortcodes[$shortcode]);
		}
	}

	/**
	 * Removes a shortcode
	 * @param $name
	 */
	function remove_shortcode($name)
	{
		unset($this->_shortcodes[$name]);
		$this->deactivate($name);
	}

	/**
	 * De-activates a shortcode
	 * @param $shortcode
	 */
	function deactivate($shortcode)
	{
		if (isset($this->_shortcodes[$shortcode]))
			remove_shortcode($shortcode);
	}
}
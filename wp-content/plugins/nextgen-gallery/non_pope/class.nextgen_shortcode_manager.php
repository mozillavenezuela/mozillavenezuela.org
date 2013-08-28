<?php

class C_NextGen_Shortcode_Manager
{
	private static $_instance = NULL;
	private $_shortcodes = array();

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
		add_filter('the_content', array(&$this, 'deactivate_all'), 1);
		add_filter('the_content', array(&$this, 'parse_content'), PHP_INT_MAX-1);
	}

	/**
	 * Deactivates all shortcodes
	 */
	function deactivate_all($content)
	{
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
		$this->activate_all();
		return do_shortcode($content);
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
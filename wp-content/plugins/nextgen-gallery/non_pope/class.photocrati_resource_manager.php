<?php

class C_Photocrati_Resource_Manager
{
	static $instance = NULL;
	var $buffer = '';
	var $styles = '';
	var $scripts = '';
	var $other_output = '';
	var $wrote_footer = FALSE;


	/**
	 * Start buffering all generated output. We'll then do two things with the buffer
	 * 1) Find stylesheets lately enqueued and move them to the header
	 * 2) Ensure that wp_print_footer_scripts() is called
	 */
	function __construct()
	{
		// We use this everywhere EXCEPT on wp-admin/update
		if (!strpos($_SERVER['REQUEST_URI'], 'wp-admin/update')) {
			ob_start(array(&$this, 'get_buffer'));
			add_action('shutdown', array(&$this, 'output_buffer'));
			add_action('wp_print_footer_scripts', array(&$this, 'get_resources'), 1);
			add_action('admin_print_footer_scripts', array(&$this, 'get_resources'), 1);
		}
	}

	/**
	 *
	 **/
	function get_resources()
	{
		ob_start();
		wp_print_styles();
		print_admin_styles();
		$this->styles = ob_get_clean();

		if (!is_admin()) {
			ob_start();
			wp_print_scripts();
			$this->scripts = ob_get_clean();
		}

		$this->wrote_footer = TRUE;
	}

	/**
	 * Removes the closing </html> tag from the output buffer. We'll then write our own closing tag
	 * in the shutdown function after running wp_print_footer_scripts()
	 * @param $content
	 * @return mixed
	 */
	function get_buffer($content)
	{
		$this->buffer = $content;
		return '';
	}

	/**
	 * Moves resources to their appropriate place
	 */
	function move_resources()
	{
		// Move stylesheets to head
		if ($this->styles) {
			$this->buffer = str_ireplace('</head>', $this->styles.'</head>', $this->buffer);
		}

		// Move the scripts to the bottom of the page
		if ($this->scripts) {
			$this->buffer = str_ireplace('</body>', $this->scripts.'</body>', $this->buffer);
		}

		if ($this->other_output) {
			$this->buffer = str_replace('</body>', $this->other_output.'</body>', $this->buffer);
		}
	}

	function print_footer_scripts_if_missing()
	{
		if (!$this->wrote_footer) {
			ob_start();
			wp_print_footer_scripts();
			$this->other_output = ob_get_clean();
		}
	}


	/**
	 * When PHP has finished, we output the footer scripts and closing tags
	 */
	function output_buffer()
	{
		// Ensure that footer scripts are always generated
		$this->print_footer_scripts_if_missing();

		// Move resources
		$this->move_resources();

		// Output the buffer
		echo $this->buffer;
	}

	static function init()
	{
		$klass = get_class();
		return self::$instance = new $klass;
	}
}
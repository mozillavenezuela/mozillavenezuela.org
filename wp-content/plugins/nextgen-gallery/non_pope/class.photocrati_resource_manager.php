<?php

class C_Photocrati_Resource_Manager
{
	static $instance = NULL;

	var $buffer = '';
	var $styles = '';
	var $scripts = '';
	var $other_output = '';
	var $wrote_footer = FALSE;
	var $run_shutdown = FALSE;

	/**
	 * Start buffering all generated output. We'll then do two things with the buffer
	 * 1) Find stylesheets lately enqueued and move them to the header
	 * 2) Ensure that wp_print_footer_scripts() is called
	 */
	function __construct()
	{
		// Add default request exceptions
		add_filter('run_ngg_resource_manager', array(&$this, 'is_valid_request'));

		// Check if we should process this request
		if (apply_filters('run_ngg_resource_manager', TRUE)) {
			add_action('init',array(&$this, 'start_buffer'), 1);
			add_action('wp_print_footer_scripts', array(&$this, 'get_resources'), 1);
			add_action('admin_print_footer_scripts', array(&$this, 'get_resources'), 1);
			add_action('shutdown', array(&$this, 'shutdown'));
		}
	}

	/**
	 * Determines if the resource manager should perform it's routines for this request
	 * @param $retval
	 * @return bool
	 */
	function is_valid_request($retval)
	{
		if (is_admin()) {
			if (isset($_REQUEST['page']) && !preg_match("#^(ngg|nextgen)#", $_REQUEST['page'])) $retval = FALSE;
		}

		if (strpos($_SERVER['REQUEST_URI'], 'wp-admin/update') !== FALSE) $retval = FALSE;
		else if (isset($_GET['display_gallery_iframe'])) $retval = FALSE;

		return $retval;
	}

	/**
	 * Start the output buffers
	 */
	function start_buffer()
	{
		ob_start(array(&$this, 'output_buffer_handler'));
		ob_start(array(&$this, 'get_buffer'));
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
	
	
	function output_buffer_handler($content)
	{
		return $this->output_buffer();
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

	/**
	 * When PHP has finished, we output the footer scripts and closing tags
	 */
	function output_buffer($in_shutdown=FALSE)
	{
		// If the footer scripts haven't been outputted, then
		// we need to take action - as they're required
		if (!$this->wrote_footer) {

			// If W3TC is installed and activated, we can't output the
			// scripts and manipulate the buffer, so we can only provide a warning
			if (defined('W3TC')) {
				define('DONOTCACHEPAGE', TRUE);
				if (!did_action('wp_footer')) {
					error_log("We're sorry, but your theme's page template didn't make a call to wp_footer(), which is required by NextGEN Gallery. Please add this call to your page templates.");
				}
				else {
					error_log("We're sorry, but your theme's page template didn't make a call to wp_print_footer_scripts(), which is required by NextGEN Gallery. Please add this call to your page templates.");
				}
			}

			// The output_buffer() function has been called in the PHP shutdown callback
			// This will allow us to print the scripts ourselves and manipulate the buffer
			if ($in_shutdown === TRUE) {
				ob_start();
				if (!did_action('wp_footer')) {
					wp_footer();
				}
				else {
					wp_print_footer_scripts();
				}
				$this->other_output = ob_get_clean();

			}

			// W3TC isn't activated and we're not in the shutdown callback.
			// We'll therefore add a shutdown callback to print the scripts
			else {
				$this->run_shutdown = TRUE;
				return '';
			}
		}

		// Once we have the footer scripts, we can modify the buffer and
		// move the resources around
		if ($this->wrote_footer) $this->move_resources();

		return $this->buffer;
	}

	/**
	 * PHP shutdown callback. Manipulate and output the buffer
	 */
	function shutdown()
	{
		if ($this->run_shutdown) echo $this->output_buffer(TRUE);
	}

	static function init()
	{
		$klass = get_class();
		return self::$instance = new $klass;
	}
}

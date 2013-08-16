<?php

class C_Ajax_Controller extends C_MVC_Controller
{
	static $_instances = array();

	function define($context=FALSE)
	{
		parent::define($context);
		$this->implement('I_Ajax_Controller');
	}

	function index_action()
	{
		$retval = FALSE;
        $error_reporting = error_reporting(
            E_CORE_ERROR|E_CORE_WARNING|E_COMPILE_ERROR|E_ERROR|E_PARSE|E_USER_ERROR|E_USER_WARNING|E_RECOVERABLE_ERROR
        );

		// Inform the MVC framework what type of content we're returning
		$this->set_content_type('json');

		// Get the action requested & find and execute the related method
		if (($action = $this->param('action'))) {
			$method = "{$action}_action";
			if ($this->has_method($method)) {
				$retval = $this->call_method($method);
			}
		}

		// If no retval has been set, then return an error
		if (!$retval)
			$retval = array('error' => 'Not a valid AJAX action');

		// Return the JSON to the browser
		echo json_encode($retval);

        // reset the reporting level
        error_reporting($error_reporting);
	}

	/**
	 * Returns an instance of this class
	 * @param string $context
	 * @return C_Ajax_Controller
	 */
	static function get_instance($context=FALSE)
	{
		if (!isset(self::$_instances[$context])) {
			$klass = get_class();
			self::$_instances[$context] = new $klass($context);
		}
		return self::$_instances[$context];
	}
}

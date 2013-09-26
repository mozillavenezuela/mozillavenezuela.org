<?php
class Mixin_Routing_App extends Mixin
{
    /**
     * Creates a new route endpoint with the assigned handler
     *
     * @param array $routes URL to route, eg /page/{page}/
     * @param array $handler Formatted array
     */
    function route($routes, $handler)
    {
        // ensure that the routing patterns array exists
        if (!is_array($this->object->_routing_patterns))
            $this->object->_routing_patterns = array();

        if (!is_array($routes))
            $routes = array($routes);

        // fetch all routing patterns
        $patterns = $this->object->_routing_patterns;

        foreach ($routes as $route) {
            // add the routing pattern
            $patterns[$this->object->_route_to_regex($route)] = $handler;
        }

        // update routing patterns
        $this->object->_routing_patterns = $patterns;
    }

    /**
     * Handles internal url rewriting with optional HTTP redirection,
     *
     * @param string $src Original URL
     * @param string $dst Destination URL
     * @param bool $redirect FALSE for internal handling, otherwise the HTTP code to send
     */
    function rewrite($src, $dst, $redirect = FALSE, $stop=FALSE)
    {
        // ensure that rewrite patterns array exists
        if (!is_array($this->object->_rewrite_patterns))
            $this->object->_rewrite_patterns = array();

        // fetch all rewrite patterns
        $patterns = $this->object->_rewrite_patterns;

		// Assign rewrite definition
		$definition = array(
			'dst' => $dst, 'redirect' => $redirect, 'stop'	=> $stop
		);

		// We treat wildcards much differently then normal rewrites
		if (preg_match("/\\{[\.\\\*]/", $src)) {
			$pattern  = str_replace('{*}',	'(.*?)',  $src);
			$pattern  = str_replace('{.*}', '(.*?)',	 $pattern);
			$pattern  = str_replace('{\\w}', '([\\w-_]*)', $pattern);
			$pattern  = str_replace('{\\d}', '(\\d*)', $pattern);
			$src = '#'.(strpos($src, '/') === 0 ? '^':'').$pattern.'/?$#';
			$definition['wildcards'] = TRUE;
		}

		// Normal rewrite
		else $src = $this->object->_route_to_regex($src);

        // add the rewrite pattern
        $patterns[$src] = $definition;

        // update rewrite patterns;
        $this->object->_rewrite_patterns = $patterns;
    }

	/**
	 * Gets an instance of the router
	 * @return type
	 */
	function get_router()
	{
		return $this->object->get_registry()->get_utility('I_Router');
	}

	function get_app_url($request_uri=FALSE, $with_qs=FALSE)
	{
		return $this->object->get_router()->get_url($this->object->get_app_uri($request_uri), $with_qs);
	}


	function get_routed_url($with_qs=TRUE)
	{
		return $this->object->get_app_url(FALSE, $with_qs);
	}

	function get_app_uri($request_uri=FALSE)
	{
		if (!$request_uri) $request_uri = $this->object->get_app_request_uri();
		return $this->object->join_paths(
			$this->object->context,
			$request_uri
		);
	}

	function get_app_request_uri()
	{
		$retval = FALSE;

		if ($this->object->_request_uri) $retval = $this->object->_request_uri;
		else if (($retval = $this->object->does_app_serve_request())) {
			if (strpos($retval, '/') !== 0) $retval = '/'.$retval;
			$this->object->set_app_request_uri($retval);
		}

		return $retval;
	}

	/**
	 * Sets the application request uri
	 * @param type $uri
	 */
	function set_app_request_uri($uri)
	{
		$this->object->_request_uri = $uri;
	}

	/**
	 * Gets the application's routing regex pattern
	 * @return string
	 */
	function get_app_routing_pattern()
	{
		return $this->object->_route_to_regex($this->object->context);
	}


	/**
	 * Determines whether this app serves the request
	 * @return boolean|string
	 */
	function does_app_serve_request()
	{
		$retval = FALSE;

		$request_uri = $this->object->get_router()->get_request_uri(TRUE);

		// Is the context present in the uri?
		if (($index = strpos($request_uri, $this->object->context)) !== FALSE) {
			$starts_with_slash = strpos($this->object->context, '/') === 0;
			if (($starts_with_slash && $index === 0) OR (!$starts_with_slash)) {
				$regex = implode('', array(
					'#',
					($starts_with_slash ? '^':''),
					preg_quote($this->object->context, '#'),
					'#'
				));
				$retval = preg_replace($regex, '', $request_uri);
				if (!$retval) $retval = '/';
				if (strpos($retval, '/') !== 0) $retval = '/'.$retval;
				if (substr($retval, -1) != '/') $retval = $retval.'/';
			}
		}

		return $retval;
	}

	/**
	 * Performs the url rewriting routines. Returns the HTTP status code used to
	 * redirect, if we're to do so. Otherwise FALSE
	 * @return int|bool
	 */
	function do_rewrites($request_uri=FALSE)
	{
		$redirect = FALSE;

		// Get the request uri if not provided
		if (!$request_uri) $request_uri = $this->object->get_app_request_uri();

		// ensure that rewrite patterns array exists
        if (!is_array($this->object->_rewrite_patterns))
            $this->object->_rewrite_patterns = array();

		// Process each rewrite rule
		// start rewriting urls
		foreach ($this->object->_rewrite_patterns as $pattern => $details) {

			// Remove this pattern from future processing for this request
			unset($this->object->_rewrite_patterns[$pattern]);

			// Wildcards are processed much differently
			if (isset($details['wildcards']) && $details['wildcards']) {
				if (preg_match($pattern, $request_uri, $matches)) {
					foreach ($matches as $index => $match) {
						if ($index == 0) {
							$request_uri = str_replace($match, $details['dst'], $request_uri);
							continue;
						}
						$request_uri = str_replace(
							"{{$index}}", $match, $request_uri
						);
					}

					// Set the redirect flag if we're to do so
					if (isset($details['redirect']) && $details['redirect']) {
						$redirect = $details['redirect'] === TRUE ?
							302 : intval($details['redirect']);
						break;
					}

					// Stop processing rewrite patterns?
					if ($details['stop']) break;
				}
			}

			// Normal rewrite pattern
			elseif (preg_match_all($pattern, $request_uri, $matches, PREG_SET_ORDER))
			{
				// Assign new request URI
				$request_uri = $details['dst'];

				// Substitute placeholders
				foreach ($matches as $match) {
					if ($redirect) break;
					foreach ($match as $key => $val) {

						// If we have a placeholder that needs swapped, swap
						// it now
						if (is_numeric($key)) continue;
						$request_uri = str_replace("{{$key}}", $val, $request_uri);
					}
					// Set the redirect flag if we're to do so
					if (isset($details['redirect']) && $details['redirect']) {
						$redirect = $details['redirect'] === TRUE ?
							302 : intval($details['redirect']);
						break;
					}

				}
			}
		}

		// Cache all known data about the application request
		$this->object->set_app_request_uri($request_uri);
		$this->object->get_router()->set_routed_app($this->object);

		return $redirect;
	}


    /**
     * Determines if the current routing app meets our requirements and serves them
     *
     * @return bool
     */
    function serve_request()
    {
        $served = FALSE;

        // ensure that the routing patterns array exists
        if (!is_array($this->object->_routing_patterns))
            $this->object->_routing_patterns = array();

        // if the application root matches, then we'll try to route the request
        if (($request_uri = $this->object->get_app_request_uri())){

			// Perform URL rewrites
			$redirect = $this->object->do_rewrites($request_uri);;

			// Are we to perform a redirect?
			if ($redirect) {
				$this->object->execute_route_handler(
					$this->object->parse_route_handler($redirect)
				);
			}

			// Handle routed endpoints
			else {
				foreach ($this->object->_routing_patterns as $pattern => $handler) {
					if (preg_match($pattern, $this->object->get_app_request_uri(), $matches)) {
						$served = TRUE;

						// Add placeholder parameters
						foreach ($matches as $key => $value) {
							if (is_numeric($key)) continue;
							$this->object->set_parameter_value($key, $value, NULL);
						}

						// If a handler is attached to the route, execute it. A
						// handler can be
						// - FALSE, meaning don't do any post-processing to the route
						// - A string, such as controller#action
						// - An array: array(
						//   'controller' => 'I_Test_Controller',
						//   'action'	  => 'index',
						//   'context'	  => 'all', (optional)
						//   'method'	  => array('GET') (optional)
						// )
						if ($handler && $handler = $this->object->parse_route_handler($handler)) {
							// Is this handler for the current HTTP request method?
							if (isset($handler['method'])) {
								if (!is_array($handler['method'])) $handler['$method'] = array($handler['method']);
								if (in_array($this->object->get_router()->get_request_method(), $handler['method'])) {
									$this->object->execute_route_handler($handler);
								}
							}

							// This handler is for all request methods
							else {
								$this->object->execute_route_handler($handler);
							}
						}
						else if (!$handler) {
							$this->object->passthru();
						}
					}
				}
			}
        }

        return $served;
    }

	/**
	 * Executes an action of a particular controller
	 * @param array $handler
	 */
	function execute_route_handler($handler)
	{
		// Get action
		$action = $handler['action'];

		// Get controller
		$controller = $this->object->get_registry()->get_utility(
			$handler['controller'], $handler['context']
		);

		// Call action
		$controller->$action();

		// Clean Exit (fastcgi safe)
		C_NextGEN_Bootstrap::shutdown();
	}

	/**
	 * Parses the route handler
	 * @param mixed $handler
	 * @return array
	 */
	function parse_route_handler($handler)
	{
		if (is_string($handler)) {
			$handler = array_combine(array('controller', 'action'), explode('#', $handler));
		}
		elseif (is_numeric($handler)) {
			$handler = array(
				'controller'	=>	'I_Http_Response',
				'action'		=>	'http_'.$handler,
			);
		}
		if (!isset($handler['context'])) $handler['context'] = FALSE;
		if (strpos($handler['action'], '_action') === FALSE) $handler['action'] .= '_action';

		return $handler;
	}


	function add_placeholder_params_from_matches($matches)
	{
		// Add the placeholder parameter values to the _params array
		foreach ($matches as $key => $value) {
			if (is_numeric($key)) continue;
			$this->object->add_placeholder_param(
				$key, $value, $matches[0]
			);
		}
	}

	/**
	 * Used to pass execution to PHP and perhaps an above framework
	 */
	function passthru()
	{
	}


	/**
	 * Adds a placeholder parameter
	 * @param string $name
	 * @param stirng $value
	 * @param string $source
	 */
	function add_placeholder_param($name, $value, $source=NULL)
	{
		if (!is_array($this->object->_parameters)) {
			$this->object->_parameters = array('global');
		}
		if (!isset($this->object->_parameters['global'])) {
			$this->object->_parameters['global'] = array();
		}
		$this->object->_parameters['global'][] = array(
			'id'	=>	'',
			'name'	=>	$name,
			'value'	=>	$value,
			'source'=>	$source
		);
	}

    /**
     * Converts the route to the regex
     *
     * @param string $route
     * @return string
     */
    function _route_to_regex($route)
    {
		// Get the settings manager
		$settings = $this->object->_settings;
		$param_slug = $settings->router_param_slug;

        // convert route to RegEx pattern
        $route_regex = preg_quote(
            str_replace(
                array('{', '}'),
                array('~', '~'),
                $route
            ), '#'
        );

		// Wrap the route
		$route_regex = '('.$route_regex.')';

		// If the route starts with a slash, then it must appear at the beginning
		// of a request uri
		if (strpos($route, '/') === 0) $route_regex = '^'.$route_regex;

		// If the route is not /, and perhaps /foo, then we need to optionally
		// look for a trailing slash as well
		if ($route != '/') $route_regex .= '/?';

		// If parameters come after a slug, it might appear as well
		if ($param_slug) {
			$route_regex .= "(".preg_quote($param_slug, '#').'/)?';
		}

		// Parameter might follow the request uri
		$route_regex .= "(/?([^/]+\-\-)?[^/]+\-\-[^/]+/?){0,}";

		// Create the regex
        $route_regex = '#' . $route_regex . '/?$#i';

        // convert placeholders to regex as well
        return preg_replace('/~([^~]+)~/i', ($param_slug ? '('.preg_quote($param_slug,'#').'\K)?' : '').'(?<\1>[^/]+)/?', $route_regex);
    }

	/**
	 * Gets a request parameter from either the request uri or querystring
	 * This method takes into consideration the values of the router_param_prefix
	 * and router_param_separator settings when searching for the parameter
	 *
	 * Parameter can take on the following forms:
	 * /key--value
	 * /[MVC_PARAM_PREFIX]key--value
	 * /[MVC_PARAM_PREFIX]-key--value
	 * /[MVC_PARAM_PREFIX]_key--value
	 * /id--key--value
	 * /id--[MVC_PARAM_PREFIX]key--value
	 * /id--[MVC_PARAM_PREFIX]-key--value
	 * /id--[MVC_PARAM_PREFIX]_key--value
	 *
	 * @param string $key
	 * @param mixed $id
	 * @param mixed $default
	 * @return mixed
	 */
	function get_parameter($key, $id=NULL, $default=NULL, $segment=FALSE, $url=FALSE)
	{
		$retval				= $default;
		$settings			= $this->object->_settings;
		$quoted_key			= preg_quote($key,'#');
		$id					= $id ? preg_quote($id,'#') : "[^/]+";
		$param_prefix		= preg_quote($settings->router_param_prefix,'#');
		$param_sep			= preg_quote($settings->router_param_separator,'#');
		$param_regex		= "#/((?P<id>{$id}){$param_sep})?({$param_prefix}[-_]?)?{$quoted_key}{$param_sep}(?P<value>[^/\?]+)/?#i";
		$found				= FALSE;
		$sources			= $url ? array('custom' => $url) : $this->object->get_parameter_sources();

		foreach ($sources as $source_name => $source) {
			if (preg_match($param_regex, $source, $matches)) {
				if ($segment)
					$retval = array('segment' => $matches[0], 'source' => $source_name);
				else
                    $retval = $matches['value'];
				$found = TRUE;
				break;
			}
		}

		// Lastly, check the $_REQUEST
		if (!$found && !$url && isset($_REQUEST[$key]))
            $retval = $this->object->recursive_stripslashes($_REQUEST[$key]);

		return $retval;
	}

	/**
	 * Sets the value of a particular parameter
	 * @param string $key
	 * @param mixed $value
	 * @param mixed $id
	 */
	function set_parameter_value($key, $value, $id=NULL, $use_prefix=FALSE, $url=FALSE)
	{
		// Remove the parameter from both the querystring and request uri
		$retval		= $this->object->remove_parameter($key, $id, $url);

		// Get the settings manager
		$settings	= $this->object->_settings;
		$param_slug = $settings->router_param_slug;

		// We're modifying a url passed in
		if ($url) {
			$parts = parse_url($retval);
			if (!isset($parts['path'])) $parts['path'] = '';
			$parts['path'] = $this->object->join_paths(
				$parts['path'],
				$param_slug && strpos($retval, $param_slug) === FALSE ?
					$param_slug : '',
				$this->object->create_parameter_segment($key, $value, $id, $use_prefix)
			);
			$retval = $this->object->construct_url_from_parts($parts);
		}

		// We're modifying the current request
		else {
			// This parameter is being appended to the current request uri
			$this->object->add_parameter_to_app_request_uri($key, $value, $id, $use_prefix);

			// Return the new full url
			$retval = $this->object->get_routed_url();
		}

		return trailingslashit($retval);
	}

	/**
	 * Alias for remove_parameter()
	 * @param string $key
	 * @param mixed $id
	 * @return string
	 */
	function remove_param($key, $id=NULL, $url=FALSE)
	{
		return $this->object->remove_parameter($key, $id, $url);
	}

	/**
	 * Removes a parameter from the querystring and application request URI
	 * and returns the full application URL
	 * @param string $key
	 * @param mixed $id
	 * @return string
	 */
	function remove_parameter($key, $id=NULL, $url=FALSE)
	{
		$retval			= $url;
		$settings		= $this->object->_settings;
		$param_sep		= $settings->router_param_separator;
		$param_prefix	= $settings->router_param_prefix ? preg_quote($settings->router_param_prefix, '#') : '';
		$param_slug		= $settings->router_param_slug ? preg_quote($settings->router_param_slug, '#') : FALSE;

		// Is the parameter already part of the request? If so, modify that
		// parmaeter
		if (($segment = $this->object->get_parameter_segment($key, $id, $url))) {
 			extract($segment);

			if ($source == 'querystring') {
				$preg_id	= $id ? '\d+' : preg_quote($id,'#');
				$preg_key	= preg_quote($key, '#');
				$regex = implode('', array(
					'#',
					$id ? "{$preg_id}{$param_sep}" : '',
					"(({$param_prefix})?[-_]?)?{$preg_key}({$param_sep}|=)[^\/&]+&?#i"
				));
				$qs = preg_replace($regex, '', $this->get_router()->get_querystring());
				$this->object->get_router()->set_querystring($qs);
				$retval = $this->object->get_routed_url();
			}
			elseif ($source == 'request_uri') {
				$uri = $this->object->get_app_request_uri();
				$uri = $this->object->join_paths(explode($segment, $uri));
				if ($settings->router_param_slug && preg_match("#{$param_slug}/?$#i", $uri, $match)) {
					$retval = $this->object->remove_url_segment($match[0], $retval);
				}
				$this->object->set_app_request_uri($uri);
				$retval = $this->object->get_routed_url();
			}
			else {
				$retval = $this->object->join_paths(explode($segment, $url));
				if ($settings->router_param_slug && preg_match("#/{$param_slug}$#i", $retval, $match)) {
					$retval = $this->object->remove_url_segment($match[0], $retval);
				}
			}
		}

        $retval = rtrim($retval, ' ?&');

		return $retval;
	}


	/**
	 * Adds a parameter to the application's request URI
	 * @param string $key
	 * @param mixed $value
	 * @param mixed $id
	 */
	function add_parameter_to_app_request_uri($key, $value, $id=NULL, $use_prefix=FALSE)
	{
		$settings	= $this->object->_settings;
		$param_slug = $settings->router_param_slug;
		
		$uri		= $this->object->get_app_request_uri();
		$parts		= array($uri);
		if ($param_slug && strpos($uri, $param_slug) === FALSE) $parts[] = $param_slug;
		$parts[]	= $this->object->create_parameter_segment($key, $value, $id, $use_prefix);
		$this->object->set_app_request_uri($this->object->join_paths($parts));

		return $this->object->get_app_request_uri();
	}


	/**
	 * Creates a parameter segment
	 * @param string $key
	 * @param mixed $value
	 * @param mixed $id
	 * @return string
	 */
	function create_parameter_segment($key, $value, $id=NULL, $use_prefix=FALSE)
	{
		$settings	= $this->object->_settings;
		if ($use_prefix) $key = $settings->router_param_prefix.$key;
		if ($value === TRUE) $value = 1;
		elseif ($value == FALSE) $value = 0; // null and false values
		$retval = $key . $settings->router_param_separator . $value;
		if ($id) $retval = $id . $settings->router_param_separator . $retval;
		return $retval;
	}

	/**
	 * Alias for set_parameter_value
	 * @param string $key
	 * @param mixed $value
	 * @param mixed $id
	 */
	function set_parameter($key, $value, $id=NULL, $use_prefix=FALSE, $url=FALSE)
	{
		return $this->object->set_parameter_value($key, $value, $id, $use_prefix, $url);
	}

	/**
	 * Alias for set_parameter_value
	 * @param string $key
	 * @param mixed $value
	 * @param mixed $id
	 */
	function set_param($key, $value, $id=NULL, $use_prefix=FALSE, $url=FALSE)
	{
		return $this->object->set_parameter_value($key, $value, $id, $use_prefix=FALSE, $url);
	}

	/**
	 * Gets a parameter's value
	 * @param string $key
	 * @param mixed $id
	 * @param mixed $default
	 * @return mixed
	 */
	function get_parameter_value($key, $id=NULL, $default=NULL, $url=FALSE)
	{
		return $this->object->get_parameter($key, $id, $default, FALSE, $url);
	}

	/**
	 * Gets a parameter's matching URI segment
	 * @param string $key
	 * @param mixed $id
	 * @param mixed $default
	 * @return mixed
	 */
	function get_parameter_segment($key, $id=NULL, $url=FALSE)
	{
		return $this->object->get_parameter($key, $id, NULL, TRUE, $url);
	}

	/**
	 * Gets sources used for parsing and extracting parameters
	 * @return array
	 */
	function get_parameter_sources()
	{
		return array(
			'querystring'	=>	$this->object->get_formatted_querystring(),
			'request_uri'	=>	$this->object->get_app_request_uri(),
			'postdata'		=>	$this->object->get_postdata()
		);
	}

	function get_postdata()
	{
		$retval		= '/' . urldecode(file_get_contents("php://input"));
		$settings	= $this->object->_settings;
		$retval = str_replace(
			array('&', '='),
			array('/', $settings->router_param_separator),
			$retval
		);

		return $retval;
	}


	function get_formatted_querystring()
	{
		$retval		= '/'.$this->object->get_router()->get_querystring();
		$settings	= $this->object->_settings;
		$retval		= str_replace(
			array('&', '='),
			array('/', $settings->router_param_separator),
			$retval
		);

		return $retval;
	}

	function has_parameter_segments()
	{
		$retval			= FALSE;
		$settings		= $this->object->_settings;
		$request_uri	= $this->object->get_app_request_uri();
		$sep			= preg_quote($settings->router_param_separator,'#');

		// If we detect the MVC_PARAM_SLUG, then we assume that we have parameters
		if ($settings->router_param_slug && strpos($request_uri, '/'.$settings->router_param_slug) !== FALSE) {
			$retval = TRUE;
		}

		// If the above didn't pass, then we try finding parameters in our
		// desired format
		if (!$retval) {
			$regex			= implode('', array(
				'#',
				$settings->router_param_slug ? '/'.preg_quote($settings->router_param_slug,'#').'/?' : '',
				"(/?([^/]+{$sep})?[^/]+{$sep}[^/]+/?){0,}",
				'$#'
			));
			$retval = preg_match($regex, $request_uri);
		}

		return $retval;
	}

    /**
     * Recursively calls stripslashes() on strings, arrays, and objects
     *
     * @param mixed $value Value to be processed
     * @return mixed Resulting value
     */
    function recursive_stripslashes($value)
    {
        if (is_string($value))
        {
            $value = stripslashes($value);
        }
        elseif (is_array($value)) {
            foreach ($value as &$tmp) {
                $tmp = $this->object->recursive_stripslashes($tmp);
            }
        }
        elseif (is_object($value))
        {
            foreach (get_object_vars($value) as $key => $data) {
                $value->{$key} = recursive_stripslashes($data);
            }
        }

        return $value;
    }
}

class C_Routing_App extends C_Component
{
    static $_instances		= array();
	var    $_request_uri	= FALSE;
	var $_settings = null;

    function define($context= FALSE)
    {
        parent::define($context);
		$this->add_mixin('Mixin_Url_Manipulation');
        $this->add_mixin('Mixin_Routing_App');
		$this->implement('I_Routing_App');
    }
    
    function initialize()
    {
        parent::initialize();
		$this->_settings = $this->object->get_routing_settings();
    }
    
    function get_routing_settings()
    {
        $settings	= C_NextGen_Settings::get_instance();
        $object = new stdClass();
        $object->router_param_separator = $settings->router_param_separator;
        $object->router_param_slug = $settings->router_param_slug;
        $object->router_param_prefix = $settings->router_param_prefix;

        return $object;
    }

    static function &get_instance($context = False)
    {
        if (!isset(self::$_instances[$context])) {
            self::$_instances[$context] = new C_Routing_App($context);
        }
        return self::$_instances[$context];
    }
}

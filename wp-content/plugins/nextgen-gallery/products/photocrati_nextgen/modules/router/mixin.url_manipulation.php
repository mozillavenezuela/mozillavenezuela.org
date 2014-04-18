<?php

class Mixin_Url_Manipulation extends Mixin
{
	function join_paths()
	{
		$args = func_get_args();
        $parts = $this->_flatten_array($args);
        foreach ($parts as &$part) {
            $part = trim(str_replace("\\", '/', $part), "/");
        }
        return implode('/', $parts);
	}

	/**
	 * Removes a segment from a url
	 * @param string $segment
	 * @param string $url
	 * @return string
	 */
	function remove_url_segment($segment, $url)
	{
		$retval = $url;
		$parts	= parse_url($url);

		// If the url has a path, then we can remove a segment
		if (isset($parts['path']) && $segment != '/') {
			if (substr($segment, -1) == '/') $segment = substr($segment, -1);
			$segment = preg_quote($segment, '#');
			if (preg_match("#{$segment}#", $parts['path'], $matches)) {
				$parts['path'] = str_replace(
					'//',
					'/',
					str_replace($matches[0], '', $parts['path'])
				);
				$retval = $this->object->construct_url_from_parts($parts);
			}
		}
		return $retval;
	}


    /**
     * Flattens an array of arrays to a single array
     * @param array $array
     * @param array $parent (optional)
     * @param bool $exclude_duplicates (optional - defaults to TRUE)
     * @return array
     */
    function _flatten_array($array, $parent=NULL, $exclude_duplicates=TRUE)
    {
        if (is_array($array)) {

            // We're to add each element to the parent array
            if ($parent) {
                foreach ($array as $index => $element) {
                    foreach ($this->_flatten_array($array) as $sub_element) {
                        if ($exclude_duplicates) {
                            if (!in_array($sub_element, $parent)) {
                                $parent[] = $sub_element;
                            }
                        }
                        else $parent[] = $sub_element;
                    }
                }
                $array = $parent;
            }

            // We're starting the process..
            else {
                $index = 0;
                while (isset($array[$index])) {
                    $element = $array[$index];
                    if (is_array($element)) {
                        $array = $this->_flatten_array($element, $array);
                        unset($array[$index]);
                    }
                    $index += 1;
                }
                $array = array_values($array);
            }
        }
        else {
            $array = array($array);
        }

        return $array;
    }


	function join_querystrings()
	{
		$parts	= array();
		$retval = array();
		$params = func_get_args();
		$parts = $this->_flatten_array($params);
		foreach ($parts as $part) {
            $part = explode("&", $part);
            foreach ($part as $segment) {
                $segment = explode("=", $segment);
                $key = $segment[0];
                $value = isset($segment[1]) ? $segment[1] : '';
                $retval[$key] = $value;

            }
		}
		return $this->object->assoc_array_to_querystring($retval);
	}

    function assoc_array_to_querystring($arr)
    {
        $retval = array();
        foreach ($arr as $key => $val) {
            if (strlen($key))
                $retval[] = strlen($val) ? "{$key}={$val}" : $key;
        }
        return implode("&", $retval);
    }


	/**
	 * Constructs a url from individual parts, created by parse_url
	 * @param array $parts
	 * @return string
	 */
	function construct_url_from_parts($parts)
	{
        // let relative paths be relative, and full paths full
        $prefix = '';
        if (!empty($parts['scheme']) && !empty($parts['host'])) {
            $prefix = $parts['scheme'] . '://' . $parts['host'];
            if (!empty($parts['port']))
                $prefix .= ':' . $parts['port'];
        }

		$retval =  $this->object->join_paths(
            $prefix,
			isset($parts['path']) ? str_replace('//', '/', trailingslashit($parts['path'])) : ''
		);

		if (isset($parts['query']) && $parts['query']) $retval .= untrailingslashit("?{$parts['query']}");

		return $retval;
	}

	function get_parameter_segments($request_uri)
	{
		return str_replace($this->strip_param_segments($request_uri), '', $request_uri);
	}

	/**
	 * Returns the request uri with the parameter segments stripped
	 * @param string $request_uri
	 * @return string
	 */
	function strip_param_segments($request_uri, $remove_slug=TRUE)
	{
		$retval		 = $request_uri ? $request_uri : '/';
		$settings	 = C_NextGen_Settings::get_instance();
		$sep		 = preg_quote($settings->router_param_separator, '#');
		$param_regex = "#((?P<id>\w+){$sep})?(?<key>\w+){$sep}(?P<value>.+)/?$#";
		$slug		 = $settings->router_param_slug && $remove_slug ? '/' . preg_quote($settings->router_param_slug,'#') : '';
		$slug_regex	 = '#'.$slug.'/?$#';

		// Remove all parameters
		while (@preg_match($param_regex, $retval, $matches)) {
			$match_regex = '#'.preg_quote(array_shift($matches),'#').'$#';
			$retval = preg_replace($match_regex, '', $retval);
		}

		// Remove the slug or trailing slash
		if (@preg_match($slug_regex, $retval, $matches)) {
			$match_regex = '#'.preg_quote(array_shift($matches),'#').'$#';
			$retval = preg_replace($match_regex, '', $retval);
		}

		// If there's a slug, we can assume everything after is a parameter,
		// even if it's not in our desired format.
		$retval = preg_replace('#'.$slug.'.*$#', '', $retval);

		if (!$retval) $retval = '/';

		return $retval;
	}
}

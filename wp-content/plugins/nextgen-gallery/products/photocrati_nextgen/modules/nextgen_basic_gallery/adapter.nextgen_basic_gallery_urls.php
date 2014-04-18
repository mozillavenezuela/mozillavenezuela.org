<?php

class A_NextGen_Basic_Gallery_Urls extends Mixin
{
    function initialize()
	{
		$this->object->add_post_hook(
			'set_parameter_value',
			get_class(),
			get_class(),
			'_set_nextgen_basic_thumbnail_parameter'
		);
		$this->object->add_post_hook(
			'remove_parameter',
			get_class(),
			get_class(),
			'_remove_nextgen_basic_thumbnail_parameter'
		);

	}
    
    
    function create_parameter_segment($key, $value, $id=NULL, $use_prefix=FALSE)
	{
		if ($key == 'show') {
            if ($value == NGG_BASIC_SLIDESHOW) $value = 'slideshow';
            elseif ($value == NGG_BASIC_THUMBNAILS) $value = 'thumbnails';
            elseif ($value == NGG_BASIC_IMAGEBROWSER) $value = 'imagebrowser';
            return $value;
        }
        elseif ($key == 'nggpage') {
			return 'page/'.$value;
		}
		else
			return $this->call_parent('create_parameter_segment', $key, $value, $id, $use_prefix);

	}
    
    
    function _set_nextgen_basic_thumbnail_parameter($key, $value, $id=NULL, $use_prefix=NULL)
	{
		$this->_set_ngglegacy_page_parameter($key, $value, $id, $use_prefix);
	}


	function _remove_nextgen_basic_thumbnail_parameter($key, $id=NULL, $url=FALSE)
	{
		$this->_set_ngglegacy_page_parameter($key);
        
	}


	function _set_ngglegacy_page_parameter($key, $value=NULL, $id=NULL, $use_prefix=NULL)
	{
		// Get the returned url
		$retval		= $this->object->get_method_property(
			$this->method_called, ExtensibleObject::METHOD_PROPERTY_RETURN_VALUE
		);

        // Get the settings manager
        $settings	= C_NextGen_Settings::get_instance();

        // Create regex pattern
        $param_slug = preg_quote($settings->router_param_slug, '#');

        if ($key == 'nggpage') {
            $regex = "#(/{$param_slug}/.*)(/?page/\\d+/?)(.*)#";
            if (preg_match($regex, $retval, $matches)) {
                $new_segment = $value ? "/page/{$value}" : "";
                $retval = rtrim(str_replace(
                    $matches[0],
                    rtrim($matches[1], "/").$new_segment.ltrim($matches[3], "/"),
                    $retval
                ), "/");

                // Set new return value
                $this->object->set_method_property(
                    $this->method_called,
                    ExtensibleObject::METHOD_PROPERTY_RETURN_VALUE,
                    $retval
                );
            }
        }

        # Convert the nggpage parameter to a slug
        if (preg_match("#(/{$param_slug}/.*)nggpage--(.*)#", $retval, $matches)) {
            $retval = rtrim(str_replace($matches[0], rtrim($matches[1],"/") ."/page/".ltrim($matches[2], "/"), $retval), "/");

            // Set new return value
            $this->object->set_method_property(
                $this->method_called,
                ExtensibleObject::METHOD_PROPERTY_RETURN_VALUE,
                $retval
            );
        }

        # Convert the show parameter to a slug
        if (preg_match("#(/{$param_slug}/.*)show--(.*)#", $retval, $matches)) {
            $retval = rtrim(str_replace($matches[0], rtrim($matches[1], "/").'/'.$matches[2], $retval), "/");
            $retval = str_replace(NGG_BASIC_SLIDESHOW, 'slideshow', $retval);
            $retval = str_replace(NGG_BASIC_THUMBNAILS, 'thumbnails', $retval);
            $retval = str_replace(NGG_BASIC_IMAGEBROWSER, 'imagebrowser', $retval);

            // Set new return value
            $this->object->set_method_property(
                $this->method_called,
                ExtensibleObject::METHOD_PROPERTY_RETURN_VALUE,
                $retval
            );
        }

		return $retval;
	}
}
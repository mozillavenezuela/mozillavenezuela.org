<?php

class A_NextGen_Basic_TagCloud_Urls extends Mixin
{
	function initialize()
	{
		$this->object->add_post_hook(
			'set_parameter_value',
			get_class(),
			get_class(),
			'_set_tag_cloud_parameter'
		);
		$this->object->add_post_hook(
			'remove_parameter',
			get_class(),
			get_class(),
			'_remove_tag_cloud_parameter'
		);
	}


	function create_parameter_segment($key, $value, $id, $use_prefix)
	{
		if ($key == 'gallerytag') {
			return 'tags/'.$value;
		}
		else return $this->call_parent('create_parameter_segment', $key, $value, $id, $use_prefix);
	}


	function _set_tag_cloud_parameter($key, $value, $id=NULL, $use_prefix=NULL)
	{
		$this->_set_tag_cloud_parameters($key, $id);
	}


	function _remove_tag_cloud_parameter($key, $id=NULL, $use_prefix=NULL)
	{
		$this->_set_tag_cloud_parameters($key, $id);
	}


	function _set_tag_cloud_parameters($key, $id=NULL)
	{
		// Get the returned url
		$retval		= $this->object->get_method_property(
			$this->method_called, ExtensibleObject::METHOD_PROPERTY_RETURN_VALUE
		);

		// Get the settings manager
		$settings	= C_NextGen_Settings::get_instance();

		// Create the regex pattern
		$sep		= preg_quote($settings->router_param_separator, '#');
		if ($id)$id = preg_quote($id, '#').$sep;
		$prefix		= preg_quote($settings->router_param_prefix, '#');
		$regex		= implode('', array(
			'#//?',
			$id ? "({$id})?" : "(\w+{$sep})?",
			"($prefix)?gallerytag{$sep}([\w-_]+)/?#"
		));

		// Replace any page parameters with the ngglegacy equivalent
		if (preg_match($regex, $retval, $matches)) {
			$retval = rtrim(str_replace($matches[0], "/tags/{$matches[3]}/", $retval), "/");
			$this->object->set_method_property(
				$this->method_called,
				ExtensibleObject::METHOD_PROPERTY_RETURN_VALUE,
				$retval
			);
		}

		return $retval;
	}
}
<?php

class A_Gallery_Display_View extends Mixin
{
    /**
     * Check whether to render certain kinds of extra additions to the view for a displayed gallery
     * @param object $displayed_gallery
     * @param string $template_id
     * @param C_MVC_View_Element $root_element
     * @param string $addition_type what kind of addition is being made 'layout', 'decoration', 'style', 'logic' etc.
     * @return string|NULL
     */
    function _check_addition_rendering($displayed_gallery, $template_id, $root_element, $addition_type)
    {
    	$view = $root_element->get_object();
    	$mode = $view->get_param('render_mode');
    	$ret = true;
    	
    	switch ($addition_type)
    	{
    		case 'layout':
  			{
    			$ret = !in_array($mode, array('bare', 'basic'));
    			
    			break;
  			}
    		case 'decoration':
  			{
    			break;
  			}
    		case 'style':
  			{
    			break;
  			}
    		case 'logic':
  			{
    			break;
  			}
    	}
    	
    	return $ret;
    }
}

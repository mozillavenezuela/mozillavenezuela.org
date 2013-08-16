<?php

class C_Image_Wrapper_Collection implements ArrayAccess
{

    public $container = array();
    public $galleries = array();

    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    public function offsetSet($offset, $value)
    {
        if (is_object($value))
        {
            $value->container = $this;
        }

        if (is_null($offset))
        {
            $this->container[] = $value;
        }
        else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * Retrieves and caches an I_Gallery_Mapper instance for this gallery id
     *
     * @param int $gallery_id Gallery ID
     * @return mixed
     */
    public function get_gallery($gallery_id)
    {
        if (!isset($this->galleries[$gallery_id]) || is_null($this->galleries[$gallery_id]))
        {
            $this->galleries[$gallery_id] = C_Component_Registry::get_instance()->get_utility('I_Gallery_Mapper');
        }
        return $this->galleries[$gallery_id];
    }
}

<?php

class A_WordPress_Routing_App extends Mixin
{
    function remove_parameter($key, $id=NULL, $url=FALSE)
    {
        $generated_url = $this->call_parent('remove_parameter', $key, $id, $url);

        if ($this->is_postname_required_in_url()) {
            $generated_url = $this->object->add_post_permalink_to_url($generated_url);
        }

        return $generated_url;
    }

    function is_postname_required_in_url()
    {
        global $wp_query;
        return !$wp_query->is_single() && in_the_loop();
    }

    function parse_url($url)
    {
        $parts = parse_url($url);
        if (!isset($parts['path'])) {
            $base_parts = parse_url($this->object->get_router()->get_base_url());
            if (!isset($base_parts['path'])) $base_parts['path'] = '/';
            $parts['path'] = $base_parts['path'];
        }
        if (!isset($parts['query'])) $parts['query'] = '';

        return $parts;
    }


    function add_post_permalink_to_url($generated_url)
    {
        $post_parts         = $this->parse_url(get_permalink());
        $generated_parts    = $this->parse_url($generated_url);

        // Combine querystrings
        $generated_parts['query'] = $this->object->join_querystrings(
            $post_parts['query'], $generated_parts['query']
        );

        // Combine paths
        $generated_parts['path'] = $this->object->join_paths($post_parts['path'], $generated_parts['path']);

        return $this->object->construct_url_from_parts($generated_parts);
    }

    function passthru()
    {
		$_SERVER['ORIG_REQUEST_URI'] = $this->object->get_router()->get_request_uri();
        $_SERVER['REQUEST_URI'] = trailingslashit(
            $this->object->strip_param_segments(
                $this->object->get_router()->get_request_uri()
            )
        );
    }

}
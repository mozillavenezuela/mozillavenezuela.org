<?php

interface I_Taxonomy_Controller extends I_MVC_Controller
{
    function index_action($tag);
    function detect_ngg_tag($posts);
    function create_ngg_tag_post($tag);
}
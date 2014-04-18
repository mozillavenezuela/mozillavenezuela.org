<?php

interface I_Legacy_Template_Locator
{
    function find($template_name);
    function find_all($prefix = FALSE);
    function get_template_directories();
    function get_templates_from_dir($dir, $prefix = FALSE);
}

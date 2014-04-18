<?php

/**
 * Provides a utility to locate legacy templates
 */
class C_Legacy_Template_Locator extends C_Component
{
    static $_instances = array();

    function define($context=FALSE)
    {
        parent::define($context);
        $this->add_mixin('Mixin_Legacy_Template_Locator');
        $this->implement('I_Legacy_Template_Locator');
    }

    static function get_instance($context=FALSE)
    {
        if (!isset(self::$_instances[$context])) {
            $klass = get_class();
            self::$_instances[$context] = new $klass($context);
        }
        return self::$_instances[$context];
    }
}


/**
 * Provides instance methods for the legacy template locator
 */
class Mixin_Legacy_Template_Locator extends Mixin
{
    /**
     * Returns an array of template storing directories
     *
     * @return array Template storing directories
     */
    function get_template_directories()
    {
        return array(
			'Child Theme' => get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'nggallery' . DIRECTORY_SEPARATOR,
			'Parent Theme' => get_template_directory() . DIRECTORY_SEPARATOR . 'nggallery' . DIRECTORY_SEPARATOR,
            'NextGEN' => NGGALLERY_ABSPATH . 'view' . DIRECTORY_SEPARATOR
        );
    }

    /**
     * Returns an array of all available template files
     *
     * @return array All available template files
     */
    function find_all($prefix = FALSE)
    {
        $files = array();
        foreach ($this->object->get_template_directories() as $label => $dir) {
            $tmp = $this->object->get_templates_from_dir($dir, $prefix);
            if (!$tmp)
                continue;
            $files[$label] = $tmp;
        }

        return $files;
    }

    /**
     * Recursively scans $dir for files ending in .php
     *
     * @param string $dir Directory
     * @return array All php files in $dir
     */
    function get_templates_from_dir($dir, $prefix = FALSE)
    {
        if (!is_dir($dir))
        {
            return;
        }

        $dir = new RecursiveDirectoryIterator($dir);
        $iterator = new RecursiveIteratorIterator($dir);

        // convert single-item arrays to string
        if (is_array($prefix) && count($prefix) <= 1)
        {
            $prefix = end($prefix);
        }

        // we can filter results by allowing a set of prefixes, one prefix, or by showing all available files
        if (is_array($prefix))
        {
            $str = implode('|', $prefix);
            $regex_iterator = new RegexIterator($iterator, "/({$str})-.+\\.php$/i", RecursiveRegexIterator::GET_MATCH);
        }
        elseif (is_string($prefix))
        {
            $regex_iterator = new RegexIterator($iterator, "#(.*)[/\\\\]{$prefix}\\-?.*\\.php$#i", RecursiveRegexIterator::GET_MATCH);
        }
        else {
            $regex_iterator = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
        }

        $files = array();
        foreach ($regex_iterator as $filename) {
            $files[] = reset($filename);
        }

        return $files;
    }


    /**
     * Find a particular template by name
     * @param $template
     */
    function find($template_name)
    {
        $template_abspath = FALSE;

        // hook into the render feature to allow other plugins to include templates
        $custom_template = apply_filters('ngg_render_template', FALSE, $template_name);

        if ($custom_template === FALSE)
            $custom_template = $template_name;

        // Ensure we have a PHP extension
        if (strpos($custom_template, '.php') === FALSE)
            $custom_template .= '.php';

        // Find the abspath of the template to render
        if (!@file_exists($custom_template))
        {
            foreach ($this->object->get_template_directories() as $dir) {
                if ($template_abspath)
                    break;
                $filename = implode(DIRECTORY_SEPARATOR, array(rtrim($dir, "/\\"), $custom_template));
                if (@file_exists($filename))
                {
                    $template_abspath = $filename;
                }
                elseif (strpos($custom_template, '-template') === FALSE) {
                    $filename = implode(DIRECTORY_SEPARATOR, array(
                        rtrim($dir, "/\\"),
                        str_replace('.php', '', $custom_template) . '-template.php'
                    ));
                    if (@file_exists($filename))
                        $template_abspath = $filename;
                }
            }
        }
        else {
            // An absolute path was already given
            $template_abspath = $custom_template;
        }

        return $template_abspath;
    }
}

<?php

// These functions do NOT work when the Adminer plugin is installed, and being
// viewed. As there's no need to use these functions when viewing Adminer, we'll
// just skip this
if (strpos($_SERVER['REQUEST_URI'], 'adminer') === FALSE) {

    if (!function_exists('h')) {
        function h($str)
        {
			if (defined('ENT_HTML401')) {
				return str_replace("'", "&#39;", htmlentities($str, ENT_COMPAT | ENT_HTML401, 'UTF-8'));
			}
			else {
				return str_replace("'", "&#39;", htmlentities($str, ENT_COMPAT, 'UTF-8'));
			}
        }
    }

    if (!function_exists('echo_h')) {
        function echo_h($str)
        {
            echo h($str);
        }
    }

    if (!function_exists('echo_safe_html')) {
        function echo_safe_html($html, $extra_tags = null)
        {
        	$tags = array('<a>', '<abbr>', '<acronym>', '<address>', '<b>', '<base>', '<basefont>', '<big>', '<blockquote>', '<br>', '<br/>', '<caption>', '<center>', '<cite>', '<code>', '<col>', '<colgroup>', '<dd>', '<del>', '<dfn>', '<dir>', '<div>', '<dl>', '<dt>', '<em>', '<fieldset>', '<font>', '<h1>', '<h2>', '<h3>', '<h4>', '<h5>', '<h6>', '<hr>', '<i>', '<ins>', '<label>', '<legend>', '<li>', '<menu>', '<noframes>', '<noscript>', '<ol>', '<optgroup>', '<option>', '<p>', '<pre>', '<q>', '<s>', '<samp>', '<select>', '<small>', '<span>', '<strike>', '<strong>', '<sub>', '<sup>', '<table>', '<tbody>', '<td>', '<tfoot>', '<th>', '<thead>', '<tr>', '<tt>', '<u>', '<ul>');

			$html = preg_replace('/\\s+on\\w+=(["\']).*?\\1/i', '', $html);
			$html = preg_replace('/(<\/[^>]+?>)(<[^>\/][^>]*?>)/', '$1 $2', $html);
        	$html = strip_tags($html, implode('', $tags));
        	
        	echo $html;
        }
    }
}

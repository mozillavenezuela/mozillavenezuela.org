<?php
/*
Plugin Name: Light SEO
Plugin URI: http://www.aldentorres.com/light-seo-wordpress-plugin
Description: WordPress SEO plugin. Focused in creating a clean plugin, with emphasis in source code's good practices.
Version: 0.0.15
Author: Alden Torres
Author URI: http://www.aldentorres.com
*/
/*  Copyright (C) 2009-2010 Alden Torres  (email : aldenml@yahoo.com)

    Copyright (C) 2008-2009 Michael Torbert, semperfiwebdesign.com (michael AT semperfiwebdesign DOT com)
    Original code by uberdose of uberdose.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class Light_SEO
{
	//-------------------------------
	// FIELDS
	//-------------------------------

	/** Max numbers of chars in auto-generated description */
	var $maximum_description_length = 160;
 	
	/** Minimum number of chars an excerpt should be so that it can be used
	 * as description. Touch only if you know what you're doing
	 */
	var $minimum_description_length = 1;

	//-------------------------------
	// CONSTRUCTORS
	//-------------------------------

	/**
	 * Constructor.
	 */
	function Light_SEO()
	{
		global $lightseop_options;
	}
	
	//-------------------------------
	// UTILS
	//-------------------------------

	/**      
	 * Convert a string to lower case.
	 * Originally, this function relied their functionality in a global UTF-8 character table.
	 * I will take my chances with a standard function.
	 * By default the charset in a typical WordPress installation is UTF-8.
	 */
	function strtolower($str)
	{
		return mb_strtolower($str, get_bloginfo('charset'));
	}

	/**      
	 * Convert a string to upper case.
	 * Originally, this function relied their functionality in a global UTF-8 character table.
	 * I will take my chances with a standard function.
	 * By default the charset in a typical WordPress installation is UTF-8.
	 */
	function strtoupper($str)
	{
		return mb_strtoupper($str, get_bloginfo('charset'));
	}

	/**
	 * Make a string's first character uppercase.
	 * By default the charset in a typical WordPress installation is UTF-8.
	 */
	function capitalize($str)
	{
		return mb_convert_case($str, MB_CASE_TITLE, get_bloginfo('charset'));
	}
	
	function is_static_front_page()
	{
		global $wp_query;
		
		$post = $wp_query->get_queried_object();
		
		return get_option('show_on_front') == 'page' && is_page() && $post->ID == get_option('page_on_front');
	}
	
	function is_static_posts_page()
	{
		global $wp_query;
		
		$post = $wp_query->get_queried_object();
		
		return get_option('show_on_front') == 'page' && is_home() && $post->ID == get_option('page_for_posts');
	}

	/**
	 * This function detects if a given request contains the name of an excluded page.
	 */
	function lightseop_mrt_exclude_this_page()
	{
		global $lightseop_options;

		$currenturl = trim(esc_url($_SERVER['REQUEST_URI'], '/'));

		$excludedstuff = explode(',', $lightseop_options['lightseo_ex_pages']);

		foreach ($excludedstuff as $exedd)
		{
			$exedd = trim($exedd);

			if ($exedd)
			{
				if (stristr($currenturl, $exedd))
				{
					return true;
				}
			}
		}

		return false;
	}
	
	function output_callback_for_title($content)
	{
		return $this->rewrite_title($content);
	}

	/**
	 * TODO: This function seems to translate the text to the current language.
	 * Actually I don't have any insight that this is really effective.
	 */
	function internationalize($in)
	{
		if (function_exists('langswitch_filter_langs_with_message'))
		{
			$in = langswitch_filter_langs_with_message($in);
		}

		if (function_exists('polyglot_filter'))
		{
			$in = polyglot_filter($in);
		}

		if (function_exists('qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage'))
		{
			$in = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($in);
		}

		$in = apply_filters('localization', $in);

		return $in;
	}

	//-------------------------------
	// ACTIONS
	//-------------------------------

	/**
	 * Runs after WordPress admin has finished loading but before any headers are sent.
	 * Useful for intercepting $_GET or $_POST triggers. 
	 */
	function init()
	{
		// Loads the plugin's translated strings. 
		load_plugin_textdomain('light_seo', false, dirname(plugin_basename(__FILE__)));
	}

	/**
	 * Runs before the determination of the template file to be used to display the requested page,
	 * so that a plugin can override the template file choice.
	 *
	 * Used in this case for title rewrite.
	 */
	function template_redirect()
	{
		global $wp_query;
		global $lightseop_options;

		$post = $wp_query->get_queried_object();

		if ($this->lightseop_mrt_exclude_this_page())
		{
			return;
		}

		if (is_feed())
		{
			return;
		}

		if (is_single() || is_page())
		{
			$lightseo_disable = htmlspecialchars(stripcslashes(get_post_meta($post->ID, '_lightseop_disable', true)));
			
			if ($lightseo_disable)
			{
				return;
			}
		}

		if ($lightseop_options['lightseo_rewrite_titles'])
		{
			ob_start(array($this, 'output_callback_for_title')); // this ob_start is matched with ob_end_flush in wp_head
		}
	}

	/**
	 * Triggered within the <head></head> section of the user's template.
	 *
	 * This hook is theme-dependent which means that it is up to the author of each WordPress theme
	 * to include it. It may not be available on all themes, so you should take this into account
	 * when using it.
	 *
	 * Although this is theme-dependent, it is one of the most essential theme hooks, so it is
	 * fairly widely supported. 
	 */
	function wp_head()
	{
		if (is_feed()) // ignore logic if it's a feed
		{
			return;
		}

		global $wp_query;
		global $lightseop_options;

		$post = $wp_query->get_queried_object();

		$meta_string = null;

		if ($this->is_static_posts_page())
		{
			// TODO: strip_tags return a string with all HTML and PHP tags stripped from a given str. Since
			// it uses a tag stripping state machine, probably it's better to remove this function if you
			// never use weird post titles.
			//
			// The apply_filters on 'single_post_title' ensure any previous plugin is applied.
			//
			// I would like to change this line to
			//
			// $title = $post->post_title;
			//
			// and save a lot of CPU cycles.
			$title = strip_tags(apply_filters('single_post_title', $post->post_title));
		}

		if (is_single() || is_page())
		{
			$lightseo_disable = htmlspecialchars(stripcslashes(get_post_meta($post->ID, '_lightseop_disable', true)));

			if ($lightseo_disable)
			{
				return;
			}
		}

		if ($this->lightseop_mrt_exclude_this_page())
		{
			return;
		}

		if ($lightseop_options['lightseo_rewrite_titles'])
		{
			// make the title rewrite as short as possible
			if (function_exists('ob_list_handlers'))
			{
				$active_handlers = ob_list_handlers();
			}
			else
			{
				$active_handlers = array();
			}
			
			if ((sizeof($active_handlers) > 0) &&
				(strtolower($active_handlers[sizeof($active_handlers) - 1]) ==
				strtolower('Light_SEO::output_callback_for_title')))
			{
				ob_end_flush(); // this ob_end_flush is matched with ob_start in template_redirect
			}
			else
			{
				// TODO:
				// if we get here there *could* be trouble with another plugin :(
				// decide what to do
			}
		}

		if ((is_home() && $lightseop_options['lightseo_home_keywords'] &&
			!$this->is_static_posts_page()) || $this->is_static_front_page())
		{
			$keywords = trim($this->internationalize($lightseop_options['lightseo_home_keywords']));
		}
		elseif ($this->is_static_posts_page() && !$lightseop_options['lightseo_dynamic_postspage_keywords']) // and if option = use page set keywords instead of keywords from recent posts
		{
			$keywords = stripcslashes($this->internationalize(get_post_meta($post->ID, "_lightseop_keywords", true)));
		}
		else
		{
			$keywords = $this->get_all_keywords();
		}

		if (is_single() || is_page() || $this->is_static_posts_page())
		{
			if ($this->is_static_front_page())
			{
				$description = trim(stripcslashes($this->internationalize($lightseop_options['lightseo_home_description'])));
			}
			else
			{
				$description = $this->get_post_description($post);
				$description = apply_filters('lightseop_description', $description);
			}
		}
		elseif (is_home())
		{
			$description = trim(stripcslashes($this->internationalize($lightseop_options['lightseo_home_description'])));
		}
		elseif (is_category())
		{
			$description = $this->internationalize(category_description());
		}

		if (isset($description) && (strlen($description) > $this->minimum_description_length) &&
			!(is_home() && is_paged()))
		{
			$description = trim(strip_tags($description));
			$description = str_replace('"', '', $description);
			
			// replace newlines on mac / windows?
			$description = str_replace("\r\n", ' ', $description);
			
			// maybe linux uses this alone
			$description = str_replace("\n", ' ', $description);

			if (!isset($meta_string))
			{
				$meta_string = '';
			}

			// description format
			$description_format = $lightseop_options['lightseo_description_format'];

			if (!isset($description_format) || empty($description_format))
			{
				$description_format = "%description%";
			}
			
			$description = str_replace('%description%', $description, $description_format);
			$description = str_replace('%blog_title%', get_bloginfo('name'), $description);
			$description = str_replace('%blog_description%', get_bloginfo('description'), $description);
			$description = str_replace('%wp_title%', $this->get_original_title(), $description);

			if ($lightseop_options['lightseo_can'] && is_attachment())
			{
				$url = $this->lightseo_mrt_get_url($wp_query);
                
				if ($url)
				{
					preg_match_all('/(\d+)/', $url, $matches);

					if (is_array($matches))
					{
						$uniqueDesc = join('', $matches[0]);
					}
				}
				
				$description .= ' ' . $uniqueDesc;
			}
			
			$meta_string .= '<meta name="description" content="' . esc_attr($description) . '" />';
		}
		
		$keywords = apply_filters('lightseop_keywords', $keywords);
		
		if (isset($keywords) && !empty($keywords) && !(is_home() && is_paged()))
		{
			if (isset($meta_string))
			{
				$meta_string .= "\n";
			}
			
			$meta_string .= '<meta name="keywords" content="' . esc_attr($keywords) . '" />';
		}

		if (function_exists('is_tag'))
		{
			$is_tag = is_tag();
		}
		
		if ((is_category() && $lightseop_options['lightseo_category_noindex']) ||
			(!is_category() && is_archive() &&!$is_tag && $lightseop_options['lightseo_archive_noindex']) ||
			($lightseop_options['lightseo_tags_noindex'] && $is_tag))
		{
			if (isset($meta_string))
			{
				$meta_string .= "\n";
			}
			
			$meta_string .= '<meta name="robots" content="noindex,follow" />';
		}
		
		$page_meta = stripcslashes($lightseop_options['lightseo_page_meta_tags']);
		$post_meta = stripcslashes($lightseop_options['lightseo_post_meta_tags']);
		$home_meta = stripcslashes($lightseop_options['lightseo_home_meta_tags']);
		
		if (is_page() && isset($page_meta) && !empty($page_meta) || $this->is_static_posts_page())
		{
			if (isset($meta_string))
			{
				$meta_string .= "\n";
			}
			
			$meta_string .= $page_meta;
		}
		
		if (is_single() && isset($post_meta) && !empty($post_meta))
		{
			if (isset($meta_string))
			{
				$meta_string .= "\n";
			}

			$meta_string .= $post_meta;
		}

		if (is_home() && !empty($home_meta))
		{
			if (isset($meta_string))
			{
				$meta_string .= "\n";
			}

			$meta_string .= $home_meta;
		}

		// add google site verification meta tag for webmasters tools
		$home_google_site_verification_meta_tag = stripcslashes($lightseop_options['lightseo_home_google_site_verification_meta_tag']);

		if (is_home() && !empty($home_google_site_verification_meta_tag))
		{
			if (isset($meta_string))
			{
				$meta_string .= "\n";
			}

			$meta_string .= wp_kses($home_google_site_verification_meta_tag, array('meta' => array('name' => array(), 'content' => array())));
		}

		if ($meta_string != null)
		{
			echo wp_kses($meta_string, array('meta' => array('name' => array(), 'content' => array()))) . "\n";
		}

		if ($lightseop_options['lightseo_can'])
		{
			$url = $this->lightseo_mrt_get_url($wp_query);

			if ($url)
			{
				$url = apply_filters('lightseop_canonical_url', $url);

				echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";
			}
		}
	}
	
	function lightseo_mrt_get_url($query)
	{
		global $lightseop_options;

		if ($query->is_404 || $query->is_search)
		{
			return false;
		}

		$haspost = count($query->posts) > 0;
		$has_ut = function_exists('user_trailingslashit');

		if (get_query_var('m'))
		{
			$m = preg_replace('/[^0-9]/', '', get_query_var('m'));
			
			switch (strlen($m))
			{
			case 4:
				$link = get_year_link($m);
				break;
			case 6:
				$link = get_month_link(substr($m, 0, 4), substr($m, 4, 2));
				break;
			case 8:
				$link = get_day_link(substr($m, 0, 4), substr($m, 4, 2), substr($m, 6, 2));
				break;
			default:
				return false;
			}
		}
		elseif (($query->is_single || $query->is_page) && $haspost)
		{
			$post = $query->posts[0];
			$link = get_permalink($post->ID);
			$link = $this->yoast_get_paged($link); 
		}
		elseif ($query->is_author && $haspost)
		{
			$author = get_userdata(get_query_var('author'));

			if ($author === false)
				return false;

			$link = get_author_link(false, $author->ID, $author->user_nicename);
		}
		elseif ($query->is_category && $haspost)
		{
			$link = get_category_link(get_query_var('cat'));
			$link = $this->yoast_get_paged($link);
		}
		elseif ($query->is_tag  && $haspost)
		{
			$tag = get_term_by('slug', get_query_var('tag'), 'post_tag');
			
			if (!empty($tag->term_id))
			{
				$link = get_tag_link($tag->term_id);
			}
			
			$link = $this->yoast_get_paged($link);			
		}
		elseif ($query->is_day && $haspost)
		{
			$link = get_day_link(get_query_var('year'), get_query_var('monthnum'), get_query_var('day'));
		}
		elseif ($query->is_month && $haspost)
		{
			$link = get_month_link(get_query_var('year'), get_query_var('monthnum'));
		}
		elseif ($query->is_year && $haspost)
		{
			$link = get_year_link(get_query_var('year'));
		}
		elseif ($query->is_home)
		{
			if ((get_option('show_on_front') == 'page') && ($pageid = get_option('page_for_posts')))
			{
				$link = get_permalink($pageid);
				$link = $this->yoast_get_paged($link);
				$link = trailingslashit($link);
			}
			else
			{
				$link = get_option('home');
				$link = $this->yoast_get_paged($link);
				$link = trailingslashit($link);
			}
		}
		else
		{
			return false;
		}
		
		return $link;
	}
	
	function yoast_get_paged($link)
	{
		$page = get_query_var('paged');

		if ($page && $page > 1)
		{
			$link = trailingslashit($link) ."page/". "$page";

			if ($has_ut)
			{
				$link = user_trailingslashit($link, 'paged');
			}
			else
			{
				$link .= '/';
			}
		}

		return $link;
	}

	function get_post_description($post)
	{
		global $lightseop_options;

		$description = trim(stripcslashes($this->internationalize(get_post_meta($post->ID, "_lightseop_description", true))));

		if (!$description)
		{
			$description = $this->trim_excerpt_without_filters_full_length($this->internationalize($post->post_excerpt));

			if (!$description && $lightseop_options["lightseo_generate_descriptions"])
			{
				$description = $this->trim_excerpt_without_filters($this->internationalize($post->post_content));
			}				
		}

		// "internal whitespace trim"
		$description = preg_replace("/\s\s+/", " ", $description);

		return $description;
	}

	/**
	 * Replace the title using regular expressions. If the regular expression fails
	 * (probably a backtrack limit error) you need to fix your environment.
	 */
	function replace_title($content, $title)
	{
		return preg_replace('/<title>(.*?)<\/title>/ms', '<title>' . esc_html($title) . '</title>', $content, 1);
	}
	
	/** @return The original title as delivered by WP (well, in most cases) */
	function get_original_title()
	{
		global $wp_query;
		global $lightseop_options;
		
		if (!$wp_query)
		{
			return null;	
		}
		
		$post = $wp_query->get_queried_object();
		
		// the_search_query() is not suitable, it cannot just return
		global $s;

		$title = null;
		
		if (is_home())
		{
			$title = get_option('blogname');
		}
		elseif (is_single())
		{
			$title = $this->internationalize(wp_title('', false));
		}
		elseif (is_search() && isset($s) && !empty($s))
		{
			if (function_exists('attribute_escape'))
			{
				$search = attribute_escape(stripcslashes($s));
			}
			else
			{
				$search = wp_specialchars(stripcslashes($s), true);
			}
			
			$search = $this->capitalize($search);
			$title = $search;
		}
		elseif (is_category() && !is_feed())
		{
			$category_description = $this->internationalize(category_description());
			$category_name = ucwords($this->internationalize(single_cat_title('', false)));
			$title = $category_name;
		}
		elseif (is_page())
		{
			$title = $this->internationalize(wp_title('', false));
		}
		elseif (function_exists('is_tag') && is_tag())
		{
			$tag = $this->internationalize(wp_title('', false));

			if ($tag)
			{
				$title = $tag;
			}
		}
		else if (is_archive())
		{
			$title = $this->internationalize(wp_title('', false));
		}
		else if (is_404())
		{
			$title_format = $lightseop_options['lightseo_404_title_format'];

			$new_title = str_replace('%blog_title%', $this->internationalize(get_bloginfo('name')), $title_format);
			$new_title = str_replace('%blog_description%', $this->internationalize(get_bloginfo('description')), $new_title);
			$new_title = str_replace('%request_url%', esc_url($_SERVER['REQUEST_URI']), $new_title);
			$new_title = str_replace('%request_words%', $this->request_as_words(esc_url($_SERVER['REQUEST_URI'])), $new_title);
			
			$title = $new_title;
		}

		return trim($title);
	}
	
	function paged_title($title)
	{
		// the page number if paged
		global $paged;
		global $lightseop_options;
		// simple tagging support
		global $STagging;

		if (is_paged() || (isset($STagging) && $STagging->is_tag_view() && $paged))
		{
			$part = $this->internationalize($lightseop_options['lightseo_paged_format']);

			if (isset($part) || !empty($part))
			{
				$part = " " . trim($part);
				$part = str_replace('%page%', $paged, $part);
				$title .= $part;
			}
		}

		return $title;
	}

	function rewrite_title($header)
	{
		global $lightseop_options;
		global $wp_query;
		
		if (!$wp_query)
		{
			return $header;	
		}
		
		$post = $wp_query->get_queried_object();
		
		// the_search_query() is not suitable, it cannot just return
		global $s;
		
		global $STagging;

		if (is_home() && !$this->is_static_posts_page())
		{
			$title = $this->internationalize($lightseop_options['lightseo_home_title']);
			
			if (empty($title))
			{
				$title = $this->internationalize(get_option('blogname'));
			}

			$title = $this->paged_title($title);
			$header = $this->replace_title($header, $title);
		}
		else if (is_attachment())
		{
			$title = get_the_title($post->post_parent).' '.$post->post_title.' – '.get_option('blogname');
			$header = $this->replace_title($header,$title);
		}
		else if (is_single())
		{
			// we're not in the loop :(
			$authordata = get_userdata($post->post_author);
			$categories = get_the_category();
			$category = '';
			
			if (count($categories) > 0)
			{
				$category = $categories[0]->cat_name;
			}

			$title = $this->internationalize(get_post_meta($post->ID, "_lightseop_title", true));
			
			if (!$title)
			{
				$title = $this->internationalize(get_post_meta($post->ID, "title_tag", true));
				
				if (!$title)
				{
					$title = $this->internationalize(wp_title('', false));
				}
			}

			$title_format = $lightseop_options['lightseo_post_title_format'];

			$new_title = str_replace('%blog_title%', $this->internationalize(get_bloginfo('name')), $title_format);
			$new_title = str_replace('%blog_description%', $this->internationalize(get_bloginfo('description')), $new_title);
			$new_title = str_replace('%post_title%', $title, $new_title);
			$new_title = str_replace('%category%', $category, $new_title);
			$new_title = str_replace('%category_title%', $category, $new_title);
			$new_title = str_replace('%post_author_login%', $authordata->user_login, $new_title);
			$new_title = str_replace('%post_author_nicename%', $authordata->user_nicename, $new_title);
			$new_title = str_replace('%post_author_firstname%', ucwords($authordata->first_name), $new_title);
			$new_title = str_replace('%post_author_lastname%', ucwords($authordata->last_name), $new_title);

			$title = $new_title;
			$title = trim($title);
			$title = apply_filters('lightseop_title_single',$title);

			$header = $this->replace_title($header, $title);
		}
		elseif (is_search() && isset($s) && !empty($s))
		{
			if (function_exists('attribute_escape'))
			{
				$search = attribute_escape(stripcslashes($s));
			}
			else
			{
				$search = wp_specialchars(stripcslashes($s), true);
			}

			$search = $this->capitalize($search);
			$title_format = $lightseop_options['lightseo_search_title_format'];

			$title = str_replace('%blog_title%', $this->internationalize(get_bloginfo('name')), $title_format);
			$title = str_replace('%blog_description%', $this->internationalize(get_bloginfo('description')), $title);
			$title = str_replace('%search%', $search, $title);
			
			$header = $this->replace_title($header, $title);
		}
		elseif (is_category() && !is_feed())
		{
			$category_description = $this->internationalize(category_description());

			if($lightseop_options['lightseo_cap_cats'])
			{
				$category_name = ucwords($this->internationalize(single_cat_title('', false)));
			}
			else
			{
				$category_name = $this->internationalize(single_cat_title('', false));
			}			

			$title_format = $lightseop_options['lightseo_category_title_format'];

			$title = str_replace('%category_title%', $category_name, $title_format);
			$title = str_replace('%category_description%', $category_description, $title);
			$title = str_replace('%blog_title%', $this->internationalize(get_bloginfo('name')), $title);
			$title = str_replace('%blog_description%', $this->internationalize(get_bloginfo('description')), $title);
			$title = $this->paged_title($title);
			
			$header = $this->replace_title($header, $title);
		}
		elseif (is_page() || $this->is_static_posts_page())
		{
			// we're not in the loop :(
			$authordata = get_userdata($post->post_author);

			if ($this->is_static_front_page())
			{
				if ($this->internationalize($lightseop_options['lightseo_home_title']))
				{
					//home title filter
					$home_title = $this->internationalize($lightseop_options['lightseo_home_title']);
					$home_title = apply_filters('lightseop_home_page_title',$home_title);
					
					$header = $this->replace_title($header, $home_title);
				}
			}
			else
			{
				$title = $this->internationalize(get_post_meta($post->ID, "_lightseop_title", true));
				
				if (!$title)
				{
					$title = $this->internationalize(wp_title('', false));
				}

				$title_format = $lightseop_options['lightseo_page_title_format'];

				$new_title = str_replace('%blog_title%', $this->internationalize(get_bloginfo('name')), $title_format);
				$new_title = str_replace('%blog_description%', $this->internationalize(get_bloginfo('description')), $new_title);
				$new_title = str_replace('%page_title%', $title, $new_title);
				$new_title = str_replace('%page_author_login%', $authordata->user_login, $new_title);
				$new_title = str_replace('%page_author_nicename%', $authordata->user_nicename, $new_title);
				$new_title = str_replace('%page_author_firstname%', ucwords($authordata->first_name), $new_title);
				$new_title = str_replace('%page_author_lastname%', ucwords($authordata->last_name), $new_title);

				$title = trim($new_title);
				$title = apply_filters('lightseop_title_page', $title);

				$header = $this->replace_title($header, $title);
			}
		}
		elseif (function_exists('is_tag') && is_tag())
		{
			$tag = $this->internationalize(wp_title('', false));

			if ($tag)
			{
				$tag = $this->capitalize($tag);
				$title_format = $lightseop_options['lightseo_tag_title_format'];
	            
				$title = str_replace('%blog_title%', $this->internationalize(get_bloginfo('name')), $title_format);
				$title = str_replace('%blog_description%', $this->internationalize(get_bloginfo('description')), $title);
				$title = str_replace('%tag%', $tag, $title);
				$title = $this->paged_title($title);
				
				$header = $this->replace_title($header, $title);
			}
		}
		elseif (isset($STagging) && $STagging->is_tag_view()) // simple tagging support
		{
			$tag = $STagging->search_tag;
			
			if ($tag)
			{
				$tag = $this->capitalize($tag);
				$title_format = $lightseop_options['lightseo_tag_title_format'];

				$title = str_replace('%blog_title%', $this->internationalize(get_bloginfo('name')), $title_format);
				$title = str_replace('%blog_description%', $this->internationalize(get_bloginfo('description')), $title);
				$title = str_replace('%tag%', $tag, $title);
				$title = $this->paged_title($title);

				$header = $this->replace_title($header, $title);
			}
		}
		else if (is_archive())
		{
			$date = $this->internationalize(wp_title('', false));
			$title_format = $lightseop_options['lightseo_archive_title_format'];

			$new_title = str_replace('%blog_title%', $this->internationalize(get_bloginfo('name')), $title_format);
			$new_title = str_replace('%blog_description%', $this->internationalize(get_bloginfo('description')), $new_title);
			$new_title = str_replace('%date%', $date, $new_title);

			$title = trim($new_title);
			$title = $this->paged_title($title);

			$header = $this->replace_title($header, $title);
		}
		else if (is_404())
		{
			$title_format = $lightseop_options['lightseo_404_title_format'];

			$new_title = str_replace('%blog_title%', $this->internationalize(get_bloginfo('name')), $title_format);
			$new_title = str_replace('%blog_description%', $this->internationalize(get_bloginfo('description')), $new_title);
			$new_title = str_replace('%request_url%', esc_url($_SERVER['REQUEST_URI']), $new_title);
			$new_title = str_replace('%request_words%', $this->request_as_words(esc_url($_SERVER['REQUEST_URI'])), $new_title);
			$new_title = str_replace('%404_title%', $this->internationalize(wp_title('', false)), $new_title);

			$header = $this->replace_title($header, $new_title);
		}
		
		return $header;
	}
	
	/**
	 * @return User-readable nice words for a given request.
	 */
	function request_as_words($request)
	{
		$request = htmlspecialchars($request);
		$request = str_replace('.html', ' ', $request);
		$request = str_replace('.htm', ' ', $request);
		$request = str_replace('.', ' ', $request);
		$request = str_replace('/', ' ', $request);

		$request_a = explode(' ', $request);
		$request_new = array();

		foreach ($request_a as $token)
		{
			$request_new[] = ucwords(trim($token));
		}

		$request = implode(' ', $request_new);

		return $request;
	}
	
	function trim_excerpt_without_filters($text)
	{
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = preg_replace( '|\[(.+?)\](.+?\[/\\1\])?|s', '', $text);
		$text = strip_tags($text);

		$max = $this->maximum_description_length;

		if ($max < strlen($text))
		{
			while ($text[$max] != ' ' && $max > $this->minimum_description_length)
			{
				$max--;
			}
		}

		$text = substr($text, 0, $max);

		return trim(stripcslashes($text));
	}
	
	function trim_excerpt_without_filters_full_length($text)
	{
		$text = str_replace(']]>', ']]&gt;', $text);
		$text = preg_replace( '|\[(.+?)\](.+?\[/\\1\])?|s', '', $text);
		$text = strip_tags($text);

		return trim(stripcslashes($text));
	}
	
	/**
	 * @return comma-separated list of unique keywords
	 */
	function get_all_keywords()
	{
		global $posts;
		global $lightseop_options;

		if (is_404())
		{
			return null;
		}
		
		// if we are on synthetic pages
		if (!is_home() && !is_page() && !is_single() &&!$this->is_static_front_page() && !$this->is_static_posts_page()) 
		{
			return null;
		}

		$keywords = array();
		
		if (is_array($posts))
		{
			foreach ($posts as $post)
			{
				if ($post)
				{
					// custom field keywords
					$keywords_a = $keywords_i = null;
					$description_a = $description_i = null;

					$id = is_attachment() ? $post->post_parent : $post->ID; // if attachment then use parent post id

					$keywords_i = stripcslashes($this->internationalize(get_post_meta($id, "_lightseop_keywords", true)));
					$keywords_i = str_replace('"', '', $keywords_i);
	                
					if (isset($keywords_i) && !empty($keywords_i))
					{
						$traverse = explode(',', $keywords_i);
	                	
						foreach ($traverse as $keyword) 
						{
							$keywords[] = $keyword;
						}
					}
	                
					if ($lightseop_options['lightseo_use_tags_as_keywords'])
					{
						if (function_exists('get_the_tags'))
						{
							$tags = get_the_tags($id);

							if ($tags && is_array($tags))
							{
								foreach ($tags as $tag)
								{
									$keywords[] = $this->internationalize($tag->name);
								}
							}
						}
					}

					// autometa
					$autometa = stripcslashes(get_post_meta($id, 'autometa', true));

					if (isset($autometa) && !empty($autometa))
					{
						$autometa_array = explode(' ', $autometa);
						
						foreach ($autometa_array as $e) 
						{
							$keywords[] = $e;
						}
					}

					if ($lightseop_options['lightseo_use_categories'] && !is_page())
					{
						$categories = get_the_category($id); 

						foreach ($categories as $category)
						{
							$keywords[] = $this->internationalize($category->cat_name);
						}
					}
				}
			}
		}

		return $this->get_unique_keywords($keywords);
	}

	function get_unique_keywords($keywords)
	{
		$arr = array_map("strtolower", $keywords);

		$arr = array_unique($arr);

		return implode(',', $arr);
	}

	/** crude approximization of whether current user is an admin */
	function is_admin()
	{
		return current_user_can('level_8');
	}

	function post_meta_tags($id)
	{
		$awmp_edit = $_POST['lightseo_edit'];
		$nonce = $_POST['nonce-lightseop-edit'];

		if (isset($awmp_edit) && !empty($awmp_edit) && wp_verify_nonce($nonce, 'edit-lightseop-nonce'))
		{
			$keywords = $_POST["lightseo_keywords"];
			$description = $_POST["lightseo_description"];
			$title = $_POST["lightseo_title"];
			$lightseo_meta = $_POST["lightseo_meta"];
			$lightseo_disable = $_POST["lightseo_disable"];
			$lightseo_titleatr = $_POST["lightseo_titleatr"];
			$lightseo_menulabel = $_POST["lightseo_menulabel"];
				
			delete_post_meta($id, '_lightseop_keywords');
			delete_post_meta($id, '_lightseop_description');
			delete_post_meta($id, '_lightseop_title');
			delete_post_meta($id, '_lightseop_titleatr');
			delete_post_meta($id, '_lightseop_menulabel');
		
			if ($this->is_admin())
			{
				delete_post_meta($id, '_lightseop_disable');
			}

			if (isset($keywords) && !empty($keywords))
			{
				add_post_meta($id, '_lightseop_keywords', $keywords);
			}

			if (isset($description) && !empty($description))
			{
				add_post_meta($id, '_lightseop_description', $description);
			}

			if (isset($title) && !empty($title))
			{
				add_post_meta($id, '_lightseop_title', $title);
			}
		    
			if (isset($lightseo_titleatr) && !empty($lightseo_titleatr))
			{
				add_post_meta($id, '_lightseop_titleatr', $lightseo_titleatr);
			}

			if (isset($lightseo_menulabel) && !empty($lightseo_menulabel))
			{
				add_post_meta($id, '_lightseop_menulabel', $lightseo_menulabel);
			}				

			if (isset($lightseo_disable) && !empty($lightseo_disable) && $this->is_admin())
			{
				add_post_meta($id, '_lightseop_disable', $lightseo_disable);
			}
		}
	}

	function add_meta_tags_textinput()
	{
		global $post;

		$post_id = $post;
	    
		if (is_object($post_id))
		{
			$post_id = $post_id->ID;
		}

		// TODO: Probably esc_attr is more than enough
		$keywords = esc_attr(htmlspecialchars(stripcslashes(get_post_meta($post_id, '_lightseop_keywords', true))));
		$title = esc_attr(htmlspecialchars(stripcslashes(get_post_meta($post_id, '_lightseop_title', true))));
		$description = esc_attr(htmlspecialchars(stripcslashes(get_post_meta($post_id, '_lightseop_description', true))));
		$lightseo_meta = esc_attr(htmlspecialchars(stripcslashes(get_post_meta($post_id, '_lightseop_meta', true))));
		$lightseo_disable = esc_attr(htmlspecialchars(stripcslashes(get_post_meta($post_id, '_lightseop_disable', true))));
		$lightseo_titleatr = esc_attr(htmlspecialchars(stripcslashes(get_post_meta($post_id, '_lightseop_titleatr', true))));
		$lightseo_menulabel = esc_attr(htmlspecialchars(stripcslashes(get_post_meta($post_id, '_lightseop_menulabel', true))));
	
?>
<script type="text/javascript">
function countChars(field, cntfield)
{
  cntfield.value = field.value.length;
}
</script>
<div id="postlightseo" class="postbox closed">
  <h3>
    <?php _e('Light SEO', 'light_seo') ?>
  </h3>
  <div class="inside">
    <div id="postlightseo">
      <input value="lightseo_edit" type="hidden" name="lightseo_edit" />
      <input type="hidden" name="nonce-lightseop-edit" value="<?php echo wp_create_nonce('edit-lightseop-nonce'); ?>" />
      <table style="margin-bottom:40px">
        <tr>
          <th style="text-align:left;" colspan="2">
          </th>
        </tr>
        <tr>
          <th scope="row" style="text-align:right;">
            <?php _e('Title:', 'light_seo') ?>
          </th>
          <td>
            <input value="<?php echo $title ?>" type="text" name="lightseo_title" size="62"/>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right;">
            <?php _e('Description:', 'light_seo') ?>
          </th>
          <td>
            <textarea name="lightseo_description" rows="1" cols="60"
                onkeydown="countChars(document.post.lightseo_description,document.post.length1)"
                onkeyup="countChars(document.post.lightseo_description,document.post.length1)"><?php echo $description ?></textarea><br />
            <input readonly="" type="text" name="length1" size="3" maxlength="3" value="<?php echo strlen($description);?>" />
            <?php _e(' characters. Most search engines use a maximum of 160 chars for the description.', 'light_seo') ?>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right;">
            <?php _e('Keywords (comma separated):', 'light_seo') ?>
          </th>
          <td>
            <input value="<?php echo $keywords ?>" type="text" name="lightseo_keywords" size="62" />
          </td>
        </tr>
<?php if ($this->is_admin()) { ?>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <?php _e('Disable on this page/post:', 'light_seo')?>
          </th>
          <td>
            <input type="checkbox" name="lightseo_disable" <?php if ($lightseo_disable) echo 'checked="checked"'; ?> />
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right;">
            <?php _e('Title Attribute:', 'light_seo') ?>
          </th>
          <td>
            <input value="<?php echo $lightseo_titleatr ?>" type="text" name="lightseo_titleatr" size="62" />
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right;">
            <?php _e('Menu Label:', 'light_seo') ?>
          </th>
          <td>
            <input value="<?php echo $lightseo_menulabel ?>" type="text" name="lightseo_menulabel" size="62" />
          </td>
        </tr>
<?php } ?>
      </table>
    </div>
  </div>
</div>
<?php
	}

	/**
	 * Defines the sub-menu admin page using the add_submenu_page function.
	 */
	function admin_menu()
	{
		add_submenu_page('options-general.php', __('Light SEO', 'lightseo'), __('Light SEO', 'lightseo'), 'administrator', __FILE__, array($this, 'options_panel'));
	}

	function options_panel()
	{
		$message = null;

		global $lightseop_options;		
		
		if (!$lightseop_options['lightseo_cap_cats'])
		{
			$lightseop_options['lightseo_cap_cats'] = '1';
		}
		
		if ($_POST['action'] && $_POST['action'] == 'lightseo_update' && $_POST['Submit_Default'] != '')
		{
			$nonce = $_POST['nonce-lightseop'];
			
			if (!wp_verify_nonce($nonce, 'lightseop-nonce'))
				die ( 'Security Check - If you receive this in error, log out and back in to WordPress');
			
			$message = __("Light SEO Options Reset.", 'lightseo');

			delete_option('lightseop_options');

			$res_lightseop_options = array(
				"lightseo_can"=>1,
				"lightseo_home_title"=>null,
				"lightseo_home_description"=>'',
				"lightseo_home_keywords"=>null,
				"lightseo_max_words_excerpt"=>'something',
				"lightseo_rewrite_titles"=>1,
				"lightseo_post_title_format"=>'%post_title% | %blog_title%',
				"lightseo_page_title_format"=>'%page_title% | %blog_title%',
				"lightseo_category_title_format"=>'%category_title% | %blog_title%',
				"lightseo_archive_title_format"=>'%date% | %blog_title%',
				"lightseo_tag_title_format"=>'%tag% | %blog_title%',
				"lightseo_search_title_format"=>'%search% | %blog_title%',
				"lightseo_description_format"=>'%description%',
				"lightseo_404_title_format"=>'Nothing found for %request_words%',
				"lightseo_paged_format"=>' - Part %page%',
				"lightseo_use_categories"=>0,
				"lightseo_dynamic_postspage_keywords"=>1,
				"lightseo_category_noindex"=>1,
				"lightseo_archive_noindex"=>1,
				"lightseo_tags_noindex"=>0,
				"lightseo_cap_cats"=>1,
				"lightseo_generate_descriptions"=>1,
				"lightseo_debug_info"=>null,
				"lightseo_post_meta_tags"=>'',
				"lightseo_page_meta_tags"=>'',
				"lightseo_home_meta_tags"=>'',
				'home_google_site_verification_meta_tag' => '',
				'lightseo_use_tags_as_keywords' => 1);
				
			update_option('lightseop_options', $res_lightseop_options);
		}
		
		// update options
		if ($_POST['action'] && $_POST['action'] == 'lightseo_update' && $_POST['Submit'] != '')
		{
			$nonce = $_POST['nonce-lightseop'];
		
			if (!wp_verify_nonce($nonce, 'lightseop-nonce'))
				die ( 'Security Check - If you receive this in error, log out and back in to WordPress');
				
			$message = __("Light SEO Options Updated.", 'lightseo');
			
			$lightseop_options['lightseo_can'] = $_POST['lightseo_can'];
			$lightseop_options['lightseo_home_title'] = $_POST['lightseo_home_title'];
			$lightseop_options['lightseo_home_description'] = $_POST['lightseo_home_description'];
			$lightseop_options['lightseo_home_keywords'] = $_POST['lightseo_home_keywords'];
			$lightseop_options['lightseo_max_words_excerpt'] = $_POST['lightseo_max_words_excerpt'];
			$lightseop_options['lightseo_rewrite_titles'] = $_POST['lightseo_rewrite_titles'];
			$lightseop_options['lightseo_post_title_format'] = $_POST['lightseo_post_title_format'];
			$lightseop_options['lightseo_page_title_format'] = $_POST['lightseo_page_title_format'];
			$lightseop_options['lightseo_category_title_format'] = $_POST['lightseo_category_title_format'];
			$lightseop_options['lightseo_archive_title_format'] = $_POST['lightseo_archive_title_format'];
			$lightseop_options['lightseo_tag_title_format'] = $_POST['lightseo_tag_title_format'];
			$lightseop_options['lightseo_search_title_format'] = $_POST['lightseo_search_title_format'];
			$lightseop_options['lightseo_description_format'] = $_POST['lightseo_description_format'];
			$lightseop_options['lightseo_404_title_format'] = $_POST['lightseo_404_title_format'];
			$lightseop_options['lightseo_paged_format'] = $_POST['lightseo_paged_format'];
			$lightseop_options['lightseo_use_categories'] = $_POST['lightseo_use_categories'];
			$lightseop_options['lightseo_dynamic_postspage_keywords'] = $_POST['lightseo_dynamic_postspage_keywords'];
			$lightseop_options['lightseo_category_noindex'] = $_POST['lightseo_category_noindex'];
			$lightseop_options['lightseo_archive_noindex'] = $_POST['lightseo_archive_noindex'];
			$lightseop_options['lightseo_tags_noindex'] = $_POST['lightseo_tags_noindex'];
			$lightseop_options['lightseo_generate_descriptions'] = $_POST['lightseo_generate_descriptions'];
			$lightseop_options['lightseo_cap_cats'] = $_POST['lightseo_cap_cats'];
			$lightseop_options['lightseo_debug_info'] = $_POST['lightseo_debug_info'];
			$lightseop_options['lightseo_post_meta_tags'] = $_POST['lightseo_post_meta_tags'];
			$lightseop_options['lightseo_page_meta_tags'] = $_POST['lightseo_page_meta_tags'];
			$lightseop_options['lightseo_home_meta_tags'] = $_POST['lightseo_home_meta_tags'];
			$lightseop_options['lightseo_home_google_site_verification_meta_tag'] = $_POST['lightseo_home_google_site_verification_meta_tag'];
			$lightseop_options['lightseo_ex_pages'] = $_POST['lightseo_ex_pages'];
			$lightseop_options['lightseo_use_tags_as_keywords'] = $_POST['lightseo_use_tags_as_keywords'];

			update_option('lightseop_options', $lightseop_options);

			if (function_exists('wp_cache_flush'))
			{
				wp_cache_flush();
			}
		}
		
		// TODO: Important, I can't change the four textareas for the additional headers until I change the whole concept in this fields. I need to do it.
?>
<?php if ($message) : ?>
  <div id="message" class="updated fade">
    <p>
      <?php echo $message; ?>
    </p>
  </div>
<?php endif; ?>
  <div id="dropmessage" class="updated" style="display:none;"></div>
  <div class="wrap">
    <h2>
      <?php _e('Light SEO Plugin Options', 'lightseo'); ?>
    </h2>
    <div style="clear:both;"></div>
<script type="text/javascript">
function toggleVisibility(id)
{
  var e = document.getElementById(id);

  if(e.style.display == 'block')
    e.style.display = 'none';
  else
    e.style.display = 'block';
}
</script>
    <form name="dofollow" action="" method="post">
      <table class="form-table">
        <?php $lightseop_options = get_option('lightseop_options'); ?>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_home_title_tip');">
              <?php _e('Home Title:', 'light_seo')?>
            </a>
          </th>
          <td>
            <textarea cols="57" rows="2" name="lightseo_home_title"><?php echo esc_attr(stripcslashes($lightseop_options['lightseo_home_title']))?></textarea>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_home_title_tip">
              <?php _e('As the name implies, this will be the title of your homepage. This is independent of any other option. If not set, the default blog title will get used.', 'light_seo')?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_home_description_tip');">
              <?php _e('Home Description:', 'light_seo')?>
            </a>
          </th>
          <td>
            <textarea cols="57" rows="2" name="lightseo_home_description"><?php echo esc_attr(stripcslashes($lightseop_options['lightseo_home_description']))?></textarea>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_home_description_tip">
              <?php _e('The META description for your homepage. Independent of any other options, the default is no META description at all if this is not set.', 'light_seo')?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_home_keywords_tip');">
              <?php _e('Home Keywords (comma separated):', 'light_seo')?>
            </a>
          </th>
          <td>
            <textarea cols="57" rows="2" name="lightseo_home_keywords"><?php echo esc_attr(stripcslashes($lightseop_options['lightseo_home_keywords'])); ?></textarea>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_home_keywords_tip">
              <?php _e("A comma separated list of your most important keywords for your site that will be written as META keywords on your homepage. Don't stuff everything in here.", 'light_seo')?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_can_tip');">
              <?php _e('Canonical URLs:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input type="checkbox" name="lightseo_can" <?php if ($lightseop_options['lightseo_can']) echo 'checked="checked"'; ?>/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_can_tip">
              <?php _e("This option will automatically generate Canonical URLS for your entire WordPress installation.  This will help to prevent duplicate content penalties by <a href='http://googlewebmastercentral.blogspot.com/2009/02/specify-your-canonical.html' target='_blank'>Google</a>.", 'light_seo')?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_rewrite_titles_tip');">
              <?php _e('Rewrite Titles:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input type="checkbox" name="lightseo_rewrite_titles" <?php if ($lightseop_options['lightseo_rewrite_titles']) echo 'checked="checked"'; ?>/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_rewrite_titles_tip">
              <?php _e("Note that this is all about the title tag. This is what you see in your browser's window title bar. This is NOT visible on a page, only in the window title bar and of course in the source. If set, all page, post, category, search and archive page titles get rewritten. You can specify the format for most of them. For example: The default templates puts the title tag of posts like this: Blog Archive >> Blog Name >> Post Title (maybe I've overdone slightly). This is far from optimal. With the default post title format, Rewrite Title rewrites this to Post Title | Blog Name. If you have manually defined a title (in one of the text fields for All in One SEO Plugin input) this will become the title of your post in the format string.", 'light_seo')?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_post_title_format_tip');">
              <?php _e('Post Title Format:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input size="59" name="lightseo_post_title_format" value="<?php echo esc_attr(stripcslashes($lightseop_options['lightseo_post_title_format'])); ?>"/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_post_title_format_tip">
<?php
_e('The following macros are supported:', 'light_seo');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'light_seo'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'light_seo'); echo('</li>');
echo('<li>'); _e('%post_title% - The original title of the post', 'light_seo'); echo('</li>');
echo('<li>'); _e('%category_title% - The (main) category of the post', 'light_seo'); echo('</li>');
echo('<li>'); _e('%category% - Alias for %category_title%', 'light_seo'); echo('</li>');
echo('<li>'); _e("%post_author_login% - This post's author' login", 'light_seo'); echo('</li>');
echo('<li>'); _e("%post_author_nicename% - This post's author' nicename", 'light_seo'); echo('</li>');
echo('<li>'); _e("%post_author_firstname% - This post's author' first name (capitalized)", 'light_seo'); echo('</li>');
echo('<li>'); _e("%post_author_lastname% - This post's author' last name (capitalized)", 'light_seo'); echo('</li>');
echo('</ul>');
?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_page_title_format_tip');">
              <?php _e('Page Title Format:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input size="59" name="lightseo_page_title_format" value="<?php echo esc_attr(stripcslashes($lightseop_options['lightseo_page_title_format'])); ?>"/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_page_title_format_tip">
<?php
_e('The following macros are supported:', 'light_seo');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'light_seo'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'light_seo'); echo('</li>');
echo('<li>'); _e('%page_title% - The original title of the page', 'light_seo'); echo('</li>');
echo('<li>'); _e("%page_author_login% - This page's author' login", 'light_seo'); echo('</li>');
echo('<li>'); _e("%page_author_nicename% - This page's author' nicename", 'light_seo'); echo('</li>');
echo('<li>'); _e("%page_author_firstname% - This page's author' first name (capitalized)", 'light_seo'); echo('</li>');
echo('<li>'); _e("%page_author_lastname% - This page's author' last name (capitalized)", 'light_seo'); echo('</li>');
echo('</ul>');
?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_category_title_format_tip');">
              <?php _e('Category Title Format:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input size="59" name="lightseo_category_title_format" value="<?php echo esc_attr(stripcslashes($lightseop_options['lightseo_category_title_format'])); ?>"/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_category_title_format_tip">
<?php
_e('The following macros are supported:', 'light_seo');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'light_seo'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'light_seo'); echo('</li>');
echo('<li>'); _e('%category_title% - The original title of the category', 'light_seo'); echo('</li>');
echo('<li>'); _e('%category_description% - The description of the category', 'light_seo'); echo('</li>');
echo('</ul>');
?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_archive_title_format_tip');">
              <?php _e('Archive Title Format:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input size="59" name="lightseo_archive_title_format" value="<?php echo esc_attr(stripcslashes($lightseop_options['lightseo_archive_title_format'])); ?>"/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_archive_title_format_tip">
<?php
_e('The following macros are supported:', 'light_seo');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'light_seo'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'light_seo'); echo('</li>');
echo('<li>'); _e('%date% - The original archive title given by wordpress, e.g. "2007" or "2007 August"', 'light_seo'); echo('</li>');
echo('</ul>');
?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_tag_title_format_tip');">
              <?php _e('Tag Title Format:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input size="59" name="lightseo_tag_title_format" value="<?php echo esc_attr(stripcslashes($lightseop_options['lightseo_tag_title_format'])); ?>"/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_tag_title_format_tip">
<?php
_e('The following macros are supported:', 'light_seo');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'light_seo'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'light_seo'); echo('</li>');
echo('<li>'); _e('%tag% - The name of the tag', 'light_seo'); echo('</li>');
echo('</ul>');
?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_search_title_format_tip');">
              <?php _e('Search Title Format:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input size="59" name="lightseo_search_title_format" value="<?php echo esc_attr(stripcslashes($lightseop_options['lightseo_search_title_format'])); ?>"/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_search_title_format_tip">
<?php
_e('The following macros are supported:', 'light_seo');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'light_seo'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'light_seo'); echo('</li>');
echo('<li>'); _e('%search% - What was searched for', 'light_seo'); echo('</li>');
echo('</ul>');
?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_description_format_tip');">
              <?php _e('Description Format:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input size="59" name="lightseo_description_format" value="<?php echo esc_attr(stripcslashes($lightseop_options['lightseo_description_format'])); ?>" />
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_description_format_tip">
<?php
_e('The following macros are supported:', 'light_seo');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'light_seo'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'light_seo'); echo('</li>');
echo('<li>'); _e('%description% - The original description as determined by the plugin, e.g. the excerpt if one is set or an auto-generated one if that option is set', 'light_seo'); echo('</li>');
echo('<li>'); _e('%wp_title% - The original wordpress title, e.g. post_title for posts', 'light_seo'); echo('</li>');
echo('</ul>');
?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_404_title_format_tip');">
              <?php _e('404 Title Format:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input size="59" name="lightseo_404_title_format" value="<?php echo esc_attr(stripcslashes($lightseop_options['lightseo_404_title_format'])); ?>"/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_404_title_format_tip">
<?php
_e('The following macros are supported:', 'light_seo');
echo('<ul>');
echo('<li>'); _e('%blog_title% - Your blog title', 'light_seo'); echo('</li>');
echo('<li>'); _e('%blog_description% - Your blog description', 'light_seo'); echo('</li>');
echo('<li>'); _e('%request_url% - The original URL path, like "/url-that-does-not-exist/"', 'light_seo'); echo('</li>');
echo('<li>'); _e('%request_words% - The URL path in human readable form, like "Url That Does Not Exist"', 'light_seo'); echo('</li>');
echo('<li>'); _e('%404_title% - Additional 404 title input"', 'light_seo'); echo('</li>');
echo('</ul>');
?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_paged_format_tip');">
              <?php _e('Paged Format:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input size="59" name="lightseo_paged_format" value="<?php echo esc_attr(stripcslashes($lightseop_options['lightseo_paged_format'])); ?>"/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_paged_format_tip">
<?php
_e('This string gets appended/prepended to titles when they are for paged index pages (like home or archive pages).', 'light_seo');
_e('The following macros are supported:', 'light_seo');
echo('<ul>');
echo('<li>'); _e('%page% - The page number', 'light_seo'); echo('</li>');
echo('</ul>');
?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_use_categories_tip');">
              <?php _e('Use Categories for META keywords:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input type="checkbox" name="lightseo_use_categories" <?php if ($lightseop_options['lightseo_use_categories']) echo 'checked="checked"'; ?>/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_use_categories_tip">
              <?php _e('Check this if you want your categories for a given post used as the META keywords for this post (in addition to any keywords and tags you specify on the post edit page).', 'light_seo')?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_use_tags_as_keywords_tip');">
              <?php _e('Use Tags for META keywords:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input type="checkbox" name="lightseo_use_tags_as_keywords" <?php if ($lightseop_options['lightseo_use_tags_as_keywords']) echo 'checked="checked"'; ?>/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_use_tags_as_keywords_tip">
              <?php _e('Check this if you want your tags for a given post used as the META keywords for this post (in addition to any keywords you specify on the post edit page).', 'light_seo')?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_dynamic_postspage_keywords_tip');">
              <?php _e('Dynamically Generate Keywords for Posts Page:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input type="checkbox" name="lightseo_dynamic_postspage_keywords" <?php if ($lightseop_options['lightseo_dynamic_postspage_keywords']) echo 'checked="checked"'; ?>/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_dynamic_postspage_keywords_tip">
              <?php _e('Check this if you want your keywords on a custom posts page (set it in options->reading) to be dynamically generated from the keywords of the posts showing on that page.  If unchecked, it will use the keywords set in the edit page screen for the posts page.', 'light_seo') ?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_category_noindex_tip');">
              <?php _e('Use noindex for Categories:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input type="checkbox" name="lightseo_category_noindex" <?php if ($lightseop_options['lightseo_category_noindex']) echo 'checked="checked"'; ?>/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_category_noindex_tip">
              <?php _e('Check this for excluding category pages from being crawled. Useful for avoiding duplicate content.', 'light_seo')?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_archive_noindex_tip');">
              <?php _e('Use noindex for Archives:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input type="checkbox" name="lightseo_archive_noindex" <?php if ($lightseop_options['lightseo_archive_noindex']) echo 'checked="checked"'; ?>/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_archive_noindex_tip">
              <?php _e('Check this for excluding archive pages from being crawled. Useful for avoiding duplicate content.', 'light_seo')?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_tags_noindex_tip');">
              <?php _e('Use noindex for Tag Archives:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input type="checkbox" name="lightseo_tags_noindex" <?php if ($lightseop_options['lightseo_tags_noindex']) echo 'checked="checked"'; ?>/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_tags_noindex_tip">
              <?php _e('Check this for excluding tag pages from being crawled. Useful for avoiding duplicate content.', 'light_seo')?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_generate_descriptions_tip');">
              <?php _e('Autogenerate Descriptions:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input type="checkbox" name="lightseo_generate_descriptions" <?php if ($lightseop_options['lightseo_generate_descriptions']) echo 'checked="checked"'; ?>/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_generate_descriptions_tip">
              <?php _e("Check this and your META descriptions will get autogenerated if there's no excerpt.", 'light_seo')?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_cap_cats_tip');">
              <?php _e('Capitalize Category Titles:', 'light_seo')?>
            </a>
          </th>
          <td>
            <input type="checkbox" name="lightseo_cap_cats" <?php if ($lightseop_options['lightseo_cap_cats']) echo 'checked="checked"'; ?>/>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_cap_cats_tip">
              <?php _e("Check this and Category Titles will have the first letter of each word capitalized.", 'light_seo')?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_ex_pages_tip');">
              <?php _e('Exclude Pages:', 'light_seo')?>
            </a>
          </th>
          <td>
            <textarea cols="57" rows="2" name="lightseo_ex_pages"><?php echo esc_attr(stripcslashes($lightseop_options['lightseo_ex_pages']))?></textarea>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_ex_pages_tip">
              <?php _e("Enter any comma separated pages here to be excluded by All in One SEO Pack.  This is helpful when using plugins which generate their own non-WordPress dynamic pages.  Ex: <em>/forum/,/contact/</em>  For instance, if you want to exclude the virtual pages generated by a forum plugin, all you have to do is give forum or /forum or /forum/ or and any URL with the word \"forum\" in it, such as http://mysite.com/forum or http://mysite.com/forum/someforumpage will be excluded from Light SEO.", 'light_seo')?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_post_meta_tags_tip');">
              <?php _e('Additional Post Headers:', 'light_seo')?>
            </a>
          </th>
          <td>
            <textarea cols="57" rows="2" name="lightseo_post_meta_tags"><?php echo htmlspecialchars(stripcslashes($lightseop_options['lightseo_post_meta_tags']))?></textarea>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_post_meta_tags_tip">
<?php
_e('What you enter here will be copied verbatim to your header on post pages. You can enter whatever additional headers you want here, even references to stylesheets.', 'light_seo');
echo '<br/>';
_e('NOTE: This field currently only support meta tags.', 'light_seo');
?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_page_meta_tags_tip');">
              <?php _e('Additional Page Headers:', 'light_seo')?>
            </a>
          </th>
          <td>
            <textarea cols="57" rows="2" name="lightseo_page_meta_tags"><?php echo htmlspecialchars(stripcslashes($lightseop_options['lightseo_page_meta_tags']))?></textarea>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_page_meta_tags_tip">
<?php
_e('What you enter here will be copied verbatim to your header on pages. You can enter whatever additional headers you want here, even references to stylesheets.', 'light_seo');
echo '<br/>';
_e('NOTE: This field currently only support meta tags.', 'light_seo');
?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_home_meta_tags_tip');">
              <?php _e('Additional Home Headers:', 'light_seo')?>
            </a>
          </th>
          <td>
            <textarea cols="57" rows="2" name="lightseo_home_meta_tags"><?php echo htmlspecialchars(stripcslashes($lightseop_options['lightseo_home_meta_tags']))?></textarea>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_home_meta_tags_tip">
<?php
_e('What you enter here will be copied verbatim to your header on the home page. You can enter whatever additional headers you want here, even references to stylesheets.', 'light_seo');
echo '<br/>';
_e('NOTE: This field currently only support meta tags.', 'light_seo');
?>
            </div>
          </td>
        </tr>
        <tr>
          <th scope="row" style="text-align:right; vertical-align:top;">
            <a style="cursor:pointer;" title="<?php _e('Click for Help!', 'light_seo')?>" onclick="toggleVisibility('lightseo_home_google_site_verification_meta_tag_tip');">
              <?php _e('Google Verification Meta Tag:', 'light_seo')?>
            </a>
          </th>
          <td>
		    <textarea cols="65" rows="1" name="lightseo_home_google_site_verification_meta_tag"><?php echo htmlspecialchars(stripcslashes($lightseop_options['lightseo_home_google_site_verification_meta_tag']))?></textarea>
            <div style="max-width:500px; text-align:left; display:none" id="lightseo_home_google_site_verification_meta_tag_tip">
<?php
_e('What you enter here will be copied verbatim to your header on the home page. Webmaster Tools provides the meta tag in XHTML syntax.', 'light_seo');
echo('<br/>');
echo('1. '); _e('On the Webmaster Tools Home page, click Verify this site next to the site you want.', 'light_seo');
echo('<br/>');
echo('2. '); _e('In the Verification method list, select Meta tag, and follow the steps on your screen.', 'light_seo');
echo('<br/>');
_e('Once you have added the tag to your home page, click Verify.', 'light_seo');
?>
            </div>
          </td>
        </tr>
      </table>
      <p class="submit">
        <?php if($lightseop_options) {  ?>
        <input type="hidden" name="action" value="lightseo_update" />
        <input type="hidden" name="nonce-lightseop" value="<?php echo esc_attr(wp_create_nonce('lightseop-nonce')); ?>" />
        <input type="hidden" name="page_options" value="lightseo_home_description" />
        <input type="submit" class='button-primary' name="Submit" value="<?php _e('Update Options', 'light_seo')?> &raquo;" />
        <input type="submit" class='button-primary' name="Submit_Default" value="<?php _e('Reset Settings to Defaults', 'light_seo')?> &raquo;" />
      </p>
      <?php } ?>
    </form>
  </div>
  <?php
	} // options_panel
} // end Light_SEO class

global $lightseop_options;

if (!get_option('lightseop_options'))
{
	lightseop_mrt_mkarry();
}

$lightseop_options = get_option('lightseop_options');

function lightseop_mrt_mkarry()
{
	$nlightseop_options = array(
		"lightseo_can"=>1,
		"lightseo_home_title"=>null,
		"lightseo_home_description"=>'',
		"lightseo_home_keywords"=>null,
		"lightseo_max_words_excerpt"=>'something',
		"lightseo_rewrite_titles"=>1,
		"lightseo_post_title_format"=>'%post_title% | %blog_title%',
		"lightseo_page_title_format"=>'%page_title% | %blog_title%',
		"lightseo_category_title_format"=>'%category_title% | %blog_title%',
		"lightseo_archive_title_format"=>'%date% | %blog_title%',
		"lightseo_tag_title_format"=>'%tag% | %blog_title%',
		"lightseo_search_title_format"=>'%search% | %blog_title%',
		"lightseo_description_format"=>'%description%',
		"lightseo_404_title_format"=>'Nothing found for %request_words%',
		"lightseo_paged_format"=>' - Part %page%',
		"lightseo_use_categories"=>0,
		"lightseo_dynamic_postspage_keywords"=>1,
		"lightseo_category_noindex"=>1,
		"lightseo_archive_noindex"=>1,
		"lightseo_tags_noindex"=>0,
		"lightseo_cap_cats"=>1,
		"lightseo_generate_descriptions"=>1,
		"lightseo_debug_info"=>null,
		"lightseo_post_meta_tags"=>'',
		"lightseo_page_meta_tags"=>'',
		"lightseo_home_meta_tags"=>'',
		'lightseo_home_google_site_verification_meta_tag' => '',
		'lightseo_use_tags_as_keywords' => 1);

	if (get_option('lightseo_post_title_format'))
	{
		foreach ($nlightseop_options as $lightseop_opt_name => $value )
		{
			if ($lightseop_oldval = get_option($lightseop_opt_name))
			{
				$nlightseop_options[$lightseop_opt_name] = $lightseop_oldval;
			}
			
			if ($lightseop_oldval == '')
			{
				$nlightseop_options[$lightseop_opt_name] = '';
			}
        
			delete_option($lightseop_opt_name);
		}
	}

	add_option('lightseop_options',$nlightseop_options);

	echo "<div class='updated fade' style='background-color:green;border-color:green;'><p><strong>Updating Light SEO configuration options in database</strong></p></div>";
}

function lightseop_list_pages($content)
{
	$url = preg_replace(array('/\//', '/\./', '/\-/'), array('\/', '\.', '\-'), get_option('siteurl'));
	$pattern = '/<li class="page_item page-item-(\d+)([^\"]*)"><a href=\"([^\"]+)" title="([^\"]+)">([^<]+)<\/a>/i';

	return preg_replace_callback($pattern, "lightseop_filter_callback", $content);
}

function lightseop_filter_callback($matches)
{
	if ($matches[1] && !empty($matches[1]))
		$postID = $matches[1];
		
	if (empty($postID))
		$postID = get_option("page_on_front");
		
	$title_attrib = stripslashes(get_post_meta($postID, '_lightseop_titleatr', true));
	$menulabel = stripslashes(get_post_meta($postID, '_lightseop_menulabel', true));
	
	if (empty($menulabel))
		$menulabel = $matches[4];
		
	if (!empty($title_attrib)) :
		$filtered = '<li class="page_item page-item-' . $postID.$matches[2] . '"><a href="' . esc_attr($matches[3]) . '" title="' . esc_attr($title_attrib) . '">' . wp_kses(esc_html($menulabel), array()) . '</a>';
	else :
    	$filtered = '<li class="page_item page-item-' . $postID.$matches[2] . '"><a href="' . esc_attr($matches[3]) . '" title="' . esc_attr($matches[4]) . '">' . wp_kses(esc_html($menulabel), array()) . '</a>';
	endif;
	
	return $filtered;
}

function lightseo_meta()
{
	global $post;
	
	$post_id = $post;
	
	if (is_object($post_id))
	{
		$post_id = $post_id->ID;
	}
	
 	$keywords = esc_attr(htmlspecialchars(stripcslashes(get_post_meta($post_id, '_lightseop_keywords', true))));
	$title = esc_attr(htmlspecialchars(stripcslashes(get_post_meta($post_id, '_lightseop_title', true))));
	$description = esc_attr(htmlspecialchars(stripcslashes(get_post_meta($post_id, '_lightseop_description', true))));
	$lightseo_meta = esc_attr(htmlspecialchars(stripcslashes(get_post_meta($post_id, '_lightseo_meta', true))));
	$lightseo_disable = esc_attr(htmlspecialchars(stripcslashes(get_post_meta($post_id, '_lightseop_disable', true))));
	$lightseo_titleatr = esc_attr(htmlspecialchars(stripcslashes(get_post_meta($post_id, '_lightseop_titleatr', true))));
	$lightseo_menulabel = esc_attr(htmlspecialchars(stripcslashes(get_post_meta($post_id, '_lightseop_menulabel', true))));	
	
?>
<script type="text/javascript">
function countChars(field, cntfield)
{
  cntfield.value = field.value.length;
}
</script>
  <input value="lightseo_edit" type="hidden" name="lightseo_edit" />
  <input type="hidden" name="nonce-lightseop-edit" value="<?php echo esc_attr(wp_create_nonce('edit-lightseop-nonce')) ?>" />
  <table style="margin-bottom:40px">
    <tr>
      <th style="text-align:left;" colspan="2"></th>
    </tr>
    <tr>
      <th scope="row" style="text-align:right;">
        <?php _e('Title:', 'light_seo') ?>
      </th>
      <td>
        <input value="<?php echo $title ?>" type="text" name="lightseo_title" size="62" onkeydown="countChars(document.post.lightseo_title,document.post.lengthT)" onkeyup="countChars(document.post.lightseo_title,document.post.lengthT)" />
        <br />
        <input readonly="readonly" type="text" name="lengthT" size="3" maxlength="3" style="text-align:center;" value="<?php echo strlen($title);?>" />
        <?php _e(' characters. Most search engines use a maximum of 60 chars for the title.', 'light_seo') ?>
      </td>
    </tr>
    <tr>
      <th scope="row" style="text-align:right;">
        <?php _e('Description:', 'light_seo') ?>
      </th>
      <td>
        <textarea name="lightseo_description" rows="3" cols="60" onkeydown="countChars(document.post.lightseo_description,document.post.length1)"
          onkeyup="countChars(document.post.lightseo_description,document.post.length1)"><?php echo $description ?></textarea>
        <br />
        <input readonly="readonly" type="text" name="length1" size="3" maxlength="3" value="<?php echo strlen($description);?>" />
        <?php _e(' characters. Most search engines use a maximum of 160 chars for the description.', 'light_seo') ?>
      </td>
    </tr>
    <tr>
      <th scope="row" style="text-align:right;">
        <?php _e('Keywords (comma separated):', 'light_seo') ?>
      </th>
      <td>
        <input value="<?php echo $keywords ?>" type="text" name="lightseo_keywords" size="62"/>
      </td>
    </tr>
<?php if($post->post_type == 'page') { ?>
    <tr>
      <th scope="row" style="text-align:right;">
        <?php _e('Title Attribute:', 'light_seo') ?>
      </th>
      <td>
        <input value="<?php echo $lightseo_titleatr ?>" type="text" name="lightseo_titleatr" size="62"/>
      </td>
    </tr>
    <tr>
      <th scope="row" style="text-align:right;">
        <?php _e('Menu Label:', 'light_seo') ?>
      </th>
      <td>
        <input value="<?php echo $lightseo_menulabel ?>" type="text" name="lightseo_menulabel" size="62"/>
      </td>
    </tr>
<?php } ?>
    <tr>
      <th scope="row" style="text-align:right; vertical-align:top;">
        <?php _e('Disable on this page/post:', 'light_seo')?>
      </th>
      <td>
        <input type="checkbox" name="lightseo_disable" <?php if ($lightseo_disable) echo 'checked="checked"'; ?>/>
      </td>
    </tr>
  </table>
<?php
}

function lightseo_meta_box_add()
{
	add_meta_box('lightseo',__('Light SEO', 'light_seo'), 'lightseo_meta', 'post');
	add_meta_box('lightseo',__('Light SEO', 'light_seo'), 'lightseo_meta', 'page');
}

if ($lightseop_options['lightseo_can'] == '1' || $lightseop_options['lightseo_can'] == 'on')
{
	remove_action('wp_head', 'rel_canonical');
}

add_action('admin_menu', 'lightseo_meta_box_add');
add_action('wp_list_pages', 'lightseop_list_pages');

$lightseo = new Light_SEO();

add_action('init', array($lightseo, 'init'));
add_action('template_redirect', array($lightseo, 'template_redirect'));
add_action('wp_head', array($lightseo, 'wp_head'));
add_action('edit_post', array($lightseo, 'post_meta_tags'));
add_action('publish_post', array($lightseo, 'post_meta_tags'));
add_action('save_post', array($lightseo, 'post_meta_tags'));
add_action('edit_page_form', array($lightseo, 'post_meta_tags'));
add_action('admin_menu', array($lightseo, 'admin_menu'));

?>

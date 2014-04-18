<?php

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

/**
 * nggShowImageBrowser()
 *
 * @access public
 * @param int|string $galleryID or gallery name
 * @param string $template (optional) name for a template file, look for imagebrowser-$template
 * @return the content
 */
function nggShowImageBrowser($galleryID, $template = '') {

    global $wpdb;

    $ngg_options = nggGallery::get_option('ngg_options');

    //Set sort order value, if not used (upgrade issue)
    $ngg_options['galSort'] = ($ngg_options['galSort']) ? $ngg_options['galSort'] : 'pid';
    $ngg_options['galSortDir'] = ($ngg_options['galSortDir'] == 'DESC') ? 'DESC' : 'ASC';

    // get the pictures
    $picturelist = nggdb::get_gallery($galleryID, $ngg_options['galSort'], $ngg_options['galSortDir']);

    if ( is_array($picturelist) )
        $out = nggCreateImageBrowser($picturelist, $template);
    else
        $out = __('[Gallery not found]','nggallery');

    $out = apply_filters('ngg_show_imagebrowser_content', $out, $galleryID);

    return $out;

}

/**
 * nggCreateImageBrowser()
 *
 * @access internal
 * @param array $picturelist
 * @param string $template (optional) name for a template file, look for imagebrowser-$template
 * @return the content
 */
function nggCreateImageBrowser($picturelist, $template = '') {

    global $nggRewrite, $ngg;

    require_once( dirname (__FILE__) . '/lib/meta.php' );

    // $_GET from wp_query
    $pid  = get_query_var('pid');

    // we need to know the current page id
    $current_page = (get_the_ID() == false) ? 0 : get_the_ID();

    // create a array with id's for better walk inside
    foreach ($picturelist as $picture)
        $picarray[] = $picture->pid;

    $total = count($picarray);

    if ( !empty( $pid )) {
        if ( is_numeric($pid) )
            $act_pid = intval($pid);
        else {
            // in the case it's a slug we need to search for the pid
            foreach ($picturelist as $key => $picture) {
                if ($picture->image_slug == $pid) {
                    $act_pid = $key;
                    break;
                }
            }
        }
    } else {
        reset($picarray);
        $act_pid = current($picarray);
    }

    // get ids for back/next
    $key = array_search($act_pid, $picarray);
    if (!$key) {
        $act_pid = reset($picarray);
        $key = key($picarray);
    }
    $back_pid = ( $key >= 1 ) ? $picarray[$key-1] : end($picarray) ;
    $next_pid = ( $key < ($total-1) ) ? $picarray[$key+1] : reset($picarray) ;

    // get the picture data
    $picture = nggdb::find_image($act_pid);

    // if we didn't get some data, exit now
    if ($picture == null)
        return;

    // add more variables for render output
    $picture->href_link = $picture->get_href_link();
    $args ['pid'] = ($ngg->options['usePermalinks']) ? $picturelist[$back_pid]->image_slug : $back_pid;
    $picture->previous_image_link = $nggRewrite->get_permalink( $args );
    $picture->previous_pid = $back_pid;
    $args ['pid'] = ($ngg->options['usePermalinks']) ? $picturelist[$next_pid]->image_slug : $next_pid;
    $picture->next_image_link  = $nggRewrite->get_permalink( $args );
    $picture->next_pid = $next_pid;
    $picture->number = $key + 1;
    $picture->total = $total;
    $picture->linktitle = ( empty($picture->description) ) ? ' ' : htmlspecialchars ( stripslashes(nggGallery::i18n($picture->description, 'pic_' . $picture->pid . '_description')) );
    $picture->alttext = ( empty($picture->alttext) ) ?  ' ' : html_entity_decode ( stripslashes(nggGallery::i18n($picture->alttext, 'pic_' . $picture->pid . '_alttext')) );
    $picture->description = ( empty($picture->description) ) ? ' ' : html_entity_decode ( stripslashes(nggGallery::i18n($picture->description, 'pic_' . $picture->pid . '_description')) );
    $picture->anchor = 'ngg-imagebrowser-' . $picture->galleryid . '-' . $current_page;

    // filter to add custom content for the output
    $picture = apply_filters('ngg_image_object', $picture, $act_pid);

    // let's get the meta data
    $meta = new nggMeta($act_pid);
    $meta->sanitize();
    $exif = $meta->get_EXIF();
    $iptc = $meta->get_IPTC();
    $xmp  = $meta->get_XMP();
    $db   = $meta->get_saved_meta();

    //if we get no exif information we try the database
    $exif = ($exif == false) ? $db : $exif;

    // look for imagebrowser-$template.php or pure imagebrowser.php
    $filename = ( empty($template) ) ? 'imagebrowser' : 'imagebrowser-' . $template;

    // create the output
    $out = nggGallery::capture ( $filename , array ('image' => $picture , 'meta' => $meta, 'exif' => $exif, 'iptc' => $iptc, 'xmp' => $xmp, 'db' => $db) );

    return $out;

}

/**
 * nggShowRelatedGallery() - create a gallery based on the tags
 *
 * @access public
 * @param string $taglist list of tags as csv
 * @param integer $maxImages (optional) limit the number of images to show
 * @return the content
 */
function nggShowRelatedGallery($taglist, $maxImages = 0) {

    $ngg_options = nggGallery::get_option('ngg_options');

    // get now the related images
    $picturelist = nggTags::find_images_for_tags($taglist, 'RAND');

    // go on if not empty
    if ( empty($picturelist) )
        return;

    // cut the list to maxImages
    if ( $maxImages > 0 )
        array_splice($picturelist, $maxImages);

    // *** build the gallery output
    $out   = '<div class="ngg-related-gallery">';
    foreach ($picturelist as $picture) {

        // get the effect code
        $thumbcode = $picture->get_thumbcode( __('Related images for', 'nggallery') . ' ' . get_the_title());

        $out .= '<a href="' . $picture->imageURL . '" title="' . stripslashes(nggGallery::i18n($picture->description, 'pic_' . $picture->pid . '_description')) . '" ' . $thumbcode . ' >';
        $out .= '<img title="' . stripslashes(nggGallery::i18n($picture->alttext, 'pic_' . $picture->pid . '_alttext')) . '" alt="' . stripslashes(nggGallery::i18n($picture->alttext, 'pic_' . $picture->pid . '_alttext')) . '" src="' . $picture->thumbURL . '" />';
        $out .= '</a>' . "\n";
    }
    $out .= '</div>' . "\n";

    $out = apply_filters('ngg_show_related_gallery_content', $out, $taglist);

    return $out;
}

/**
 * nggShowRelatedImages() - return related images based on category or tags
 *
 * @access public
 * @param string $type could be 'tags' or 'category'
 * @param integer $maxImages of images
 * @return the content
 */
function nggShowRelatedImages($type = '', $maxImages = 0) {
    $ngg_options = nggGallery::get_option('ngg_options');

    if ($type == '') {
        $type = $ngg_options['appendType'];
        $maxImages = $ngg_options['maxImages'];
    }

    $sluglist = array();

    switch ($type) {
        case 'tags':
            if (function_exists('get_the_tags')) {
                $taglist = get_the_tags();

                if (is_array($taglist)) {
                    foreach ($taglist as $tag) {
                        $sluglist[] = $tag->slug;
                    }
                }
            }
        break;

        case 'category':
            $catlist = get_the_category();

            if (is_array($catlist)) {
                foreach ($catlist as $cat) {
                    $sluglist[] = $cat->category_nicename;
                }
            }
        break;
    }

    $sluglist = implode(',', $sluglist);
    $out = nggShowRelatedGallery($sluglist, $maxImages);

    return $out;
}

/**
 * Template function for theme authors
 *
 * @access public
 * @param string  (optional) $type could be 'tags' or 'category'
 * @param integer (optional) $maxNumbers of images
 * @return void
 */
function the_related_images($type = 'tags', $maxNumbers = 7) {
    echo nggShowRelatedImages($type, $maxNumbers);
}

/**
 * Wrapper to I_Displayed_Gallery_Renderer->display_images(); this will display
 * a basic thumbnails gallery
 *
 * @param int $galleryID Gallery ID
 * @param string $template Path to template file
 * @param bool $images_per_page Basic thumbnails setting
 */
function nggShowGallery($galleryID, $template = '', $images_per_page = FALSE)
{
    $args = array(
        'source' => 'galleries',
        'container_ids' => $galleryID
    );

    if (apply_filters('ngg_show_imagebrowser_first', FALSE, $galleryID))
        $args['display_type'] = NGG_BASIC_IMAGEBROWSER;
    else
        $args['display_type'] = NGG_BASIC_THUMBNAILS;

    if (!empty($template))
        $args['template'] = $template;
    if (!empty($images_per_page))
        $args['images_per_page'] = $images_per_page;

    echo C_Component_Registry::get_instance()
                             ->get_utility('I_Displayed_Gallery_Renderer')
                             ->display_images($args);
}


/**
 * Wrapper to I_Displayed_Gallery_Renderer->display_images(); this will display
 * a basic slideshow gallery
 *
 * @param int $galleryID Gallery ID
 * @param int $width Gallery width
 * @param int $height Gallery height
 */
function nggShowSlideshow($galleryID, $width, $height)
{
    $args = array(
        'source'         => 'galleries',
        'container_ids'  => $galleryID,
        'gallery_width'  => $width,
        'gallery_height' => $height,
        'display_type'   => NGG_BASIC_SLIDESHOW
    );

    echo C_Component_Registry::get_instance()
                             ->get_utility('I_Displayed_Gallery_Renderer')
                             ->display_images($args);
}

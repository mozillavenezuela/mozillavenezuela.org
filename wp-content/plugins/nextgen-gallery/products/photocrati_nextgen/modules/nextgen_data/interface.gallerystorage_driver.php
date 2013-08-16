<?php

interface I_GalleryStorage_Driver
{
	function get_image_sizes();
	function get_image_abspath($image, $size=FALSE);
	function get_full_abspath($image);
    function get_cache_abspath($gallery = FALSE);
	function get_original_abspath($image);
	function get_upload_abspath($gallery=FALSE);
	function get_upload_relpath($gallery=FALSE);
	function get_gallery_abspath($gallery);
	function get_backup_abspath($image);
	function get_image_url($image, $size=FALSE);
	function get_original_url($image);
	function get_full_url($image);
	function get_image_html($image, $size=FALSE);
	function get_original_html($image);
	function get_full_html($image);
	function get_original_dimensions($image);
	function get_full_dimensions($image);
	function backup_image($image);
	function move_images($images, $gallery, $db_entries=TRUE);
	function copy_images($images, $gallery, $db_entries=TRUE);
	function upload_image($gallery, $data=FALSE);
	function get_driver_class_name();
	function generate_image_clone($image_path, $clone_path, $params);
	function generate_image_size($image, $size);
	function generate_thumbnail($image);
	function delete_image($image, $size=FALSE);
}

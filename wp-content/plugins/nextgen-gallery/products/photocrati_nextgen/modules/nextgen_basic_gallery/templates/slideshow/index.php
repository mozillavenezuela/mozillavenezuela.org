<?php $this->start_element('nextgen_gallery.gallery_container', 'container', $displayed_gallery); ?>
<?php if ($show_thumbnail_link): ?>
<!-- Thumbnails Link -->
<div class="slideshowlink">
    <a href='<?php echo $thumbnail_link ?>'><?php echo_h($thumbnail_link_text) ?></a>
</div>
<?php endif ?>

<?php if ($flash_enabled): ?>
	<!-- Display Flash Slideshow -->

	<?php
	// Configure slideshow parameters
	$width = $gallery_width;
	$height = $gallery_height;

		if ($cycle_interval == 0)
			$cycle_interval = 1;
    
    if ($flash_background_color && $flash_background_color[0] == '#')
    	$flash_background_color = substr($flash_background_color, 1);
    	
    if ($flash_text_color && $flash_text_color[0] == '#')
    	$flash_text_color = substr($flash_text_color, 1);
    	
    if ($flash_rollover_color && $flash_rollover_color[0] == '#')
    	$flash_rollover_color = substr($flash_rollover_color, 1);
    	
    if ($flash_screen_color && $flash_screen_color[0] == '#')
    	$flash_screen_color = substr($flash_screen_color, 1);

    // init the flash output
    $swfobject = new swfobject( $flash_path, 'so' . $displayed_gallery_id, $width, $height, '7.0.0', 'false');

    $swfobject->message = '<p>' . __('Slideshows require the&nbsp;<a href="http://www.macromedia.com/go/getflashplayer">Flash Player</a>&nbsp;and a&nbsp;<a href="http://www.mozilla.com/firefox/">browser with Javascript support</a>.', 'nggallery') . '</p>';

    $swfobject->add_params('wmode', 'opaque');
    $swfobject->add_params('allowfullscreen', 'true');
    $swfobject->add_params('bgcolor', $flash_screen_color, 'FFFFFF', 'string', '#');
    $swfobject->add_attributes('styleclass', 'slideshow');
    $swfobject->add_attributes('name', 'so' . $displayed_gallery_id);

    // adding the flash parameter
    //$swfobject->add_flashvars( 'file', urlencode ( trailingslashit ( home_url() ) . 'index.php?callback=imagerotator&gid=' . $displayed_gallery_id ) );
    $swfobject->add_flashvars( 'file', urlencode ( $mediarss_link ) );
    $swfobject->add_flashvars( 'shuffle', $flash_shuffle, 'false', 'bool');

    // option has oposite meaning : true should switch to next image
    $swfobject->add_flashvars( 'linkfromdisplay', !$flash_next_on_click, 'false', 'bool');
    $swfobject->add_flashvars( 'shownavigation', $flash_navigation_bar, 'true', 'bool');
    $swfobject->add_flashvars( 'showicons', $flash_loading_icon, 'true', 'bool');
    $swfobject->add_flashvars( 'kenburns', $flash_slow_zoom, 'false', 'bool');
    $swfobject->add_flashvars( 'overstretch', $flash_stretch_image, 'false', 'string');
    $swfobject->add_flashvars( 'rotatetime', $cycle_interval, 5, 'int');
    $swfobject->add_flashvars( 'transition', $flash_transition_effect, 'random', 'string');
    	
    $swfobject->add_flashvars( 'backcolor', $flash_background_color, 'FFFFFF', 'string', '0x');
    $swfobject->add_flashvars( 'frontcolor', $flash_text_color, '000000', 'string', '0x');
    $swfobject->add_flashvars( 'lightcolor', $flash_rollover_color, '000000', 'string', '0x');
    $swfobject->add_flashvars( 'screencolor', $flash_screen_color, '000000', 'string', '0x');
    if ($flash_watermark_logo) {
		$ngg_options = C_NextGen_Settings::get_instance();
		$swfobject->add_flashvars( 'logo', $ngg_options['wmPath'], '', 'string');
	}


    $swfobject->add_flashvars( 'audio', $flash_background_music, '', 'string');
    $swfobject->add_flashvars( 'width', $width, '260');
    $swfobject->add_flashvars( 'height', $height, '320');
    ?>

    <div class="slideshow" id="gallery_<?php echo_h($displayed_gallery_id) ?>">
        <?php echo $swfobject->output(); ?>
    </div>
    <script type="text/javascript" defer="defer">
        <?php if ($flash_xhtml_validation): ?>
        <!--
        <?php endif ?>
		jQuery(function($){
			<?php echo $swfobject->javascript(); ?>
		});
        <?php if ($flash_xhtml_validation): ?>
        -->
        <?php endif ?>
    </script>

<?php else: ?>
	<!-- Display JQuery Cycle Slideshow -->
	<div class="ngg-slideshow-image-list ngg-slideshow-nojs" id="<?php echo_h($anchor)?>-image-list">
		<?php
    
		$this->include_template('photocrati-nextgen_gallery_display#list/before');
	
		?>
		<?php for ($i=0; $i<count($images); $i++): ?>

			<?php
			// Determine image dimensions
			$image = $images[$i];
			$image_size = $storage->get_original_dimensions($image);

			if ($image_size == null) {
				$image_size['width'] = $image->meta_data['width'];
				$image_size['height'] = $image->meta_data['height'];
			}

			// Determine whether an image is hidden or not
			if (isset($image->hidden) && $image->hidden) {
			  $image->style = 'style="display: none;"';
			}
			else {
				$image->style = '';
			}

			// Determine image aspect ratio
			$image_ratio = $image_size['width'] / $image_size['height'];
			if ($image_ratio > $aspect_ratio) {
				if ($image_size['width'] > $gallery_width) {
					$image_size['width'] = $gallery_width;
					$image_size['height'] = (int) round($gallery_width / $image_ratio);
				}
			}
			else {
				if ($image_size['height'] > $gallery_height) {
					$image_size['width'] = (int) round($gallery_height * $image_ratio);
					$image_size['height'] = $gallery_height;
				}
			}
			?>
			
			<?php
			
			$template_params = array(
					'index' => $i,
					'class' => 'ngg-gallery-slideshow-image'
				);
			$template_params = array_merge(get_defined_vars(), $template_params);
			
			$this->include_template('photocrati-nextgen_gallery_display#image/before', $template_params);
			
			?>
			
				<img data-image-id='<?php echo esc_attr($image->pid); ?>'
					 title="<?php echo esc_attr($image->description)?>"
					 alt="<?php echo esc_attr($image->alttext)?>"
					 src="<?php echo esc_attr($storage->get_image_url($image, 'full', TRUE))?>"
					 width="<?php echo esc_attr($image_size['width'])?>"
					 height="<?php echo esc_attr($image_size['height'])?>"
				/>
				
			<?php
			
			$this->include_template('photocrati-nextgen_gallery_display#image/after', $template_params);
			
			?>
			
		<?php endfor ?>
			
		<?php
		
		$this->include_template('photocrati-nextgen_gallery_display#list/after');
		
		?>
	</div>

	<?php

	$this->include_template('photocrati-nextgen_gallery_display#container/before');

	?>
	<div
		class="ngg-galleryoverview ngg-slideshow"
		id="<?php echo_h($anchor)?>"
		data-placeholder="<?php echo nextgen_esc_url($placeholder)?>"
		style="max-width:<?php echo_h($gallery_width) ?>px; max-height:<?php echo_h($gallery_height) ?>px;">

		<div
			class="ngg-slideshow-loader"
			id="<?php echo_h($anchor)?>-loader"
			style="width:<?php echo_h($gallery_width) ?>px; height:<?php echo_h($gallery_height) ?>px;">
			<img src="<?php echo_h(NGGALLERY_URLPATH) ?>images/loader.gif" alt="" />
		</div>
	</div>
	<?php

	$this->include_template('photocrati-nextgen_gallery_display#container/after');

	?>
	<script type="text/javascript">
	jQuery('#<?php echo_h($anchor)?>-image-list').hide().removeClass('ngg-slideshow-nojs');
	jQuery(function($){
		jQuery('#<?php echo_h($anchor); ?>').nggShowSlideshow({
			id: '<?php echo_h($displayed_gallery_id); ?>',
			fx: '<?php echo_h($cycle_effect); ?>',
			width: <?php echo_h($gallery_width); ?>,
			height: <?php echo_h($gallery_height); ?>,
			domain: '<?php echo_h(trailingslashit(home_url())); ?>',
			timeout: <?php echo_h(intval($cycle_interval) * 1000); ?>
		});
	});
	</script>
<?php endif ?>
<?php $this->end_element(); ?>
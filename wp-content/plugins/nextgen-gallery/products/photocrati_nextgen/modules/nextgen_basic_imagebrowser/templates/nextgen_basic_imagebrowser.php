<?php $this->start_element('nextgen_gallery.gallery_container', 'container', $displayed_gallery); ?>
	<div class='ngg-imagebrowser' id='<?php echo $anchor; ?>'>

    <h3><?php echo esc_attr($image->alttext); ?></h3>

		<?php
		
		$template_params = array(
				'index' => 0,
				'class' => 'pic',
				'image' => $image,
			);
			
		$this->include_template('photocrati-nextgen_gallery_display#image/before', $template_params);
		
		?>
        <a href='<?php echo esc_attr($storage->get_image_url($image, 'full', TRUE)); ?>'
           title='<?php echo esc_attr($image->description); ?>'
           data-src="<?php echo esc_attr($storage->get_image_url($image)); ?>"
           data-thumbnail="<?php echo esc_attr($storage->get_image_url($image, 'thumb')); ?>"
           data-image-id="<?php echo esc_attr($image->{$image->id_field}); ?>"
           data-title="<?php echo esc_attr($image->alttext); ?>"
           data-description="<?php echo esc_attr(stripslashes($image->description)); ?>"
           <?php echo $effect_code ?>>
            <img title='<?php echo esc_attr($image->alttext); ?>'
                 alt='<?php echo esc_attr($image->alttext); ?>'
                 src='<?php echo esc_attr($storage->get_image_url($image, 'full', TRUE)); ?>'/>
        </a>
	  <?php

		$this->include_template('photocrati-nextgen_gallery_display#image/after', $template_params);

		?>

    <div class='ngg-imagebrowser-nav'>

        <div class='back'>
            <a class='ngg-browser-prev'
               id='ngg-prev-<?php echo $previous_pid; ?>'
               href='<?php echo $previous_image_link; ?>'>
                &#9668; <?php _e('Back', 'nggallery'); ?>
            </a>
        </div>

        <div class='next'>
            <a class='ngg-browser-next'
               id='ngg-next-<?php echo $next_pid; ?>'
               href='<?php echo $next_image_link; ?>'>
                <?php _e('Next', 'nggallery'); ?>
                &#9658;
            </a>
        </div>

        <div class='counter'>
            <?php _e('Picture', 'nggallery'); ?> <?php echo $number; ?> <?php _e('of', 'nggallery'); ?> <?php echo $total; ?>
        </div>

        <div class='ngg-imagebrowser-desc'>
            <p>
                <?php echo $image->description; ?>
            </p>
        </div>

    </div>

</div>
<?php $this->end_element(); ?>
<script type='text/javascript'>
	jQuery(function($){
		new NggPaginatedGallery(
			'<?php echo $displayed_gallery->id() ?>',
			$('#<?php echo $anchor ?>'),
			$('#<?php echo $anchor ?> .ngg-browser-prev, #<?php echo $anchor ?> .ngg-browser-next')
		)
	});
</script>
<?php

$this->start_element('nextgen_gallery.gallery_container', 'container', $displayed_gallery);

?>
<div
	class="ngg-galleryoverview"
	id="ngg-gallery-<?php echo_h($displayed_gallery_id)?>-<?php echo_h($current_page)?>">

    <?php if (!empty($slideshow_link)): ?>
	<div class="slideshowlink">
        <a href='<?php echo $slideshow_link ?>'><?php echo $slideshow_link_text ?></a>
		
	</div>
	<?php endif ?>

	<?php if ($show_piclens_link): ?>
	<!-- Piclense link -->
	<div class="piclenselink">
		<a class="piclenselink" href="<?php echo esc_attr($piclens_link) ?>">
			<?php echo_h($piclens_link_text); ?>
		</a>
	</div>
	<?php endif ?>
	<?php

	$this->start_element('nextgen_gallery.image_list_container', 'container', $images);

	?>
	<!-- Thumbnails -->
	<?php for ($i=0; $i<count($images); $i++):
       $image = $images[$i];
       $thumb_size = $storage->get_image_dimensions($image, $thumbnail_size_name);
       $style = isset($image->style) ? $image->style : null;

       if (isset($image->hidden) && $image->hidden) {
          $style = 'style="display: none;"';
       }
       else {
       		$style = null;
       }

			 $this->start_element('nextgen_gallery.image_panel', 'item', $image);

			?>
			<div id="<?php echo_h('ngg-image-' . $i) ?>" class="ngg-gallery-thumbnail-box" <?php if ($style) echo $style; ?>>
				<?php

				$this->start_element('nextgen_gallery.image', 'item', $image);

				?>
        <div class="ngg-gallery-thumbnail">
            <a href="<?php echo esc_attr($storage->get_image_url($image))?>"
               title="<?php echo esc_attr($image->description)?>"
               data-image-id='<?php echo esc_attr($image->pid); ?>'
               <?php echo $effect_code ?>>
                <img
                    title="<?php echo esc_attr($image->alttext)?>"
                    alt="<?php echo esc_attr($image->alttext)?>"
                    src="<?php echo esc_attr($storage->get_image_url($image, $thumbnail_size_name))?>"
                    width="<?php echo esc_attr($thumb_size['width'])?>"
                    height="<?php echo esc_attr($thumb_size['height'])?>"
                    style="max-width:none;"
                />
            </a>
        </div>
				<?php

				$this->end_element();

				?>
			</div> 
			<?php

			$this->end_element();

			?>

        <?php if ($number_of_columns > 0): ?>
            <?php if ((($i + 1) % $number_of_columns) == 0 ): ?>
                <br style="clear: both" />
            <?php endif; ?>
        <?php endif; ?>

	<?php endfor ?>
	<?php

	$this->end_element();

	?>

	<?php if ($pagination): ?>
	<!-- Pagination -->
	<?php echo $pagination ?>
	<?php else: ?>
	<div class="ngg-clear"></div>
	<?php endif ?>
</div>
<?php $this->end_element(); ?>

<?php $this->start_element('nextgen_gallery.gallery_container', 'container', $displayed_gallery); ?>
<div class="ngg-albumoverview">
    <?php foreach ($galleries as $gallery) { ?>
        <div class="ngg-album">
            <div class="ngg-albumtitle">
                <a href="<?php echo nextgen_esc_url($gallery->pagelink); ?>"><?php echo_safe_html($gallery->title); ?></a>
            </div>
            <div class="ngg-albumcontent">
                <div class="ngg-thumbnail">
                    <a class="gallery_link" href="<?php echo nextgen_esc_url($gallery->pagelink); ?>"><img class="Thumb" alt="<?php echo esc_attr($gallery->title); ?>" src="<?php echo nextgen_esc_url($gallery->previewurl); ?>"/></a>
                </div>
                <div class="ngg-description">
                    <p><?php echo_safe_html($gallery->galdesc); ?></p>
                    <?php if (isset($gallery->counter) && $gallery->counter > 0) { ?>
                        <p><strong><?php echo $gallery->counter; ?></strong>&nbsp;<?php _e('Photos', 'nggallery'); ?></p>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } ?>
</div>
<?php $this->end_element(); ?>

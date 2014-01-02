<?php $this->start_element('nextgen_gallery.gallery_container', 'container', $displayed_gallery); ?>
<div class="ngg-albumoverview">
    <?php foreach ($galleries as $gallery) { ?>
        <div class="ngg-album-compact">
            <div class="ngg-album-compactbox">
                <div class="ngg-album-link">
                    <a class="Link gallery_link" href="<?php echo nextgen_esc_url($gallery->pagelink); ?>">
                        <img class="Thumb"
                             alt="<?php echo esc_attr($gallery->title); ?>"
                             src="<?php echo nextgen_esc_url($gallery->previewurl); ?>"/>
                    </a>
                </div>
            </div>
            <?php if (!empty($image_gen_params)) {
                $max_width = 'style="max-width: ' . ($image_gen_params['width'] + 20) . 'px"';
            } else {
                $max_width = '';
            } ?>
            <h4>
                <a class="ngg-album-desc"
                   title="<?php echo esc_attr($gallery->title); ?>"
                   href="<?php echo nextgen_esc_url($gallery->pagelink); ?>"
                   <?php echo $max_width; ?>>
                    <?php echo_safe_html($gallery->title); ?>
                </a>
            </h4>
            <?php if (isset($gallery->counter) && $gallery->counter > 0) { ?>
                <p><strong><?php echo $gallery->counter; ?></strong>&nbsp;<?php _e('Photos', 'nggallery'); ?></p>
            <?php } ?>
        </div>
    <?php } ?>
    <br class="ngg-clear"/>
</div>
<?php $this->end_element(); ?>

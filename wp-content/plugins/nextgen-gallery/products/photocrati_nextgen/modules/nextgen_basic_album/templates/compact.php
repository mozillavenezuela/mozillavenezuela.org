<?php $this->start_element('nextgen_gallery.gallery_container', 'container', $displayed_gallery); ?>
<div class="ngg-albumoverview">
    <?php foreach ($galleries as $gallery) { ?>
        <div class="ngg-album-compact">
            <div class="ngg-album-compactbox">
                <div class="ngg-album-link">
                    <a class="Link" href="<?php echo $gallery->pagelink; ?>">
                        <img class="Thumb"
                             alt="<?php echo $gallery->title; ?>"
                             src="<?php echo $gallery->previewurl; ?>"/>
                    </a>
                </div>
            </div>
            <h4>
                <a class="ngg-album-desc"
                   title="<?php echo $gallery->title; ?>"
                   href="<?php echo $gallery->pagelink; ?>"
                    ><?php echo $gallery->title; ?></a>
            </h4>
            <?php if (isset($gallery->counter) && $gallery->counter > 0) { ?>
                <p><strong><?php echo $gallery->counter; ?></strong>&nbsp;<?php _e('Photos', 'nggallery'); ?></p>
            <?php } ?>
        </div>
    <?php } ?>
    <br class="ngg-clear"/>
</div>
<?php $this->end_element(); ?>

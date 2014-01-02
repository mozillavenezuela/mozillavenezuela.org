<table>
	<!-- Lightbox Library Name -->
	<tr>
		<td class="column1">
			<label for="lightbox_library"><?php echo_h($lightbox_library_label)?></label>
		</td>
		<td>
			<select name="lightbox_library_id" id="lightbox_library">
				<?php foreach ($libs as $lib) { ?>
                    <option value="<?php echo esc_attr($lib->$id_field)?>"
                            <?php selected($lib->name, $selected, TRUE)?>
                            data-library-name='<?php echo $lib->name; ?>'>
                        <?php if (isset($lib->title) && $lib->title) { ?>
                            <?php echo_h($lib->title) ?>
                        <?php } else { ?>
                            <?php echo_h($lib->name) ?>
                        <?php } ?>
                    </option>
				<?php } ?>
			</select>
		</td>
	</tr>

    <?php foreach ($sub_fields as $name => $form) { ?>
        <tbody class="lightbox_library_settings hidden" id="lightbox_library_<?php print $name; ?>">
            <?php echo $form; ?>
        </tbody>
    <?php } ?>

    <tr>
		<td colspan="2">
			<a href="#"
			   id="lightbox_library_advanced_toggle"
			   data-hidden_label="<?php echo esc_attr(_('(Show Advanced Settings)'))?>"
			   data-active_label="<?php echo esc_attr(_('(Hide Advanced Settings)'))?>"
               data-currently-hidden='true'>
                <?php echo_h(_("(Show Advanced Settings)"))?>
			</a>
		</td>
	</tr>

    <?php foreach ($adv_fields as $name => $form) { ?>
        <tbody class="lightbox_library_advanced_settings hidden" id="lightbox_library_<?php print $name; ?>_advanced">
            <?php echo $form; ?>
        </tbody>
    <?php } ?>
	<tr>
		<td>&nbsp;
		</td>
		<td>&nbsp;
		</td>
	</tr>
	<tr>
		<td class="column1">
			<label for="lightbox_global"><?php esc_html_e('What must the lightbox be applied to?', 'nggallery')?></label>
		</td>
		<td>
			<select name="thumbEffectContext" id="lightbox_global">
          <option value="nextgen_images" <?php selected('nextgen_images', $lightbox_global, TRUE)?>><?php esc_html_e('Only apply to NextGEN images', 'nggallery'); ?></option>
          <option value="nextgen_and_wp_images" <?php selected('nextgen_and_wp_images', $lightbox_global, TRUE)?>><?php esc_html_e('Only apply to NextGEN and WordPress images', 'nggallery'); ?></option>
          <option value="all_images" <?php selected('all_images', $lightbox_global, TRUE)?>><?php esc_html_e('Try to apply to all images', 'nggallery'); ?></option>
          <option value="all_images_direct" <?php selected('all_images_direct', $lightbox_global, TRUE)?>><?php esc_html_e('Try to apply to all images that link to image files', 'nggallery'); ?></option>
			</select>
		</td>
	</tr>
	</tbody>
</table>

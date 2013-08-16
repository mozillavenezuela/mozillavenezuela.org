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

	</tbody>
</table>
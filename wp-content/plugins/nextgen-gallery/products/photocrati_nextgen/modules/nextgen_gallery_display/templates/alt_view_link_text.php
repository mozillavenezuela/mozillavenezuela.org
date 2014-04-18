<tr id='tr_<?php print esc_attr("{$display_type_name}_alt_view_link_text"); ?>' class='<?php print !empty($hidden) ? 'hidden' : ''; ?>'>
    <td>
        <label for='<?php echo esc_attr($display_type_name); ?>_alt_view_link_text' class='tooltip'>
            <?php echo_h($alt_view_link_text_label); ?>
			<span>
				<?php echo_h($tooltip)?>
			</span>
        </label>
    </td>
    <td>
        <input type='text'
               id='<?php echo esc_attr($display_type_name); ?>_alt_view_link_text'
               name='<?php echo esc_attr($display_type_name); ?>[alternative_view_link_text]'
               class='alt_view_link_text'
               placeholder='<?php _e('link text'); ?>'
               value='<?php echo esc_attr($alternative_view_link_text); ?>'/>
    </td>
</tr>

<table>
	<tr>
		<td class="column1">
			<label for="mediarss_activated">
				<?php echo_h($mediarss_activated_label)?>
			</label>
		</td>
		<td>
			<label for="mediarss_activated">
				<?php echo_h($mediarss_activated_yes) ?>
			</label>
			<input
                id='mediarss_activated'
				type="radio"
				name="misc_settings[useMediaRSS]"
				value="1"
				<?php checked(TRUE, $mediarss_activated ? TRUE : FALSE)?>
			/>
			&nbsp;
			<label for="mediarss_activated_no">
				<?php echo_h($mediarss_activated_no) ?>
			</label>
			<input
                id='mediarss_activated_no'
				type="radio"
				name="misc_settings[useMediaRSS]"
				value="0"
				<?php checked(FALSE, $mediarss_activated ? TRUE : FALSE)?>
			/>
			<p class="description">
				<?php echo_h($mediarss_activated_help)?>
			</p>
		</td>
	</tr>
    <tr>
        <td class='column1'>
            <?php echo $cache_label; ?>
        </td>
        <td>
            <input type='submit'
                   name="action_proxy"
                   class="button delete button-secondary"
                   data-proxy-value="cache"
                   data-confirm="<?php echo $cache_confirmation; ?>"
                   value='<?php echo $cache_label; ?>'
                />
        </td>
    </tr>

    <?php print $slug_field; ?>

    <?php print $maximum_entity_count_field; ?>
</table>

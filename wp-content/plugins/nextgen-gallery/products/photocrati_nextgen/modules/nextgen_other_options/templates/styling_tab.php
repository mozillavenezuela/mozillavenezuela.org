<table class="full-width">
	<tr>
		<td class="column1">
			<label for="activated_stylesheet">
				<?php echo_h($select_stylesheet_label) ?>
			</label>
		</td>
		<td>
			<select id="activated_stylesheet" name="style_settings[CSSfile]">
			<?php foreach ($stylesheets as $value => $p): ?>
				<option
					value="<?php echo esc_attr($value)?>"
					description="<?php echo esc_attr($p['description'])?>"
					author="<?php echo esc_attr($p['author'])?>"
					version="<?php echo esc_attr($p['version'])?>"
					<?php selected($value, $activated_stylesheet)?>
				><?php echo_h($p['name'])?></option>
			<?php endforeach ?>
			</select>
            <p class="description">
				Place any custom stylesheets in <strong>wp-content/ngg_styles</strong><br/>
                All stylesheets must contain a <a href='#' onclick='javascript:alert("/*\nCSS Name: Example\nDescription: This is an example stylesheet\nAuthor: John Smith\nVersion: 1.0\n*/");'>file header</a>
            </p>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<a
				href="#"
				id="advanced_stylesheet_options"
				class="nextgen_advanced_toggle_link"
				rel="advanced_stylesheet_form"
				hidden_label="<?php echo esc_attr($hidden_label)?>"
				active_label="<?php echo esc_attr($active_label)?>">
				<?php echo_h($hidden_label) ?>
			</a>
		</td>
	</tr>
	<tr class="hidden" id="advanced_stylesheet_form">
		<td colspan="2">
			<label for="cssfile_contents" class="align-to-top">
				<?php echo_h($cssfile_contents_label)?>
			</label>
			<p
				class="description"
				writable_label="<?php echo esc_attr($writable_label)?>"
				readonly_label="<?php echo esc_attr($readonly_label)?>"
				id="writable_identicator">
			</p>
			<textarea id="cssfile_contents" name="cssfile_contents"></textarea>
		</td>
	</tr>
</table>
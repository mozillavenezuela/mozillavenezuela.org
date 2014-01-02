<div class="wrap" id='ngg_page_content' style='position: relative; visibility: hidden;'>
	<h2><?php echo_h($page_heading)?></h2>
	<?php if ($errors): ?>
	<?php foreach ($errors as $msg): ?>
	<?php echo $msg ?>
	<?php endforeach ?>
	<?php endif ?>
	<?php if ($success AND empty($errors)): ?>
	<div class='success updated'>
		<p><?php echo_h($success);?></p>
	</div>
	<?php endif ?>
	<form method="POST" action="<?php echo nextgen_esc_url($_SERVER['REQUEST_URI'])?>">
		<?php if (isset($form_header)): ?>
		<?php echo $form_header."\n"; ?>
		<?php endif ?>
		<input type="hidden" name="action"/>
		<div class="accordion" id="nextgen_admin_accordion">
		<?php foreach($tabs as $tab): ?>
			<?php echo $tab ?>
		<?php endforeach ?>
		</div>
        <?php if ($show_save_button): ?>
		<p>
			<input type="submit" name='action_proxy' value="Save" class="button-primary"/>
		</p>
        <?php endif ?>
	</form>
</div>

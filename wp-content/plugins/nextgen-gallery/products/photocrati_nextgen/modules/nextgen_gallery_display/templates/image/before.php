<?php
	if (!isset($id))
	{
		$id = 'ngg-image-' . $index;
	}
?>
<div id="<?php echo_h($id) ?>" class="<?php echo_h($class) ?>" <?php if (isset($image->style)) echo $image->style; ?>>

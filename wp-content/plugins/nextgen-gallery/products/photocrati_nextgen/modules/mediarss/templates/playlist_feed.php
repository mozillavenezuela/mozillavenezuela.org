<playlist version='1' xmlns='http://xspf.org/ns/0/'>
	<trackList>
		<?php foreach($images as $image): ?>
		<?php
			$image_url  = $storage->get_image_url($image, 'full', TRUE);
			$thumb_url  = $storage->get_thumb_url($image, TRUE);
			$thumb_size = $storage->get_thumb_dimensions($image);
			$width		= $thumb_size['width'];
			$height		= $thumb_size['height'];
			
			$image_title = $image->description;
			
			if ($image_title == null)
				$image_title = $image->alttext;

            if (strlen($image_title) >= 25)
                $image_title = substr_replace($image_title, '...', 15, -10);
            ?>
		<track>
			<title><![CDATA[<?php echo strip_tags($image_title); ?>]]></title>
			<location><![CDATA[<?php echo nextgen_esc_url($image_url)?>]]></location>
		</track>
		<?php endforeach ?>
	</trackList>
</playlist>

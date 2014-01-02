<?php echo('<?xml version="1.0" encoding="UTF-8"?>');?>
<rss version='2.0' xmlns:media='http://search.yahoo.com/mrss/'>
	<channel>
		<generator><![CDATA[<?php echo_h($generator)?>]]></generator>
		<title><?php echo_h($feed_title) ?></title>
		<description><?php echo_h($feed_description) ?></description>
		<link><![CDATA[<?php echo nextgen_esc_url($feed_link)?>]]></link>
		<?php foreach($images as $image): ?>
		<?php
			$image_url  = $storage->get_image_url($image, 'full', TRUE);
			$thumb_url  = $storage->get_thumb_url($image, TRUE);
			$thumb_size = $storage->get_thumb_dimensions($image);
			$width		= $thumb_size['width'];
			$height		= $thumb_size['height'];
		?>
		<item>
			<title><![CDATA[<?php echo_h($image->alttext)?>]]></title>
			<description><![CDATA[<?php echo_h($image->description)?>]]></description>
			<link><![CDATA[<?php echo nextgen_esc_url($image_url)?>]]></link>
			<guid>image-id:<?php echo_h($image->id_field)?></guid>
			<media:content url="<?php echo nextgen_esc_url($image_url)?>" medium="image" />
			<media:title><![CDATA[<?php echo_h($image->alttext)?>]]></media:title>
			<?php if (isset($description)): ?>
			<media:description><![CDDATA[<?php echo_h($image->description)?>]]></media:description>
			<?php endif ?>
			<media:thumbnail width="<?php echo esc_attr($width)?>" height="<?php echo esc_attr($height)?>" url="<?php echo nextgen_esc_url($thumb_url) ?>"/>
			<?php if (isset($tagnames)): ?>
			<media:keywords><![CDATA[<?php echo_h($tagnames)?>]]></media:keywords>
			<?php endif ?>
			<media:copyright><![CDATA[<?php echo_h($copyright)?>]]></media:copyright>
		</item>
		<?php endforeach ?>
	</channel>
</rss>
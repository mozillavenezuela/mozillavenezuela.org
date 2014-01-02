<?php

$paths = array(
    "../../..",
    "../../../..",
    "../../../../..",
    "../../../../../..",
    "../../../../../../..",
    "../../../../../../../..",
    "../../../../../../../../..",
    "../../../../../../../../../..",
    "../../../../../../../../../../..",
    "../../../../../../../../../../../..",
    "../../../../../../../../../../../../.."
);

/* include wordpress, make sure its available in one of the higher folders */
foreach( $paths as $path ) {
   if( @include_once( $path . '/wp-load.php' ) ) break;
}

function boom_dir_list( $dir ) {
	$list = scandir( $dir );
	unset( $list[0] );
	unset( $list[1] );
	$output = array();
	foreach( $list as $folder ) {
		$output[$folder] = ucwords( $folder );
	}
	return $output;
}

$nivo_bullets = boom_dir_list( 'bullets' );
$nivo_arrows = boom_dir_list( 'arrows' );
$nivo_transitions = array(
	'random'			=> 'random',
	'sliceDown'			=> 'sliceDown',
	'sliceDownLeft'		=> 'sliceDownLeft',
	'sliceUp'			=> 'sliceUp',
	'sliceUpLeft'		=> 'sliceUpLeft',
	'sliceUpDown'		=> 'sliceUpDown',
	'sliceUpDownLeft'	=> 'sliceUpDownLeft',
	'fold'				=> 'fold',
	'fade'				=> 'fade',
	'slideInRight'		=> 'slideInRight',
	'slideInLeft'		=> 'slideInLeft',
	'boxRandom'			=> 'boxRandom',
	'boxRain'			=> 'boxRain',
	'boxRainReverse'	=> 'boxRainReverse',
	'boxRainGrow'		=> 'boxRainGrow',
	'boxRainGrowReverse'=> 'boxRainGrowReverse'
);
$nivo_controls = array(
	'hide'		=> 'Hide',
	'rollover'	=> 'Show on Rollover',
	'show'		=> 'Show'
);

?><!DOCTYPE html>
<html>
<head>

</head>
<body>
	<div id="slider-options">
		<form action="options.php" method="post" id="slider-options-form">
			<div id="slider-general-settings">
				<h2>General Options</h2>
				<table class="form-table">
				<tbody>
					<tr>
						<th>Auto Insertion of Slider?</th>
						<td>
							<label>
								<input type="checkbox" name="slider[autoinsert]" value="on" <?php checked( 'on', boom_slider_get_option('autoinsert') ) ?> />
								Enable auto-insert the slider?
							</label>
							<div style="font-size: 11px; color: #999;">This only works if the theme already supports the WP 3.0 Custom Headers. Otherwise disable this and use the boom_header_image() template tag.</div>
						</td>
					</tr>
					<?php if( $chsfp ) : ?>
					<tr>
						<th>Slider width:</th>
						<td><input type="text" name="slider[width]" value="<?php echo boom_slider_get_option('width') ?>" /></td>
					</tr>
					<tr>
						<th>Slider height:</th>
						<td><input type="text" name="slider[height]" value="<?php echo boom_slider_get_option('height') ?>" /></td>
					</tr>
					<?php endif; ?>
				</tbody>
				</table>
			</div>
			<div id="nivo-settings">
				<h2>Nivo Slider Options</h2>
				<table class="form-table">
				<tbody>
					<tr>
						<th>Transition:</th>
						<td>
				<select name="slider[transition]">
					<?php foreach( $nivo_transitions as $key => $value ) {
						echo "<option value='{$key}'";
						selected( $key, boom_slider_get_option('transition') );
						echo ">{$value}</option>";
					}
					?>
				</select>
						</td>
					</tr>
					<tr>
						<th>Slices:</th>
						<td><input type="text" name="slider[slices]" value="<?php echo boom_slider_get_option('slices') ?>" /></td>
					</tr>
					<tr>
						<th>Animation Speed:</th>
						<td><input type="text" name="slider[speed]" value="<?php echo boom_slider_get_option('speed') ?>" /></td>
					</tr>
					<tr>
						<th>Pause on Hover:</th>
						<td><input type="checkbox" name="slider[hoverpause]" value="on" <?php checked( boom_slider_get_option('hoverpause'), 'on' ) ?> /></td>
					</tr>
					<tr>
						<th>Pause Time:</th>
						<td><input type="text" name="slider[pause]" value="<?php echo boom_slider_get_option('pause') ?>" /></td>
					</tr>
					<tr>
						<th>Next & Prev Buttons:</th>
						<td>
				<select name="slider[direction]">
					<?php foreach( $nivo_controls as $key => $value ) {
						echo "<option value='{$key}'";
						selected( $key, boom_slider_get_option('direction') );
						echo ">{$value}</option>";
					}
					?>
				</select>
						</td>
					</tr>
					<tr>
						<th>Next & Prev Buttons Style:</th>
						<td>
				<select name="slider[arrows]">
					<?php foreach( $nivo_arrows as $key => $value ) {
						echo "<option value='{$key}'";
						selected( $key, boom_slider_get_option('arrows') );
						echo ">{$value}</option>";
					}
					?>
				</select>
						</td>
					</tr>
					<tr>
						<th>Navigation Controls:</th>
						<td><input type="checkbox" name="slider[navcontrols]" value="on" <?php checked( boom_slider_get_option('navcontrols'), 'on' ) ?> /></td>
					</tr>
					<tr>
						<th>Navigation Controls Style:</th>
						<td>
				<select name="slider[bullets]">
					<?php foreach( $nivo_bullets as $key => $value ) {
						echo "<option value='{$key}'";
						selected( $key, boom_slider_get_option('bullets') );
						echo ">{$value}</option>";
					}
					?>
				</select>
						</td>
					</tr>
					<tr>
						<th>Navigation Controls bottom position:</th>
						<td><input type="text" name="slider[bottom]" value="<?php echo boom_slider_get_option('bottom') ?>" /></td>
					</tr>
					<tr>
						<th>Keyboard Navigation:</th>
						<td><input type="checkbox" name="slider[keyboard]" value="on" <?php checked( boom_slider_get_option('keyboard'), 'on' ) ?> /></td>
					</tr>
				</tbody>
				</table>
			</div>
			<p class="submit">
				<input type="button" class="button-secondary" value="Cencel" id="slider-options-cancel" />
				<input type="button" class="button-primary" value="Save Settings" id="slider-options-insert" />
			</p>
		</form>
	</div>
<script>
jQuery(function($){
	$('#slider-options-insert').click(function(){
		$.ajax({
			url: window.location.href,
			data: $('#slider-options-form').serialize(),
			type: 'POST',
			success: function( data ) {
				sliderOptions_hideDialog();
			}
		});
	});
	$('#slider-options-cancel').click(function(){
		sliderOptions_hideDialog();
	});
	function sliderOptions_hideDialog() {
		$('#slider-options').remove();
		tb_remove();
	}
});
</script>
</body>
</html>
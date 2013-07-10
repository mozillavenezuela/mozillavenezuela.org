<?php

	/**
	 * This is a modified version of Nivo Slider Default Theme file.
	 */

	header("Content-type: text/css");
	$paths = array(
		"../../..",
		"../../../..",
		"../../../../..",
		"../../../../../..",
	);

	/* include wordpress, make sure its available in one of the higher folders */
	foreach( $paths as $path ) {
	   if( @include_once( $path . '/wp-load.php' ) ) break;
	}

	include 'nivo-slider/nivo-slider.css';

?>
.slider-wrapper {
	position: relative;
}
.theme-default .nivoSlider {
	position: relative;
	overflow: hidden !important;
	background: #fff url(images/loading.gif) no-repeat 50% 50%;
    -webkit-box-shadow: 0px 1px 5px 0px #4a4a4a;
    -moz-box-shadow: 0px 1px 5px 0px #4a4a4a;
    box-shadow: 0px 1px 5px 0px #4a4a4a;
}
.theme-default .nivoSlider img {
	position: absolute;
	top: 0px;
	left: 0px;
	display: none;
}
.theme-default .nivoSlider a {
	border: 0;
	display: block;
}

.theme-default .nivo-controlNav {
	position: absolute;
	left: 50%;
	z-index: 999;
	bottom: <?php echo boom_slider_get_option( 'bottom' ) ?>px;
	margin-left: -<?php echo boom_count_header_images() ?>0px; /* Tweak this to center bullets */
}
.theme-default .nivo-controlNav a {
	display: block;
	text-indent: -9999px;
	border: 0;
	float: left;
}

.theme-default .nivo-directionNav a {
	display: block;
	text-indent: -9999px;
	border: 0;
}

<?php include( 'bullets/' . boom_slider_get_option( 'bullets' ) . '/style.css' ); ?>
<?php include( 'arrows/' . boom_slider_get_option( 'arrows' ) . '/style.css' ); ?>
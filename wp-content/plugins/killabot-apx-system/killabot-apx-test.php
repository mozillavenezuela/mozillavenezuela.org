<?php
$url = $_GET['uri'];
$ua  = 'Killabot v1.0 - WordPress Internal Browser';
if(!in_array('curl', get_loaded_extensions())):
	require_once('libcurl/libcurlemu.inc.php');
endif;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_USERAGENT, $ua);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,"log=killabot&pwd=password");
$result=curl_exec ($ch);
curl_close ($ch);
// insert testing url into xhtml
$beforePos = strpos($result,"</body>");
$before    = substr($result,0,$beforePos);
print $before;
print '<div style="background:#efefef;padding:2px;border:1px solid #cdcdcd;"><span style="font-size:11px;font-weight:bold;">&nbsp;<img src="images/icon.gif" alt="Killabot Shield" style="vertical-align:middle;"/>&nbsp;TEST URL => </span><span style="font-size:11px;color:#353535;"> '.$url.'</span></div>';
$after  = substr($result,$beforePos);
print $after;?>
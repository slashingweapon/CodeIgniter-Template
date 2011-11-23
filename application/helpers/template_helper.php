<?php

@include_once('krumo/class.krumo.php');

function url_host($url) {
	$retval = parse_url($url, PHP_URL_HOST);
	if (!$retval)
		$retval = $url;
	return $retval;
}

function url_ensure($url) {
	$scheme = parse_url($url, PHP_URL_SCHEME);
	if (!$scheme)
		$url = "http://$url";
	return $url;
}

function debug_var(&$var) {
	if (function_exists('krumo'))
		krumo($var);
	else
		return "<div>needs Krumo</div>";
}

function percentage($var) {
	$var *= 100;
	
	return sprintf("%2.2f%%", $var);
}

function currency($var) {
	$var = preg_replace('/[^0-9\.]/', '', $var);
	return sprintf("$%.2f", $var);
}

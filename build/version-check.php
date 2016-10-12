<?php
$version_checks = array(
	"$plugin_slug.php" => array(
		'@Version:\s+(.*)\n@' => 'header',
	),
	"$plugin_slug.php" => array(
		'@Version:\s+(.*)\n@' => 'header',
	),
	'readme.txt' => array(
		'@Stable tag:\s+(.*)\n@' => 'readme',
	),
	'HelpScout_Desk.php' => array(
		"@const HSD_VERSION = '(.*?)';@" => 'constant',
	),
);

<?php

// function ld_weave_registration_path($path = '')
// {
// 	global $site, $application;
// 	$basePath = $site->getPath() . '/' .  $application->getPath();
// 	$path = $_SERVER["REQUEST_URI"];
// 	$path = str_replace("$basePath/user/1.0/", "/", $path);
// 	$path = str_replace("$basePath/user/1/", "/", $path);
// 	return $path;
// }

function ld_weave_sync_path($path = '/')
{
	global $site, $application;
	if ($path != '/') {
		return $path;
	}
	$basePath = $site->getPath() . '/' .  $application->getPath();
	if (isset($_SERVER["REDIRECT_URL"])) {
		$path = $_SERVER["REDIRECT_URL"];
	} else {
		$path = $_SERVER["REQUEST_URI"];
		// query string parameters should be removed
		if (!empty($_SERVER["QUERY_STRING"])) {
		    $path = str_replace("?" . $_SERVER["QUERY_STRING"], "", $path);
		}
	}
	$path = str_replace("$basePath/1.0/", "/", $path);
	return $path;
}

// function ld_weave_prepare_sql($sql = '')
// {
// 	global $dbPrefix;
// 	$sql = str_replace("into users", "into {$dbPrefix}users", $sql);
// 	$sql = str_replace("update users", "update {$dbPrefix}users", $sql);
// 	$sql = str_replace("from users", "from {$dbPrefix}users", $sql);
// 	return $sql;
// }

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

function ld_weave_count($collection = null)
{
	$collections = array(
		'clients' => 1, 'crypto' => 2, 'forms' => 3, 'history' => 4, 'keys' => 5,
		'meta' => 6, 'bookmarks' => 7, 'prefs' => 8, 'tabs' => 9, 'passwords' => 10
	);
	if (empty($collection) || empty($collections[$collection])) {
		return 0;
	}
	$collectionId = $collections[$collection];
	
	global $application;
	$db = $application->getDbConnection();
	$dbPrefix = $application->getDbPrefix();
	
	$user = Ld_Auth::getUser();
	$userId = $user['id'];

	$result = $db->fetchCol("SELECT count(id) FROM {$dbPrefix}wbo WHERE username = '$userId' AND collection = '$collectionId'");
	return $result[0];
}

// function ld_weave_prepare_sql($sql = '')
// {
// 	global $dbPrefix;
// 	$sql = str_replace("into users", "into {$dbPrefix}users", $sql);
// 	$sql = str_replace("update users", "update {$dbPrefix}users", $sql);
// 	$sql = str_replace("from users", "from {$dbPrefix}users", $sql);
// 	return $sql;
// }

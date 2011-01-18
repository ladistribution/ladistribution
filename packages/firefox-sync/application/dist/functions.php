<?php

function ld_weave_sync_path($path = '/')
{
	global $site, $application;
	$path = $application->getCurrentPath();
	$path = str_replace("/1.0/", "/", $path);
	$path = str_replace("/1.1/", "/", $path);
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

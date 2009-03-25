<?php

if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'action.php');

class action_plugin_ld extends DokuWiki_Action_Plugin {

	/**
	 * Return some info
	 */
	function getInfo()
	{
		return array(
			'author' => 'h6e.net',
			'email'  => 'contact@h6e.net',
			'date'   => '2008-02-17',
			'name'   => 'LaDistribution packaging',
			'desc'   => 'support for LaDistribution prepend/append file based extension mechanism',
			'url'    => 'http://h6e.net/dokuwiki/plugins/ld',
		);
	}

	/**
	 * Register the eventhandlers
	 */
	function register(&$controller)
	{
		$controller->register_hook('DOKUWIKI_STARTED',
			'BEFORE',
			$this,
			'prepend',
			array());
		$controller->register_hook('DOKUWIKI_DONE',
			'BEFORE',
			$this,
			'append',
			array());
	}

	function prepend(&$event, $param)
	{
		if (file_exists(DOKU_INC . 'dist/prepend.php')) {
			include DOKU_INC . 'dist/prepend.php';
		}
	}

	function append(&$event, $param)
	{
		if (file_exists(DOKU_INC . 'dist/append.php')) {
			include DOKU_INC . 'dist/append.php';
		}
	}

}

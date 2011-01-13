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
			'date'   => '2010-10-13',
			'name'   => 'La Distribution package',
			'desc'   => 'support for La Distribution prepend/append file based extension mechanism',
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
		$controller->register_hook('TPL_METAHEADER_OUTPUT',
			'BEFORE',
			$this,
			'tpl_metaheader_output',
			array());
		$controller->register_hook('TPL_CONTENT_DISPLAY',
			'AFTER',
			$this,
			'template',
			array());
	}

	function prepend(&$event, $param)
	{
		global $conf;
		if (file_exists(DOKU_INC . 'dist/prepend.php')) {
			include DOKU_INC . 'dist/prepend.php';
		}
	}

	function append(&$event, $param)
	{
		global $conf;
		if (file_exists(DOKU_INC . 'dist/append.php')) {
			include DOKU_INC . 'dist/append.php';
		}
	}

	function tpl_metaheader_output(&$event, $param)
	{
		// Scripts
		$event->data["script"][] = array('type' => 'text/javascript',
			'src' => Ld_Ui::getJsUrl('/jquery/jquery.js', 'js-jquery'));
		// Stylesheets
		$event->data['link'][] = array('rel' => 'stylesheet', 'type' => 'text/css',
			'href' => Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'css-ld-ui'));
		if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
			$event->data['link'][] = array('rel' => 'stylesheet', 'type' => 'text/css',
				'href' => Ld_Ui::getApplicationStyleUrl());
		}
	}

	function template(&$event, $param)
	{
		global $conf;
		if (isset($conf['superbar']) && $conf['superbar'] == 'never') {
			return;
		}
		if (isset($conf['superbar']) && $conf['superbar'] == 'connected' && empty($_SERVER['REMOTE_USER'])) {
			return;
		}
		Ld_Ui::superBar();
	}

}

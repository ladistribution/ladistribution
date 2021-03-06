<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class LdPlugin extends Plugin
{

	function onInitializePlugin()
	{
	}

	function onEndShowHeadElements($action)
	{
		// Scripts
		$action->element('script', array('type' => 'text/javascript',
			'src' => Ld_Ui::getJsUrl('/jquery/jquery.js', 'js-jquery')));
		$action->element('script', array('type' => 'text/javascript',
			'src' => Ld_Ui::getJsUrl('/ld/ld.js', 'lib-admin')));
		// Stylesheets
		if (common_config('site', 'theme') == 'ld') {
			$action->element('link', array('rel' => 'stylesheet', 'type' => 'text/css',
				'href' => Ld_Ui::getCssUrl('/h6e-minimal/h6e-minimal.css', 'h6e-minimal')));
			$action->element('link', array('rel' => 'stylesheet', 'type' => 'text/css',
				'href' => Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'ld-ui')));
			if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
				$action->element('link', array('rel' => 'stylesheet', 'type' => 'text/css',
					'href' => Ld_Ui::getApplicationStyleUrl()));
				$action->raw(
					'  <style type="text/css">' . "\n  " . $this->_getCssRules() . "\n" . '  </style>' . "\n"
				);
			}
		} else {
			$action->element('link', array('rel' => 'stylesheet', 'type' => 'text/css',
				'href' => Ld_Ui::getCssUrl('/ld-ui/ld-bars.css', 'ld-ui')));
			if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
				$action->element('link', array('rel' => 'stylesheet', 'type' => 'text/css',
					'href' => Ld_Ui::getApplicationStyleUrl()));
			}
			$action->raw(
				'<style type="text/css">'.
				'#footer { margin-bottom:25px }'.
				'</style>'
			);
		}
	}

	protected function _getCssRules()
	{
		$colors = Ld_Ui::getApplicationColors();
		return 'body { background-color:#' . $colors['ld-colors-background'] .'; }
  label[for="notice_data-text"] { color:#' . $colors['ld-colors-text'] .'; }
  #footer { border-color:#' . $colors['ld-colors-border'] .'; }
  #header address a { color:#' . $colors['ld-colors-title'] .'; }
  #site_nav_local_views ul.nav li a, #site_nav_local_views ul.nav li a:hover, #site_nav_local_views ul.nav li.current a,
  #wrap #core #content, #wrap #core #aside_primary, #anon_notice { border-color:#' . $colors['ld-colors-border-3'] .'; background-color:#' . $colors['ld-colors-background-3'] .'; }
  #site_nav_local_views ul.nav li a, #site_nav_local_views ul.nav li a:hover, #site_nav_local_views ul.nav li.current a,
  #wrap #core #content, #wrap #core #content a, #wrap #core #aside_primary, #wrap #core #aside_primary a, #anon_notice, #anon_notice a { color:#' . $colors['ld-colors-text-3'] .'; }
  #site_nav_local_views ul.nav li.current a { border-bottom-color:#' . $colors['ld-colors-background-3'] . '; }
  #content h1, #aside_primary h2 { color:#' . $colors['ld-colors-title-3'] .'; }';
	}

	function onStartShowHeader($action)
	{
		if (common_config('site', 'theme') == 'ld') {
			$action->elementStart('div', array('class' => 'h6e-layout'));
			$conf = Zend_Registry::get('application')->getConfiguration();
			$topbar = isset($conf['topbar']) ? $conf['topbar'] : 'everyone';
			if ($topbar == 'everyone' || ($topbar == 'connected' && common_current_user())) {
				$action->raw(Ld_Ui::getTopBar(array('logoutUrl' => common_local_url('logout'))));
			}
			$action->elementStart('div', array('class' => 'h6e-main-content'));
		}
	}

	function onEndShowFooter($action)
	{
		// $conf = Zend_Registry::get('application')->getConfiguration();
		// $superbar = isset($conf['superbar']) ? $conf['superbar'] : 'everyone';
		// if ($superbar == 'everyone' || ($superbar == 'connected' && common_current_user())) {
		// 	$action->raw( Ld_Ui::getSuperBar() );
		// }
		if (common_config('site', 'theme') == 'ld') {
			$action->elementEnd('div');
			$action->elementEnd('div');
		}
	}

	function onStartAccountSettingsDesignMenuItem($widget, $menu)
	{
		return false;
	}

	function onStartAccountSettingsAvatarMenuItem($widget, $menu)
	{
		return false;
	}

	function onStartAccountSettingsOtherMenuItem($widget)
	{
		return false;
	}

	function onStartPublicFeaturedUsersSection($action)
	{
	    return false;
	}

	function onStartShowShortcutIcon($action)
	{
	    return false;
	}

	function onStartPrimaryNav($action)
	{
		$action->menuItem(common_local_url(''), _m('MENU', 'Public'));
	}

	function onEndPrimaryNav($action)
	{
		if (Ld_Ui::isAdmin()) {
			$action->menuItem(Ld_Ui::getApplicationSettingsUrl(), _m('MENU', Ld_Translate::translate('Settings')));
		}
	}

	function onStartSecondaryNav($action)
	{
		// see lib/action.php#showSecondaryNav for original source
		return false;
	}

	function onEndShowScripts($action)
	{
		$action->raw(
			'<script type="text/javascript">'.
			'var aside = $("#aside_primary"); if (aside.children("div").length == 0) { aside.hide() };'.
			'</script>'
		);
	}

	function onPluginVersion(&$versions)
	{
		$versions[] = array(
			'name' => 'La Distribution Package',
			'version' => '0.5.3',
			'author' => 'h6e.net',
			'homepage' => 'http://h6e.net/',
			'rawdescription' => _m('Integrate a Status.net instance with La Distribution')
		);
		return true;
	}

}

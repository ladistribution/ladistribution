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
		if (common_config('site', 'theme') == 'ld') {
			$action->element('link', array('rel' => 'stylesheet', 'type' => 'text/css',
				'href' => Ld_Ui::getCssUrl('/h6e-minimal/h6e-minimal.css', 'h6e-minimal')));
			$action->element('link', array('rel' => 'stylesheet', 'type' => 'text/css',
				'href' => Ld_Ui::getCssUrl('/ld-ui/ld-ui.css', 'ld-ui')));
		} else {
			$action->element('link', array('rel' => 'stylesheet', 'type' => 'text/css',
				'href' => Ld_Ui::getCssUrl('/ld-ui/ld-bars.css', 'ld-ui')));
			$action->raw(
				'<style type="text/css">'.
				'#footer { margin-bottom:25px }'.
				'</style>'
			);
		}
	}

	function onStartShowHeader($action)
	{
		if (common_config('site', 'theme') == 'ld') {
			$action->elementStart('div', array('class' => 'h6e-layout'));
			$action->raw(Ld_Ui::getTopBar(array('logoutUrl' => common_local_url('logout'))));
			$action->elementStart('div', array('class' => 'h6e-main-content'));
		}
	}

	function onEndShowFooter($action)
	{
		$conf = Zend_Registry::get('application')->getConfiguration();
		$superbar = isset($conf['superbar']) ? $conf['superbar'] : 'everyone';
		if ($superbar == 'everyone' || ($superbar == 'connected' && common_current_user())) {
			$action->raw( Ld_Ui::getSuperBar() );
		}
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

	function onStartSecondaryNav($action)
	{
		// see lib/action.php#showSecondaryNav for original source
		$action->menuItem(common_local_url('doc', array('title' => 'about')), _('About'));
		$action->menuItem(common_local_url('doc', array('title' => 'help')), _('Help'));
		$action->menuItem(common_local_url('doc', array('title' => 'faq')), _('FAQ'));
		// $bb = common_config('site', 'broughtby');
		// if (!empty($bb)) {
		// 	$action->menuItem(common_local_url('doc', array('title' => 'tos')), _('TOS'));
		// }
		// $action->menuItem(common_local_url('doc', array('title' => 'privacy')), _('Privacy'));
		// $action->menuItem(common_local_url('doc', array('title' => 'source')), _('Source'));
		$action->menuItem(common_local_url('version'), _('Version'));
		// $action->menuItem(common_local_url('doc', array('title' => 'contact')), _('Contact'));
		// $action->menuItem(common_local_url('doc', array('title' => 'badge')), _('Badge'));
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

	/*
	function prefix(&$key)
	{
		if (empty($this->prefix)) {
			$application = Zend_Registry::get("application");
			$this->prefix = $application->getDbPrefix();
			$this->prefix = substr($this->prefix, 0, -1);
		}
		$key = str_replace('statusnet:', $this->prefix . ':', $key);
	}

	function onStartCacheGet(&$key, &$value)
	{
		$this->prefix($key);
		return true;
	}

	function onStartCacheSet(&$key, &$value, &$flag, &$expiry, &$success)
	{
		$this->prefix($key);
		return true;
	}

	function onStartCacheIncrement(&$key, &$step, &$value)
	{
		$this->prefix($key);
		return true;
	}

	function onStartCacheDelete(&$key, &$success)
	{
		$this->prefix($key);
		return true;
	}
	*/

	function onPluginVersion(&$versions)
	{
		$versions[] = array(
			'name' => 'La Distribution Package',
			'version' => '0.4.1',
			'author' => 'h6e.net',
			'homepage' => 'http://h6e.net/',
			'rawdescription' => _m('Integrate a Status.net instance with La Distribution')
		);
		return true;
	}

}

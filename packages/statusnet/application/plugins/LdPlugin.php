<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class LdPlugin extends Plugin
{

	function onInitializePlugin()
	{
	}

	function getCssUrl($file, $package)
	{
		$site = Zend_Registry::get('site');
		$infos = $site->getLibraryInfos("css-$package");
		$url = $site->getUrl('css') . $file . '?v=' . $infos['version'];
		return $url;
	}

	function onEndShowHeadElements($action)
	{
		$action->element('link', array('rel' => 'stylesheet', 'type' => 'text/css',
			'href' => $this->getCssUrl('/h6e-minimal/h6e-minimal.css', 'h6e-minimal')));
		$action->element('link', array('rel' => 'stylesheet', 'type' => 'text/css',
			'href' => $this->getCssUrl('/ld-ui/ld-ui.css', 'ld-ui')));
	}

	function onStartShowHeader($action)
	{
		$action->elementStart('div', array('class' => 'h6e-layout'));
		$action->raw(Ld_Ui::get_top_bar(array('logoutUrl' => common_local_url('logout'))));
		$action->elementStart('div', array('class' => 'h6e-main-content'));
	}

	function onEndShowFooter($action)
	{
		$action->raw(Ld_Ui::get_super_bar());
		$action->elementEnd('div');
		$action->elementEnd('div');
	}

	function onStartAccountSettingsDesignMenuItem($widget, $menu)
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

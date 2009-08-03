<?php

class Ld extends Plugin
{

	function info()
	{
		return array (
			'name' => 'Ld',
			'url' => 'http://h6e.net/habari/plugins/ld',
			'author' => 'h6e',
			'authorurl' => 'http://h6e.net/',
			'version' => '0.2.28.1',
			'description' => 'Authentication and UI for La Distribution',
			'license' => 'Apache License 2.0',
		);
	}

	public function action_admin_header()
	{
		$this->header();
	}

	public function action_template_header()
	{
		$this->header();
	}

	public function header()
	{
		$css_url = Zend_Registry::get('site')->getUrl('css');
		?>
		<link rel="stylesheet" type="text/css" href="<?php echo $css_url ?>/ld-ui/ld-bars.css"/>
		<style type="text/css">body { padding-bottom:50px; }</style>
		<?php
	}

	public function action_template_footer()
	{
		$superbar = Options::get('superbar');
		if ($superbar == 'never') {
			return;
		}
		$auth = Zend_Auth::getInstance();
		if ($superbar == 'connected' && !$auth->hasIdentity()) {
			return;
		}
		$this->footer();
	}

	public function action_admin_footer()
	{
		$superbar = Options::get('superbar');
		if ($superbar == 'never') {
			return;
		}
		$this->footer();
	}

	public function footer()
	{
		Ld_Ui::super_bar(array('jquery' => false));
	}

	public function action_init()
	{
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$username = $auth->getIdentity();
			$ld_user = Zend_Registry::get('site')->getUser($username);
			if (isset($ld_user)) {
				$habari_user = $this->get_habari_user($ld_user);
				if (!empty($habari_user)) {
					$_SESSION['user_id'] = $habari_user->id;
					$_SESSION['ld_user_id'] = true;
				}
			}
		} else {
			if (isset($_SESSION['ld_user_id'])) {
				unset($_SESSION['ld_user_id']);
				unset($_SESSION['user_id']);
			}
		}
	}

	public function filter_user_authenticate($user, $who, $pw)
	{
		$ld_user = Zend_Registry::get('site')->getUser($who);

		if (isset($ld_user)) {

			$habari_user = $this->get_habari_user($ld_user);

			$result = Ld_Auth::authenticate($who, $pw);
			if ($result->isValid()) {
				return $habari_user;
			}

			return $user;

		}

		return $user;
	}

	protected function get_habari_user($ld_user)
	{
		$habari_user = User::get_by_name($ld_user['username']);
		if (empty($habari_user)) {
			$habari_user = User::create(array(
				'username' => $ld_user['username'],
				'email' =>  $ld_user['email']
			));
		}
		return $habari_user;
	}

	public function action_user_logout()
	{
		Ld_Auth::logout();
	}

}

?>

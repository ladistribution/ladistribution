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
			'version' => '1.0',
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
		$this->footer();
	}

	public function action_admin_footer()
	{
		$this->footer();
	}

	public function footer()
	{
		Ld_Ui::super_bar();
	}

	public function filter_user_authenticate($user, $who, $pw)
	{
		$ld_user = Zend_Registry::get('site')->getUser($who);

		if (isset($ld_user)) {

			$habari_user = User::get_by_name( $who );
			if (empty($habari_user)) {
				$habari_user = User::create(array(
					'username' => $ld_user['username'],
					'email' =>  $ld_user['email']
				));
			}

			$auth = Zend_Auth::getInstance();
			$adapter = new Ld_Auth_Adapter_File();
			$adapter->setCredentials($who, $pw);
			$result = $auth->authenticate($adapter);
			if ($result->isValid()) {
				return $habari_user;
			}

			return $user;

		}

		return $user;
	}

	public function action_user_logout()
	{
		$auth = Zend_Auth::getInstance();
		if ($auth->hasIdentity()) {
			$auth->clearIdentity();
		}
	}

}

?>

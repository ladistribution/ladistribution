<?php

if (!defined('STATUSNET') && !defined('LACONICA')) {
    exit(1);
}

class LdAuthenticationPlugin extends AuthenticationPlugin
{

	public $authoritative = true;

	public $autoregistration = true;

	public $password_changeable = false;

	public $provider_name = 'ld';

	function checkPassword($username, $password)
	{
		$result = Ld_Auth::authenticate($username, $password);
		if ($result->isValid()) {
		    return true;
		}
		return false;
    }

	function autoRegister($username, $nickname = null)
	{
		if (is_null($nickname)) {
			$nickname = $username;
		}
		$user = Zend_Registry::get('site')->getUser($username);
		$registration_data = array();
		$registration_data['nickname'] = $nickname;
		if (isset($user['fullname'])) {
			$registration_data['fullname'] = $user['fullname'];
		}
		return User::register($registration_data);
	}

	function onInitializePlugin()
	{
		parent::onInitializePlugin();
		if (Ld_Auth::isAuthenticated()) {
			if ( common_current_user() && common_is_real_login() ) {
				return;
			}
			$nickname = Ld_Auth::getUsername();
			$user = new User_username();
			$user->username = $nickname;
			$user->provider_name = $this->provider_name;
			if (!$user->find()) {
				$user = $this->autoRegister($user->username);
				if ($user) {
					User_username::register($user, $nickname, $this->provider_name);
				}
			}
			$this->sync_profile($nickname);
			if (class_exists('Ld_Plugin')) {
				Ld_Plugin::doAction('Statusnet:login', $nickname);
			}
			common_set_user($nickname);
			common_real_login(true);
		} else {
			if ( common_current_user() ) {
				common_set_user(null);
				common_real_login(false); // not logged in
				common_forgetme(); // don't log back in!
			}
		}
	}
	
	public function sync_profile($username)
	{
		$ld_user = Ld_Auth::getUser($username);
		$sn_user = User::staticGet('nickname', $username);
		$sn_profile = $sn_user->getProfile();
		// update avatars: 24 / 48 / 96
		foreach (array(AVATAR_PROFILE_SIZE, AVATAR_STREAM_SIZE, AVATAR_MINI_SIZE) as $size) {
			$avatar_url = Ld_Ui::getAvatarUrl($ld_user, $size);
			// avoid unique urls
			$default_avatar_url = Ld_Ui::getDefaultAvatarUrl($size);
			if ($avatar_url == $default_avatar_url) {
				$avatar_url .= '#ld-' . $username . '-' . $size;
			}
			if ($avatar = $sn_profile->getAvatar($size)) {
				if ($avatar_url == $avatar->url) {
					continue;
				}
				$avatar->delete();
			}
			$avatar = new Avatar();
			$avatar->created = DB_DataObject_Cast::dateTime();
			$avatar->width = $size;
			$avatar->height = $size;
			$avatar->profile_id = $sn_user->id;
			$avatar->mediatype = 'img';
			$avatar->original = 0;
			$avatar->url = $avatar_url;
			$avatar->insert();
			// handle memcache
			$memcache = common_memcache();
			if ($memcache) {
				$kv = array('profile_id' => $sn_profile->id, 'width' => $size,  'height' => $size);
				ksort($kv);
				$memcache->delete($sn_profile->multicacheKey('Avatar', $kv));
			}
		}
		// update email
		$sn_user->query('BEGIN');
		$orig_user = clone($sn_user);
		$sn_user->email = $ld_user['email'];
		$result = $sn_user->updateKeys($orig_user);
		$sn_user->emailChanged();
		$sn_user->query('COMMIT');
		// sync roles
		$role = Zend_Registry::get('application')->getUserRole($username);
		switch ($role) {
			case 'moderator':
				// empty memcache (not understood yet why keys are not up to date as expected)
				// $memcache = common_memcache();
				// if ($memcache) {
				// 	$kv = array('profile_id' => $sn_profile->id, 'role' => Profile_role::MODERATOR);
				// 	ksort($kv);
				// 	$memcache->delete(Memcached_DataObject::multicacheKey('Profile_role', $kv));
				// }
				if (!$sn_user->hasRole(Profile_role::MODERATOR)) {
					$sn_user->grantRole(Profile_role::MODERATOR);
				}
				if ($sn_user->hasRole(Profile_role::ADMINISTRATOR)) {
					$sn_user->revokeRole(Profile_role::ADMINISTRATOR);
				}
				break;
			case 'user':
				if ($sn_user->hasRole(Profile_role::MODERATOR)) {
					$sn_user->revokeRole(Profile_role::MODERATOR);
				}
				if ($sn_user->hasRole(Profile_role::ADMINISTRATOR)) {
					$sn_user->revokeRole(Profile_role::ADMINISTRATOR);
				}
				break;
		}
	}

	function onEndLogout()
	{
		Ld_Auth::logout();
	}

	function onPluginVersion(&$versions)
	{
		$versions[] = array(
			'name' => 'La Distribution Authentication',
			'version' => '0.4.1',
			'author' => 'h6e.net',
			'homepage' => 'http://h6e.net/',
			'rawdescription' => _m('SSO support for Status.net in La Distribution')
		);
		return true;
	}

}

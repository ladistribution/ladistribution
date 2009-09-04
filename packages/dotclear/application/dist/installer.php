<?php

class Ld_Installer_Dotclear extends Ld_Installer
{

	function install($preferences = array())
	{
		if (strlen($preferences['admin_password']) < 6) {
			throw new Exception("Password should be at least 6 characters long.");
		}

		parent::install($preferences);

		$this->create_config_file($preferences);

		$this->create_htaccess();

		// poor man patch
		$this->addPrepend($this->absolutePath . "/admin/index.php");
		$this->addPrepend($this->absolutePath . "/admin/install/index.php");
		$this->addPrepend($this->absolutePath . "/inc/prepend.php");
	}

	function update()
	{
		parent::update();

		// poor man patch
		$this->addPrepend($this->absolutePath . "/admin/index.php");
		$this->addPrepend($this->absolutePath . "/admin/install/index.php");
		$this->addPrepend($this->absolutePath . "/inc/prepend.php");
	}

	function create_config_file($preferences, $auth = false)
	{
		$cfg = "<?php\n";
		$cfg .= 'define("DC_DBPERSIST", false);' . "\n";
		$cfg .= "define('DC_MASTER_KEY', '" . Ld_Auth::generatePhrase() . "');\n";
		$cfg .= "define('DC_SESSION_NAME', '" . 'dcxd' . "');\n";
		$cfg .= "define('DC_PLUGINS_ROOT',dirname(__FILE__).'/../plugins');\n";
		$cfg .= "define('DC_TPL_CACHE',dirname(__FILE__).'/../cache');\n";
		if ($auth) {
			$cfg .= "define('DC_AUTH_CLASS','ldDcAuth');\n";
			$cfg .= '$' . "__autoload['ldDcAuth'] = dirname(__FILE__).'/../dist/ld.auth.php';\n";
		}
		Ld_Files::put($this->absolutePath . "/inc/config.php", $cfg);
	}

	public function create_htaccess()
	{
		if (constant('LD_REWRITE')) {
			$path = $this->site->getBasePath() . '/' . $this->path . '/';
			$htaccess  = "RewriteEngine on\n";
			$htaccess .= "RewriteBase $path\n";
			$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-f\n";
			$htaccess .= "RewriteCond %{REQUEST_FILENAME} !-d\n";
			$htaccess .= "RewriteRule (.*) index.php/$1 [L]\n";
			Ld_Files::put($this->absolutePath . "/.htaccess", $htaccess);
		}
	}

	function addPrepend($filename)
	{
		$file = file($filename);
		$file[11] = "\n" . "require_once '" . $this->absolutePath . "/dist/prepend.php';" . "\n";
		Ld_Files::put($filename, implode("", $file));
	}

	function postInstall($preferences = array())
	{
		$this->httpClient = new Zend_Http_Client();
		$this->httpClient->setCookieJar();

		// We use Dotclear installer to actually perform the install
		// FIXME: we must check the preferences before sending to Dotclear
		$this->httpClient->setUri($this->instance->getUrl() . '/admin/install/index.php');
		$this->httpClient->setParameterPost(array(
			'u_firstname'  => $preferences['admin_firstname'],
			'u_name'       => $preferences['admin_name'],
			'u_email'      => $preferences['admin_email'],
			'u_login'      => $preferences['admin_username'],
			'u_pwd'        => $preferences['admin_password'],
			'u_pwd2'       => $preferences['admin_password'],
		));
		$response = $this->httpClient->request('POST');
        // if (constant('LD_DEBUG')) {
        //  echo $response->getBody();
        // } 

		// We need to log in to really finish install
		$this->httpClient->setUri($this->instance->getUrl() . '/admin/auth.php');
		$this->httpClient->setParameterPost(array(
			'user_id'  => $preferences['admin_username'],
			'user_pwd' => $preferences['admin_password']
		));
		$response = $this->httpClient->request('POST');
        // if (constant('LD_DEBUG')) {
        //  echo $response->getBody();
        // }

		if (constant('LD_REWRITE')) {

			$con = $this->instance->getDbConnection();
			$dbPrefix = $this->instance->getDbPrefix();

			$blog_table = $dbPrefix . 'blog';
			$setting_table = $dbPrefix . 'setting';

			$blog_name = $preferences['title'];
			$blog_url = $this->instance->getUrl();

			$con->query("UPDATE $blog_table SET blog_name = '$blog_name', blog_url = '$blog_url' WHERE blog_id = 'default'");
			$con->query("INSERT INTO $setting_table SET setting_type = 'string', setting_ns = 'system', setting_value = 'path_info', blog_id = 'default', setting_id = 'url_scan'");

		}

		$this->create_config_file($preferences, true);
	}

	function uninstall()
	{
		$db = $this->instance->getDbConnection();
		$dbPrefix = $this->instance->getDbPrefix();

		$tables = array(
			// 1st round
			'comment', 'link', 'log', 'meta', 'permissions', 'ping',
			'post_media', 'session', 'setting', 'spamrule', 'version',
			// 2nd round
			'media', 'post', 'user',
			// 3d round
			'category',
			// 4th round
			'blog'
		);

		foreach ($tables as $table) {
			$tablename = $dbPrefix . $table;
			$db->query("DROP TABLE IF EXISTS $tablename");
		}

		parent::uninstall();
	}

}

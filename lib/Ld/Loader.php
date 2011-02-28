<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Loader
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Loader
{

    public static $site = null;

    public static $config = array();

    public static $constantsDefined = false;

    public static $autoloadRegistered = false;

    public static function defineConstants($dir)
    {
        date_default_timezone_set('UTC');

        defined('LD_TMP_DIR') OR define('LD_TMP_DIR', realpath( $dir ) . '/tmp');

        defined('LD_LIB_DIR') or define('LD_LIB_DIR', realpath( $dir ) . '/lib' );

        defined('LD_SERVER') OR define('LD_SERVER', 'http://ladistribution.net/');

        defined('LD_RELEASE') OR define('LD_RELEASE', 'edge');

        set_include_path( LD_LIB_DIR . PATH_SEPARATOR . get_include_path() );

        self::$constantsDefined = true;
    }

    public static function registerAutoload()
    {
        // Zend Framework & Ld Libraries
        require_once 'Zend/Loader/Autoloader.php';
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('Ld_');

        // TODO: to be replaced by an Autoloader
        require_once 'clearbricks/common/lib.files.php';
        require_once 'clearbricks/zip/class.zip.php';
        require_once 'clearbricks/zip/class.unzip.php';

        self::$autoloadRegistered = true;
    }

    public static function loadSite($dir)
    {
        $dir = realpath($dir);

        if (!self::$constantsDefined) {
            self::defineConstants($dir);
        }

        if (!self::$autoloadRegistered) {
            self::registerAutoload();
        }

        // Site configuration
        $config = self::$config = Ld_Files::getJson($dir . '/dist/config.json');
        if (empty($config['dir'])) {
            $config['dir'] = $dir;
        }
        if (empty($config['host']) && isset($_SERVER['SERVER_NAME'])) {
            $config['host'] = $_SERVER['SERVER_NAME'];
            if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80') {
                $config['host'] .= ':' . $_SERVER['SERVER_PORT'];
            }
        }

        // Site object
        $site = self::$site = new Ld_Site_Local($config);
        Zend_Registry::set('site', $site);

        self::setupPlugins();
        self::setupAuthentication();
        self::setupLocales();

        if (!empty($config['timezone'])) {
            date_default_timezone_set($config['timezone']);
        }

        // This constants should bet set after plugins are loaded
        defined('LD_DEBUG') OR define('LD_DEBUG', false);
        defined('LD_REWRITE') OR define('LD_REWRITE', true);
        defined('LD_BREADCRUMBS') OR define('LD_BREADCRUMBS', false);
        defined('LD_APPEARANCE') OR define('LD_APPEARANCE', true);

        defined('LD_SVN') OR define('LD_SVN', Ld_Files::exists($dir . '/.svn'));
        if (!constant('LD_SVN')) {
            defined('LD_COMPRESS_JS') OR define('LD_COMPRESS_JS', true);
            defined('LD_COMPRESS_CSS') OR define('LD_COMPRESS_CSS', true);
        }

        // Legacy CSS Constant
        defined('H6E_CSS') OR define('H6E_CSS', $site->getUrl('css'));

        Ld_Plugin::doAction("Site:loaded", $site);

        return $site;
    }

    public static function loadSubSite($dir)
    {
        $dir = realpath($dir);

        // Site configuration
        self::$config = Ld_Files::getJson($dir . '/dist/config.json');
        if (empty(self::$config['dir'])) {
            self::$config['dir'] = $dir;
        }
        if (empty(self::$config['host']) && isset($_SERVER['SERVER_NAME'])) {
            self::$config['host'] = $_SERVER['SERVER_NAME'];
        }

        // Plugins
        if (empty(self::$config['active_plugins'])) {
            self::$config['active_plugins'] = array('subsite');
        }
        self::setupPlugins();

        // Site object
        $site = self::$site = new Ld_Site_Child(self::$config);
        $site->setParentSite( Zend_Registry::get('site') );
        Zend_Registry::set('site', $site);

        return $site;
    }

    public static function setupPlugins()
    {
        $active_plugins = self::$site->getConfig('active_plugins');
        if (empty($active_plugins)) {
            $active_plugins = array();
        }

        global $ld_global_plugins;
        if (isset($ld_global_plugins)) {
            $active_plugins = array_merge($ld_global_plugins, $active_plugins);
            $active_plugins = array_unique($active_plugins);
        }

        foreach ($active_plugins as $plugin) {
            $plugin = strtolower($plugin);
            $fileName = self::$site->getDirectory('shared') . '/plugins/' . $plugin . '.php';
            $className = 'Ld_Plugin_' . Zend_Filter::filterStatic($plugin, 'Word_DashToCamelCase');
            if (class_exists($className, false) == false && Ld_Files::exists($fileName)) {
                require_once $fileName;
            }
            if (class_exists($className, false) && method_exists($className, 'load')) {
                $class = new $className;
                $class->load();
            }
        }
    }

    public static function setupAuthentication()
    {
        // Setup Authentication
        if (function_exists('mcrypt_ecb') && isset(self::$config['secret'])) {
            $cookieManager = new Ld_Cookie(self::$config['secret']);
        } else {
            $cookieManager = new Ld_Cookie_Simple();
        }
        $path = self::$site->getPath();
        $cookieConfig = array('cookieName' => 'ld-auth', 'cookiePath' => empty($path) ? '/' : $path);
        if (class_exists('Ld_Plugin')) {
            $cookieConfig = Ld_Plugin::apply('Loader:authCookieConfig', $cookieConfig);
        }
        $authStorage = new Ld_Auth_Storage_Cookie($cookieManager, $cookieConfig);
        $auth = Zend_Auth::getInstance();
        $auth->setStorage($authStorage);
    }

    public static function setupLocales()
    {
        $site = self::$site;

        $dir = $site->getDirectory('shared') . '/locales';

        $default_mo = $dir . '/ld/en_US/default.mo';
        if (Ld_Files::exists($default_mo)) {
            $options = array();
            $options['disableNotices'] = true; // 'auto' locale can generate notices we better avoid
            $adapter = new Zend_Translate('gettext', $default_mo, 'en_US', $options);
            $locales = Ld_Files::getDirectories($dir . '/ld/', array('en_US'));
            foreach ($locales as $locale) {
                $adapter->addTranslation($dir . "/ld/$locale/default.mo", $locale);
                if (Ld_Files::exists($dir . "/admin/$locale/default.mo")) {
                    $adapter->addTranslation($dir . "/admin/$locale/default.mo", $locale);
                }
            }
            if (isset($_COOKIE['ld-lang']) && $adapter->isAvailable($_COOKIE['ld-lang'])) {
                $adapter->setLocale($_COOKIE['ld-lang']);
            }
            Zend_Registry::set('Zend_Translate', $adapter);
        }
    }

}

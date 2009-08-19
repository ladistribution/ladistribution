<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Loader
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Loader
{

    public static function defineConstants($dir)
    {
        date_default_timezone_set('UTC');

        defined('LD_DEBUG') OR define('LD_DEBUG', false);

        defined('LD_REWRITE') OR define('LD_REWRITE', true);

        defined('LD_SESSION') OR define('LD_SESSION', true);

        defined('LD_TMP_DIR') OR define('LD_TMP_DIR', realpath( $dir ) . '/tmp');

        defined('LD_LIB_DIR') or define('LD_LIB_DIR', realpath( $dir ) . '/lib' );

        defined('LD_SERVER') OR define('LD_SERVER', 'http://ladistribution.net/');

        defined('LD_RELEASE') OR define('LD_RELEASE', 'edge');

        if (constant('LD_DEBUG')) {
            error_reporting( E_ALL | E_NOTICE /* | E_STRICT */);
        }

        set_include_path( LD_LIB_DIR . PATH_SEPARATOR . get_include_path() );
    }

    public static function loadSite($dir)
    {
        self::defineConstants($dir);

        // Zend Framework & Ld Libraries
        require_once 'Zend/Loader/Autoloader.php';
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->registerNamespace('Ld_');

        // TODO: to be replaced by an Autoloader
        require_once 'clearbricks/common/lib.files.php';
        require_once 'clearbricks/zip/class.zip.php';
        require_once 'clearbricks/zip/class.unzip.php';

        // Site configuration
        $config = Ld_Files::getJson($dir . '/dist/config.json');
        if (empty($config['dir'])) {
            $config['dir'] = $dir;
        }
        if (empty($config['host']) && isset($_SERVER['SERVER_NAME'])) {
            $config['host'] = $_SERVER['SERVER_NAME'];
        }

        // Site object
        $site = new Ld_Site_Local($config);
        Zend_Registry::set('site', $site);

        // Setup Authentication
        if (function_exists('mcrypt_ecb') && isset($config['secret'])) {
            $cookieManager = new Ld_Cookie($config['secret']);
        } else {
            $cookieManager = new Ld_Cookie_Simple();
        }
        $path = $site->getPath();
        $cookieConfig = array('cookieName' => 'ld-auth', 'cookiePath' => empty($path) ? '/' : $path);
        $authStorage = new Ld_Auth_Storage_Cookie($cookieManager, $cookieConfig);
        $auth = Zend_Auth::getInstance();
        $auth->setStorage($authStorage);

        // Locale
        $default_mo = $site->getDirectory('shared') . '/locales/ld/en_US/default.mo';
        if (file_exists($default_mo)) {
            $adapter = new Zend_Translate('gettext', $default_mo, 'en_US');
            $locales = Ld_Files::getDirectories($site->getDirectory('shared') . '/locales/ld/', array('en_US'));
            foreach ($locales as $locale) {
                $adapter->addTranslation($site->getDirectory('shared') . "/locales/ld/$locale/default.mo", $locale);
            }
            Zend_Registry::set('Zend_Translate', $adapter);
        }

        // Legacy CSS Constant
        defined('H6E_CSS') OR define('H6E_CSS', $site->getUrl('css'));

        return $site;
    }

}

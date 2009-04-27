<?php

class Bootstrap
{

    protected static $_front;

    protected static $_registry;

    function run()
    {
        self::prepare();

        self::dispatch();
    }

    function prepare()
    {
        // Zend Framework
        require_once 'Zend/Loader.php';
        Zend_Loader::registerAutoload();

        // Clearbricks
        require_once 'clearbricks/common/lib.files.php';
        require_once 'clearbricks/zip/class.zip.php';
        require_once 'clearbricks/zip/class.unzip.php';

        self::$_registry = Zend_Registry::getInstance();

        self::$_registry['site'] = new Ld_Site_Local();

        self::setupMvc();
        self::setupRoutes();
        self::setupCache();
    }

    public static function setupMvc()
    {
        $mvc = Zend_Layout::startMvc(array('layoutPath' => dirname(__FILE__) . '/default/views/layouts'));

        $mvc->getView()->headLink()->appendStylesheet(LD_CSS_URL . '/h6e-minimal/h6e-minimal.css', 'screen');
        $mvc->getView()->headLink()->appendStylesheet(LD_CSS_URL . '/ld-ui/ld-bars.css', 'screen');

        self::$_front = Zend_Controller_Front::getInstance();

        self::$_front->addControllerDirectory(
            dirname(__FILE__) . '/slotter/controllers', 'slotter'
        );

        self::$_front->addControllerDirectory(
            dirname(__FILE__) . '/identity/controllers', 'identity'
        );

        self::$_front->addControllerDirectory(
            dirname(__FILE__) . '/default/controllers', 'default'
        );
    }

    public static function setupRoutes()
    {
        $router = self::$_front->getRouter();

        $route = new Zend_Controller_Router_Route(
            'slotter/instance/id/:id/:action',
            array('module' => 'slotter', 'controller' => 'instance', 'action' => 'manage')
        );
        $router->addRoute('instance-action', $route);

        $route = new Zend_Controller_Router_Route(
            'identity/:id',
            array('module' => 'identity', 'controller' => 'openid', 'action' => 'profile')
        );
        $router->addRoute('identity', $route);
    }

    public static function setupCache()
    {
        Ld_Files::createDirIfNotExists(LD_TMP_DIR . '/cache/');

        $frontendOptions = array(
           'lifetime' => 60, // cache lifetime of 1 minute
           'automatic_serialization' => true
        );

        $backendOptions = array(
            'cache_dir' => LD_TMP_DIR . '/cache/'
        );

        self::$_registry['cache'] = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
    }

    /**
     * Dispatch the request.
     */
    public static function dispatch()
    {
        self::$_front->dispatch();
    }

}

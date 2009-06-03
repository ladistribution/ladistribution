<?php

class Bootstrap
{

    protected static $_front;

    protected static $_registry;

    public static function run()
    {
        self::prepare();

        self::dispatch();
    }

    public static function prepare()
    {
        self::$_registry = Zend_Registry::getInstance();

        self::setupMvc();
        self::setupRoutes();
        self::setupCache();
    }

    public static function setupMvc()
    {
        $site = self::$_registry['site'];

        $mvc = Zend_Layout::startMvc(array('layoutPath' => $site->getDirectory('shared') . '/modules/default/views/layouts'));

        $mvc->getView()->headLink()->appendStylesheet($site->getUrl('css') . '/h6e-minimal/h6e-minimal.css', 'screen');
        $mvc->getView()->headLink()->appendStylesheet($site->getUrl('css') . '/ld-ui/ld-bars.css', 'screen');

        self::$_front = Zend_Controller_Front::getInstance();

        self::$_front->addControllerDirectory(
            $site->getDirectory('shared') . '/modules/slotter/controllers', 'slotter'
        );

        self::$_front->addControllerDirectory(
            $site->getDirectory('shared') . '/modules/identity/controllers', 'identity'
        );

        self::$_front->addControllerDirectory(
            $site->getDirectory('shared') . '/modules/default/controllers', 'default'
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
        $cacheDirectory = LD_TMP_DIR . '/cache/';

        Ld_Files::createDirIfNotExists($cacheDirectory);

        if (file_exists($cacheDirectory) && is_writable($cacheDirectory)) {
            $frontendOptions = array(
               'lifetime' => 60, // cache lifetime of 1 minute
               'automatic_serialization' => true
            );
            $backendOptions = array(
                'cache_dir' => $cacheDirectory
            );
            self::$_registry['cache'] = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
        }
    }

    /**
     * Dispatch the request.
     */
    public static function dispatch()
    {
        self::$_front->dispatch();
    }

}

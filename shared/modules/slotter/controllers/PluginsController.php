<?php

require_once 'BaseController.php';

/**
 * Settings controller
 */
class Slotter_PluginsController extends Slotter_BaseController
{

    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->_acl->isAllowed($this->userRole, 'plugins', 'manage')) {
            $this->_disallow();
        }

        $translator = $this->getTranslator();
        $this->appendTitle( $translator->translate('Plugins') );
    }

    public function indexAction()
    {
        $site = $this->getSite();

        if ($this->getRequest()->isPost() && $this->_hasParam('plugins')) {
            $active_plugins = array();
            foreach ((array)$this->_getParam('plugins') as $id => $status) {
                if ($status == 'active') {
                    $active_plugins[] = $id;
                }
            }
            $site->setConfig('active_plugins', $active_plugins);
        }

        if ($this->getRequest()->isPost() && $this->_hasParam('configuration')) {
            $configuration = (array)$this->_getParam('configuration');
            $site->setConfig($configuration);
            $this->_redirector->gotoSimple('index', 'plugins', 'slotter');
            return;
        }

        $active_plugins = (array)$site->getConfig('active_plugins');

        $plugins = array();
        $plugin_files = Ld_Files::getFiles($site->getDirectory('shared') . '/plugins');
        foreach ($plugin_files as $fileName) {
            $id = strtolower(str_replace('.php', '', $fileName));
            $className = 'Ld_Plugin_' . Zend_Filter::filterStatic($id, 'Word_DashToCamelCase');
            require_once $site->getDirectory('shared') . '/plugins/' . $id . '.php';
            if (class_exists($className, false) && method_exists($className, 'infos')) {
                $plugin = new $className;
                $plugin->active = in_array($id, $active_plugins);
                $plugins[$id] = $plugin;
            }
        }

        ksort($plugins);

        $this->view->plugins = $plugins;

        $this->view->configuration = $site->getConfig();
    }

}

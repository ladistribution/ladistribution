<?php

/**
 * Base controller
 */
class Slotter_BaseController extends Ld_Controller_Action
{

    public function init()
    {
        parent::init();

        if (empty($this->_registry['instance'])) {
            throw new Exception('No Instance defined.');
        }

        $this->view->addHelperPath(dirname(__FILE__) . '/../views/helpers/', 'View_Helper');

        if (empty($this->view->action)) {
            $this->view->action = $this->action = $this->getRequest()->getActionName();
        }

        $this->_initNavigation();
    }

    protected function _initNavigation()
    {
        $settings = array();
        $settings[] = array( 'label' => $this->translate('General'), 'module' => 'slotter', 'controller' => 'settings' );
        // $settings[] = array( 'label' => $this->translate('Users'), 'module' => 'slotter', 'controller' => 'users' );
        if (defined('LD_APPEARANCE') && constant('LD_APPEARANCE')) {
            $settings[] = array( 'label' => $this->translate('Colors'), 'module' => 'slotter', 'controller' => 'appearance' );
            if (Ld_Plugin::applyFilters('Site:customCss', true)) {
                $settings[] = array( 'label' => $this->translate('CSS'), 'module' => 'slotter', 'controller' => 'appearance', 'action' => 'css' );
            }
        }
        if ($this->admin->userCan('manage', 'repositories')) {
            $settings[] = array( 'label' => $this->translate('Repositories'), 'module' => 'slotter', 'controller' => 'repositories' );
        }
        if ($this->admin->userCan('manage', 'databases')) {
            $settings[] = array( 'label' => $this->translate('Databases'), 'module' => 'slotter', 'controller' => 'databases' );
        }
        if (!$this->site->isChild()) {
            if (defined('LD_MULTI_DOMAINS') && constant('LD_MULTI_DOMAINS')) {
                $settings[] = array( 'label' => $this->translate('Domains'), 'module' => 'slotter', 'controller' => 'domains' );
            }
            $plugins = $this->site->getPlugins();
            if (!empty($plugins)) {
                $settings[] = array( 'label' => $this->translate('Xtras'), 'module' => 'slotter', 'controller' => 'plugins' );
            }
        }

        $pages = array(
            array( 'label' => $this->translate('Home'), 'module' => 'slotter', 'route' => 'default',
                'pages' => array(
                    array( 'label' => $this->translate('Applications'), 'module'=> 'slotter', 'route' => 'default'),
                    // array( 'label' => $this->translate('Ressources'), 'module' => 'slotter', 'controller' => 'repositories', 'pages' => array(
                    //          array( 'label' => $this->translate('Repositories'), 'module' => 'slotter', 'controller' => 'repositories' ),
                    //          array( 'label' => $this->translate('Databases'), 'module' => 'slotter', 'controller' => 'databases' ),
                    //      )),
                    array( 'label' => $this->translate('Users'), 'module' => 'slotter', 'controller' => 'users' ),
                    array( 'label' => $this->translate('Settings'), 'module' => 'slotter', 'controller' => 'settings', 'pages' => $settings),
            ))
        );

        $this->_container = new Zend_Navigation($pages);
        $this->view->navigation($this->_container);
    }

    /* Deprecated */

    protected function _disallow() { return $this->disallow(); }

}

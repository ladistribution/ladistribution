<?php

require_once 'BaseController.php';

/**
 * Settings controller
 */
class Slotter_SettingsController extends Slotter_BaseController
{

    /**
     * preDispatch
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->_acl->isAllowed($this->userRole, null, 'admin')) {
            $this->_disallow();
        }

        $translator = $this->getTranslator();
        $this->appendTitle( $translator->translate('General Settings') );
    }

    function indexAction()
    {
        if ($this->getRequest()->isPost() && $this->_hasParam('configuration')) {
            $configuration = $this->getSite()->getConfig();
            foreach ($this->_getParam('configuration') as $key => $value) {
                $configuration[$key] = $value;
            }
            $this->getSite()->setConfig($configuration);
            $this->_updateAdminBaseUrl();
        }

        $this->view->preferences = $this->_getPreferences();

        $this->view->configuration = $this->getSite()->getConfig();
    }

    protected function _updateAdminBaseUrl()
    {
        $frontController = Zend_Controller_Front::getInstance();
        if ($configuration['root_admin']) {
            $baseUrl = $this->site->getPath();
        } else {
            $baseUrl = $this->site->getPath() . '/' . $this->admin->getPath();
        }
        $frontController->setBaseUrl($baseUrl);
    }

    protected function _getPreferences()
    {
        $translator = $this->getTranslator();

        $preferences = array();

        $preferences[] = array(
            'name' => 'name', 'label' => $translator->translate('Site Name'), 'type' => 'text', 'defaultValue' => 'La Distribution'
        );

        if (!defined('LD_MULTI_DOMAINS') || !constant('LD_MULTI_DOMAINS')) {
        $preferences[] = array(
            'name' => 'host', 'label' => $translator->translate('Host'), 'type' => 'text'
        );
        }

        $preferences[] = array(
            'name' => 'path', 'label' => $translator->translate('Path'), 'type' => 'text'
        );

        $preferences[] = array(
            'name' => 'open_registration', 'label' => $translator->translate('Anyone can register?'),
            'type' => 'boolean', 'defaultValue' => false
        );

        if (!defined('LD_MULTI_DOMAINS') || !constant('LD_MULTI_DOMAINS')) {

        $options = array();
        $options[] = array(
            'value' => '',
            'label' => '&#x2716; ' .  $translator->translate('None')
        );
        $options[] = array(
            'value' => $this->admin->getPath(),
            'label' => '&#x2605; ' . $this->admin->getName() . ' /' . $this->admin->getPath() . '/'
        );
        foreach ($this->getSite()->getApplicationsInstances(array('admin')) as $id => $instance) {
            $options[] = array(
                'value' => $instance->getPath(),
                'label' => '&#x25CF; ' . $instance->getName() . ' /' . $instance->getPath() . '/'
            );
        }

        $preferences[] = array(
            'name' => 'root_application', 'label' => $translator->translate('Default Application'),
            'type' => 'list', 'defaultValue' => 'admin', 'options' => $options
        );

        }

        $preferences[] = array(
            'name' => 'root_admin', 'label' => $translator->translate('Admin path on root?'),
            'type' => 'boolean', 'defaultValue' => false
        );

        $preferences = Ld_Plugin::applyFilters('Slotter:preferences', $preferences);

        return $preferences;
    }

}

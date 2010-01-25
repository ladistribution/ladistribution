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
    }

    function indexAction()
    {
        if ($this->getRequest()->isPost() && $this->_hasParam('configuration')) {
            $configuration = Ld_Files::getJson($this->getSite()->getDirectory('dist') . '/config.json');
            unset($configuration['open_registration']);
            unset($configuration['root_admin']);
            foreach ($this->_getParam('configuration') as $key => $value) {
                $configuration[$key] = $value;
            }
            Ld_Files::putJson($this->getSite()->getDirectory('dist') . '/config.json', $configuration);
        }

        $translator = $this->getTranslator();

        $this->appendTitle($translator->translate('Global settings'));

        $preferences = array();

        $preferences[] = array(
            'name' => 'host', 'label' => $translator->translate('Host'), 'type' => 'text'
        );

        $preferences[] = array(
            'name' => 'path', 'label' => $translator->translate('Path'), 'type' => 'text'
        );

        $preferences[] = array(
            'name' => 'open_registration', 'label' => $translator->translate('Anyone can register?'),
            'type' => 'boolean', 'defaultValue' => false
        );

        $options = array();
        foreach ($this->getSite()->getApplicationsInstances() as $id => $instance) {
            $options[] = array('value' => $instance->getPath(), 'label' => $instance->getName());
        }

        $preferences[] = array(
            'name' => 'root_application', 'label' => $translator->translate('Default Application'),
            'type' => 'list', 'defaultValue' => 'admin', 'options' => $options
        );

        $preferences[] = array(
            'name' => 'root_admin', 'label' => $translator->translate('Admin path on root?'),
            'type' => 'boolean', 'defaultValue' => false
        );

        $this->view->preferences = $preferences;

        $this->view->configuration = Ld_Files::getJson($this->getSite()->getDirectory('dist') . '/config.json');
    }

}

<?php

require_once 'BaseController.php';

class Slotter_DomainsController extends Slotter_BaseController
{

    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->_acl->isAllowed($this->userRole, 'domains', 'manage')) {
            $this->_disallow();
        }

        $id = $this->view->id = $this->id = $this->_getParam('id');

        $this->_handleNavigation();
    }

    protected function _handleNavigation()
    {
        $translator = $this->getTranslator();

        $domainsPage = $this->_container->findOneByLabel( $translator->translate('Domains') );
        $domainsPage->addPage(array(
            'label' => $translator->translate("New"), 'module'=> 'slotter', 'controller' => 'domains', 'action' => 'new'
        ));

        if (isset($this->id)) {
            $action = $this->getRequest()->action;
            $domainsPage->addPage(array(
                'label' => ucfirst($action),
                'module'=> 'slotter',
                'route' => 'default',
                'controller' => 'domains',
                'action' => $action,
                'params' => array('id' => $this->_getParam('id'))
            ));
        }
    }

    public function indexAction()
    {
        if ($this->getRequest()->isPost()) {
            if ($this->_hasParam('applications')) {
                foreach ($this->_getParam('applications') as $id => $domain) {
                    $application = $this->getSite()->getInstance($id);
                    $application->setInfos(array('domain' => $domain))->save();
                }
            }
            if ($this->_hasParam('defaults')) {
                foreach ($this->_getParam('defaults') as $id => $application) {
                    $originalConfig = $this->getSite()->getDomain($id);
                    if ($originalConfig['host'] == $this->site->getConfig('host')) {
                        $this->site->setConfig('root_application', $application);
                    }
                    $new = array('default_application' => $application);
                    $this->getSite()->updateDomain($id, $new);
                }
            }
            if ($this->_hasParam('default')) {
                $domain = $this->getSite()->getDomain( $this->_getParam('default') );
                $this->site->setConfig('host', $domain['host']);
                $this->site->setConfig('root_application', $domain['default_application']);
            }
        }

        $this->view->domains = $this->_getDomains();

        $this->view->default = $this->site->getConfig('host');

        $this->view->admin = $this->admin;

        $this->view->applications = $this->site->getApplicationsInstances(array('admin'));
    }

    public function newAction()
    {
        if ($this->getRequest()->isPost()) {
            $config = array(
                'host' => $this->_getParam('host'),
                'default_application' => $this->_getParam('default_application'),
            );
            $this->getSite()->addDomain($config);
            $this->_redirector->gotoSimple('index', 'domains');
            return;
        }

        $this->view->preferences = $this->_getPreferences();
    }

    public function editAction()
    {
        if ($this->getRequest()->isPost()) {
            $id = $this->_getParam('id');
            $original = $this->getSite()->getDomain($id);
            if ($original['host'] == $this->site->getConfig('host')) {
                $this->site->setConfig('host', $this->_getParam('host'));
                $this->site->setConfig('root_application', $this->_getParam('default_application'));
            }
            $new = array(
                'host' => $this->_getParam('host'),
                'default_application' => $this->_getParam('default_application'),
            );
            $this->getSite()->updateDomain($id, $new);
            $this->_redirector->gotoSimple('index', 'domains');
            return;
        }

        $this->view->configuration = $this->site->getDomain( $this->_getParam('id') );

        $this->view->preferences = $this->_getPreferences();
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $id = $this->_getParam('id');
            $this->getSite()->deleteDomain($id);
            $this->_redirector->gotoSimple('index', 'domains');
            return;
        }
    }
    
    protected function _getDomains()
    {
        $domains = $this->site->getDomains();

        if (empty($domains)) {
             $this->site->addDomain(array(
                 'host' => $this->site->getConfig('host'),
                 'default_application' => $this->site->getConfig('root_application')
             ));
             $domains = $this->site->getDomains();
        }

        return $domains;
    }

    protected function _getPreferences()
    {
        $translator = $this->getTranslator();

        $preferences = array();
        $preferences[] = array('type' => 'text', 'name' => 'host', 'label' => $translator->translate('Host'));

        $options = array();
        $options[] = array(
            'value' => '',
            'label' => '✖ ' .  $translator->translate('None')
        );
        $options[] = array(
            'value' => $this->admin->getPath(),
            'label' => '★ ' . $this->admin->getName() . ' /' . $this->admin->getPath() . '/'
        );
        foreach ($this->getSite()->getApplicationsInstances(array('admin')) as $id => $instance) {
            $options[] = array(
                'value' => $instance->getPath(),
                'label' => '● ' . $instance->getName() . ' /' . $instance->getPath() . '/'
            );
        }

        $preferences[] = array(
            'name' => 'default_application', 'label' => $translator->translate('Default Application'),
            'type' => 'list', 'defaultValue' => 'admin', 'options' => $options
        );

        return $preferences;
    }

}

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
        $domainsPage = $this->_container->findOneByLabel( $this->translate('Domains') );

        if ($domainsPage) {

            $domainsPage->addPage(array(
                'label' => $this->translate("New"), 'module'=> 'slotter', 'controller' => 'domains', 'action' => 'new'
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
    }

    public function indexAction()
    {
        if ($this->getRequest()->isPost()) {
            $site = $this->getSite();
            // applications/domains mapping
            if ($this->_hasParam('applications')) {
                foreach ($this->_getParam('applications') as $appId => $domainId) {
                    $appInstance = $site->getInstance($appId);
                    $appInstance->setInfos(array('domain' => $domainId))->save();
                }
            }
            // handle default applications
            if ($this->_hasParam('defaults')) {
                foreach ($this->_getParam('defaults') as $domainId => $appPath) {

                    $domainConfig = $site->getDomain($domainId);
                    if ($domainConfig['host'] == $site->getConfig('host')) {
                        $site->setConfig('root_application', $appPath);
                    }
                    $domainConfig['default_application'] = $appPath;
                    $site->updateDomain($domainId, $domainConfig);
                    // update app
                    // if (!empty($appPath)) {
                    //     $appInstance = $site->getInstance($appPath);
                    //     $appInstance->setInfos(array('domain' => $domainId))->save();
                    // }
                }
            }
            // handle default domain
            if ($this->_hasParam('default')) {
                $domain = $site->getDomain( $this->_getParam('default') );
                $site->setConfig('host', $domain['host']);
                $site->setConfig('root_application', $domain['default_application']);
            }
        }

        $this->view->domains = $this->getSite()->getDomains();

        $this->view->default = $this->site->getConfig('host');

        $this->view->admin = $this->admin;

        // application list, with admin first
        $this->view->applications = array_merge(array($this->admin->getId() => $this->admin), $this->site->getApplicationsInstances(array('admin')));
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

    /**
     * Order action.
     */
    public function orderAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost() && $this->_hasParam('domains')) {

            $domains = $this->getSite()->getDomains();

            $order = 0;
            foreach ($this->_getParam('domains') as $id) {
                if (isset($domains[$id])) {
                    $domains[$id]['order'] = $order;
                    $order ++;
                }
            }

            $this->getSite()->writeDomains($domains);

            $this->noRender();
            $this->getResponse()->appendBody('ok');
        }
    }

    protected function _getPreferences()
    {
        $preferences = array();
        $preferences[] = array('type' => 'text', 'name' => 'host', 'label' => $this->translate('Host'));

        $options = array();
        $options[] = array(
            'value' => '',
            'label' => '✖ ' .  $this->translate('None')
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
            'name' => 'default_application', 'label' => $this->translate('Default Application'),
            'type' => 'list', 'defaultValue' => 'admin', 'options' => $options
        );

        return $preferences;
    }

}

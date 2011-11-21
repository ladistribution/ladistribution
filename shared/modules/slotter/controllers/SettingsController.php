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

        $this->appendTitle( $this->translate('General Settings') );
    }

    public function indexAction()
    {
        if ($this->getRequest()->isPost() && $this->_hasParam('configuration')) {
            $configuration = $this->getSite()->getConfig();
            foreach ($this->_getParam('configuration') as $key => $value) {
                $configuration[$key] = $value;
            }
            $this->getSite()->setConfig($configuration);
            $this->view->notification = $this->translate("Settings updated");
        }

        if ($this->getRequest()->isPost() && $this->_hasParam('locales')) {
            $this->_updateLocales();
        }

        $this->view->preferences = $this->_getPreferences();
        $this->view->configuration = $this->getSite()->getConfig();

        $this->view->allLocales = $this->getSite()->getAllLocales();
        $this->view->locales = $this->getSite()->getLocales();

        $this->view->canManageLocales = $this->_acl->isAllowed($this->userRole, 'locales', 'manage');
    }

    protected function _getPreferences()
    {
        $preferences = array();

        $preferences[] = array(
            'name' => 'name', 'label' => $this->translate('Site Name'), 'type' => 'text', 'defaultValue' => 'La Distribution'
        );

        if (!defined('LD_MULTI_DOMAINS') || !constant('LD_MULTI_DOMAINS')) {
        $preferences[] = array(
            'name' => 'host', 'label' => $this->translate('Host'), 'type' => 'text'
        );
        }

        // $preferences[] = array(
        //     'name' => 'path', 'label' => $this->translate('Path'), 'type' => 'text'
        // );

        $preferences[] = array(
            'name' => 'open_registration', 'label' => $this->translate('Anyone can register?'),
            'type' => 'boolean', 'defaultValue' => false
        );

        if (!defined('LD_MULTI_DOMAINS') || !constant('LD_MULTI_DOMAINS')) {
        $options = array();
        $options[] = array(
            'value' => '',
            'label' => '&#x2716; ' .  $this->translate('None')
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
            'name' => 'root_application', 'label' => $this->translate('Default Application'),
            'type' => 'list', 'defaultValue' => 'admin', 'options' => $options
        );
        }

        $tz_options = array();
        $tz_options[] = array('value' => '', 'label' => '&#x2716; ' .  $this->translate('Default'));
        foreach (DateTimeZone::listIdentifiers() as $value) {
            if (preg_match('/^(America|Antartica|Arctic|Asia|Atlantic|Europe|Indian|Pacific)\//', $value)) {
                $tz_options[] = array('value' => $value, 'label' => $value);
            }
        }
        $preferences[] = array(
            'name' => 'timezone', 'label' => $this->translate('Timezone'),
            'type' => 'list', 'defaultValue' => 'admin', 'options' => $tz_options
        );

        // $preferences[] = array(
        //     'name' => 'root_admin', 'label' => $this->translate('Admin path on root?'),
        //     'type' => 'boolean', 'defaultValue' => false
        // );

        $preferences = Ld_Plugin::applyFilters('Slotter:preferences', $preferences);

        return $preferences;
    }

    protected function _updateLocales()
    {
        // Update configuration
        $locales = array();
        foreach ($this->_getParam('locales') as $id => $state) {
            $locales[] = $id;
        }
        $this->getSite()->updateLocales($locales);

        // Collect remote endpoints
        $allEndpoints = array();
        foreach ($this->getSite()->getRepositoriesConfiguration() as $repository) {
            if ($repository['type'] == 'remote') {
                $allEndpoints[] = $repository['endpoint'];
            }
        }
        // Install extra repositories
        foreach ($locales as $locale) {
            $shortLocale = substr($locale, 0, 2);
            if ($shortLocale == 'en') {
                continue;
            }
            $mainEndpoints = array(
                'http://ladistribution.net/repositories/edge/main',
                'http://ladistribution.net/repositories/europe/main',
                'http://ladistribution.net/repositories/danube/main',
                'http://ladistribution.net/repositories/concorde/main');
            foreach ($mainEndpoints as $endpoint) {
                if (in_array($endpoint, $allEndpoints)) {
                    $localeEndpoint = str_replace('/main', '/' . $shortLocale, $endpoint);
                    if (!in_array($localeEndpoint, $allEndpoints)) {
                        $this->getSite()->addRepository(array(
                            'type' => 'remote', 'endpoint' => $localeEndpoint, 'name' => ''
                        ));
                    }
                }
            }
        }

        // Install available locales packages for applications
        foreach ($locales as $locale) {

            $locale = str_replace('_', '-', strtolower($locale));

            // Main locale package
            $packageId = "ld-locale-$locale";
            if (!$this->getSite()->isPackageInstalled($packageId) && $this->getSite()->hasPackage($packageId)) {
                $this->getSite()->createInstance($packageId);
            }

            // Install available locales packages for applications
            foreach ($this->getSite()->getInstances() as $id => $infos) {
                $instance = $this->getSite()->getInstance($id);
                if (isset($instance)) {
                    $packageId = $instance->getPackageId() . '-locale-' . $locale ;
                    $instance->addExtension($packageId);
                }
            }

        }
    }
}

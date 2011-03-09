<?php

require_once 'BaseController.php';

/**
 * Locales controller
 */
class Slotter_LocalesController extends Slotter_BaseController
{

    /**
     * preDispatch
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->_acl->isAllowed($this->userRole, 'locales', 'manage')) {
            $this->_disallow();
        }

        $this->appendTitle( $this->translate('Locales') );
    }

    public function indexAction()
    {
        if ($this->getRequest()->isPost() && $this->_hasParam('locales')) {

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

            // in this case, we believe the user wants to go back to the index
            // $this->_redirector->gotoSimple('index', 'index');
            // return;

        }

        $this->view->allLocales = $this->getSite()->getAllLocales();

        $this->view->locales = $this->getSite()->getLocales();
    }

}

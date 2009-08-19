<?php

require_once 'BaseController.php';

/**
 * Locales controller
 */
class Slotter_LocalesController extends Slotter_BaseController
{

    protected function _getAllLocales()
    {
        return array(
            'en_US' => 'English (USA)',
            'fr_FR' => 'Français (France)',
            'de_DE' => 'Deutsch (Deutschland)'
        );
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

            // Install available locales packages for applications
            foreach ($locales as $locale) {

                $locale = str_replace('_', '-', strtolower($locale));

                // Main locale package
                $packageId = "ld-locale-$locale";
                if (!$this->getSite()->isPackageInstalled($packageId) && $this->getSite()->hasPackage($packageId)) {
                    $this->getSite()->createInstance($packageId);
                }

                // Install available locales packages for applications
                foreach ($this->getSite()->getApplicationsInstances() as $instance) {
                    $packageId = $instance->getPackageId() . '-locale-' . $locale ;
                    if (!$instance->hasExtension($packageId) && $this->getSite()->hasPackage($packageId)) {
                        $instance->addExtension($packageId);
                    }
                }

            }

        }

        $this->view->allLocales = $this->_getAllLocales();

        $this->view->locales = $this->getSite()->getLocales();
    }

}

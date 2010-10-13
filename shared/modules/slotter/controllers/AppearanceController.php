<?php

require_once 'BaseController.php';

/**
 * Appearance controller
 */
class Slotter_AppearanceController extends Slotter_BaseController
{

    /**
     * preDispatch
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $translator = $this->getTranslator();
        $this->appendTitle( $translator->translate('Appearance') );
    }

    public function indexAction()
    {
        if (!$this->_acl->isAllowed($this->userRole, null, 'admin')) {
            $this->_disallow();
        }

        if ($this->getRequest()->isPost() && $this->_hasParam('colors')) {
            $colors = Ld_Ui::getSiteColors();
            foreach ($this->_getParam('colors') as $key => $value) {
                $colors[$key] = $value;
            }
            $colors['version'] = md5( serialize($colors) );
            $filename = $this->site->getDirectory('dist') . '/colors.json';
            Ld_Files::putJson($filename, $colors);
        }
        $this->view->colors = Ld_Ui::getSiteColors();
    }

    public function styleAction()
    {
        // Cache-Control	max-age=604800, public
        // Expires	Fri, 15 Oct 2010 12:19:44 GMT

        $this->getResponse()->setHeader('Content-Type', 'text/css');
        Zend_Layout::getMvcInstance()->disableLayout();

        $this->view->addScriptPath(LD_LIB_DIR . '/Ld/Ui/scripts');

        if ($this->_hasParam('id')) {
            $application = $this->getSite()->getInstance( $this->_getParam('id') );
        }
        if ($application) {
            $colors = Ld_Ui::getApplicationColors($application);
        } else {
            $colors = Ld_Ui::getSiteColors();
        }

        if ($this->_hasParam('parts')) {
            $parts = explode(',', $this->_getParam('parts'));
        } else {
            if ($application) {
                $parts = $application->getColorSchemes();
            } else {
                $parts = array('base', 'bars', 'panels');
            }
        }

        $this->view->parts = $parts;
        $this->view->colors = $colors;

        $this->renderScript('site-style.phtml');
    }

}

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
            $colors = $this->site->getColors();
            foreach ($this->_getParam('colors') as $key => $value) {
                $colors[$key] = $value;
            }
            $this->site->setColors($colors);
            $this->_redirector->gotoSimple('index', 'appearance', 'slotter');
            return;
        }
        $this->view->colors = $this->site->getColors();
    }

    public function cssAction()
    {
        if (!$this->_acl->isAllowed($this->userRole, null, 'admin')) {
            $this->_disallow();
        }

        if ($this->getRequest()->isPost() && $this->_hasParam('css')) {
            $this->site->setCustomCss($this->_getParam('css'));
            $this->_redirector->gotoSimple('css', 'appearance', 'slotter');
        }

        $this->view->css = $this->site->getCustomCss();
    }

    public function styleAction()
    {
        $this->getResponse()->setHeader('Content-Type', 'text/css');
        Zend_Layout::getMvcInstance()->disableLayout();

        $this->view->addScriptPath(LD_LIB_DIR . '/Ld/Ui/scripts');

        if ($this->_hasParam('id')) {
            $application = $this->getSite()->getInstance( $this->_getParam('id') );
        }
        if (isset($application)) {
            $colors = Ld_Ui::getApplicationColors($application);
        } else {
            $colors = Ld_Ui::getSiteColors();
        }

        if ($this->_hasParam('parts')) {
            $parts = explode(',', $this->_getParam('parts'));
        } else {
            if (isset($application)) {
                $parts = $application->getColorSchemes();
            } else {
                $parts = array('base', 'bars', 'panels');
            }
        }

        $this->view->parts = $parts;
        $this->view->colors = $colors;

        $expires = 60 * 60 * 24 * 7;
        $this->getResponse()->setHeader('Cache-Control', "max-age=$expires, public");
        $this->getResponse()->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');

        $this->renderScript('site-style.phtml');
    }

}

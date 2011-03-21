<?php

require_once 'BaseController.php';

class Slotter_AppearanceController extends Slotter_BaseController
{

    public function preDispatch()
    {
        parent::preDispatch();
    }

    public function indexAction()
    {
        if (!$this->_acl->isAllowed($this->userRole, null, 'admin')) {
            $this->_disallow();
        }

        $this->appendTitle( $this->translate('Colors') );

        if ($this->getRequest()->isPost() && $this->_hasParam('colors')) {
            $colors = $this->site->getColors();
            foreach ($this->_getParam('colors') as $key => $value) {
                $colors[$key] = $value;
            }
            $this->site->setColors($colors);
            $this->_flashMessenger->addMessage( $this->translate("Colors updated") );
            return $this->redirectTo( $this->view->url() );
        }
        $this->view->colors = $this->site->getColors();
    }

    public function cssAction()
    {
        if (!$this->_acl->isAllowed($this->userRole, null, 'admin')) {
            $this->_disallow();
        }

        $this->appendTitle( $this->translate('Custom CSS') );

        if ($this->getRequest()->isPost() && $this->_hasParam('css')) {
            $css = trim($this->_getParam('css'));
            $this->site->setCustomCss($css);
            $this->_flashMessenger->addMessage( $this->translate("CSS updated") );
            return $this->redirectTo( $this->view->url() );
        }

        $this->view->css = $this->site->getCustomCss();
    }

    public function styleAction()
    {
        $this->view->addScriptPath(LD_LIB_DIR . '/Ld/Ui/scripts');

        if ($this->_hasParam('id')) {
            $application = $this->getSite()->getInstance( $this->_getParam('id') );
        }
        if (isset($application)) {
            $colors = $application->getColors();
        } else {
            $colors = $this->getSite()->getColors();
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
        $this->getResponse()->setHeader('Content-Type', 'text/css', true);
        $this->getResponse()->setHeader('Cache-Control', "max-age=$expires, public", true);
        $this->getResponse()->setHeader('Pragma', "public", true);
        $this->getResponse()->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT', true);

        $this->disableLayout();
        $this->view->addScriptPath(LD_LIB_DIR . '/Ld/Ui/scripts');
        $this->renderScript('site-style.phtml');
    }

}

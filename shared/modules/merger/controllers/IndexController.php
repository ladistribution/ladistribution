<?php

/**
 * Index controller
 */
class Merger_IndexController extends Ld_Controller_Action
{

    public function init()
    {
        parent::init();

        $translator = $this->getTranslator();

        $this->view->addHelperPath(dirname(__FILE__) . '/../../slotter/views/helpers/', 'View_Helper');

        if ($this->_hasParam('username')) {
            $this->mergerUser = $this->site->getUser($this->_getParam('username'));
            if (empty($this->mergerUser)) {
                throw new Exception("Unknown user");
            }
            $this->view->layoutTitle = $this->mergerUser['fullname'];
        // } else if ($this->getSite()->isChild() && $owner = $this->site->getOwner()) {
        //     $this->view->layoutTitle = $owner['fullname'];
        }

        $this->view->canAdmin = $this->admin->userCan('admin');
    }

    public function indexAction()
    {
        if (Ld_Auth::isAuthenticated()) {
            $url = $this->view->url(array('action' => 'personal'), 'merger-feed');
            $this->_redirector->gotoUrl($url, array('prependBase' => false));
            return;
        }
        $this->_forward('public');
    }

    public function publicAction()
    {
        $translator = $this->getTranslator();
        $this->appendTitle($translator->translate('Public Feed'));
        $feeds = Ld_Feed_Merger::getFeeds('public');
        $entries = Ld_Feed_Merger::getEntries($feeds);
        $this->view->entries = $entries;
        $this->view->feedType = 'public';
        $this->render('index');
    }

    public function personalAction()
    {
        if (!Ld_Auth::isAuthenticated()) {
            $this->_disallow();
            return;
        }
        $translator = $this->getTranslator();
        $this->appendTitle($translator->translate('Personal Feed'));
        $feeds = Ld_Feed_Merger::getFeeds('personal');
        $hashes = $this->_hasParam('hashes') ? explode(";", $this->_getParam('hashes')) : array();
        $entries = Ld_Feed_Merger::getEntries($feeds, $hashes);
        $this->view->entries = $entries;
        $this->view->feedType = 'personal';
        $this->_render();
    }

    protected function _render()
    {
        $format = $this->_getParam('format', 'html');
        switch ($format) {
            case 'xml':
            case 'atom':
                $this->disableLayout();
                $this->getResponse()->setHeader('Content-Type', "text/html; charset=utf-8");
                $this->renderScript('index/index.atom');
                break;
            case 'json':
                $this->noRender();
                echo $this->view->json($this->view->entries);
                break;
            case 'html':
            default:
                $this->render('index');
        }
    }

    protected function _disallow()
    {
        $this->view->layoutTitle = null;
        if ($this->authenticated) {
             $this->_forward('disallow', 'auth', 'default');
         } else {
             $this->_forward('login', 'auth', 'default');
         }
    }

}

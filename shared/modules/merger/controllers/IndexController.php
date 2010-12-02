<?php

/**
 * Index controller
 */
class Merger_IndexController extends Ld_Controller_Action
{

    public function init()
    {
        parent::init();

        $this->view->addHelperPath(dirname(__FILE__) . '/../../slotter/views/helpers/', 'View_Helper');

        if ($this->_hasParam('username')) {
            $this->mergerUser = $this->site->getUser($this->_getParam('username'));
            if (empty($this->mergerUser)) {
                throw new Exception("Unknown user");
            }
            $this->view->layoutTitle = $this->mergerUser['fullname'];
        // } else if ($this->getSite()->isChild() && $owner = $this->site->getOwner()) {
        //     $this->view->layoutTitle = $owner['fullname'];
        } else {
            $this->view->layoutTitle = "News Feed";
        }

        $this->_setTitle('News Feed');
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
        $feeds = Ld_Feed_Merger::getFeeds('personal');
        $entries = Ld_Feed_Merger::getEntries($feeds);
        $this->view->entries = $entries;
        $this->view->feedType = 'personal';
        $this->render('index');
    }

    protected function _disallow()
    {
        if ($this->authenticated) {
             $this->_forward('disallow', 'auth', 'default');
         } else {
             $this->_forward('login', 'auth', 'default');
         }
    }

}

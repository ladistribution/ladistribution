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
    }

    public function indexAction()
    {
        if (Ld_Auth::isAuthenticated()) {
            $this->view->feedType = $feedType = $this->_getParam('feed', 'personal');
        } else {
            $this->view->feedType = $feedType = 'public';
        }

        // code should be elsewhere 
        $username = Ld_Auth::getUsername();
        $this->view->userRole = $this->admin->getUserRole($username);

        $this->_setTitle('News Feed');

        $feeds = Ld_Feed_Merger::getFeeds($feedType);
        $entries = Ld_Feed_Merger::getEntries($feeds);

        $this->view->entries = $entries;
    }

}

<?php

/**
 * Index controller
 */
class Merger_SettingsController extends Ld_Controller_Action
{

    /**
     * preDispatch
     */
    public function preDispatch()
    {
        parent::preDispatch();

        if (!$this->userCan('admin')) {
            $this->disallow();
        }

        $translator = $this->getTranslator();
        $this->appendTitle( $translator->translate('News Feed') );
        $this->appendTitle( $translator->translate('Settings') );
    }

    public function init()
    {
        parent::init();

        $this->view->addHelperPath(dirname(__FILE__) . '/../../slotter/views/helpers/', 'View_Helper');
    }

    public function indexAction()
    {
        if ($this->getRequest()->isPost()) {
            $public = $this->_getParam('public');
            $personal = $this->_getParam('personal');
            $merger = compact('public', 'personal');
            $this->site->setConfig('merger', $merger);
        }

        $types = array('application/rss+xml', 'application/atom+xml');
        $rels = array('feed', 'alternate', 'public-feed', 'personal-feed');

        $this->view->applications = array();
        foreach (Ld_Feed_Merger::getInstances() as $id => $instance) {
            $feeds = array();
            foreach ($instance->getLinks() as $link) {
                if (in_array($link['rel'], $rels) && in_array($link['type'], $types)) {
                    $feeds[] = $link;
                }
            }
            if (count($feeds) > 0) {
                $this->view->applications[$id] = $feeds;
            }
        }

        $this->view->config = $this->site->getConfig('merger');
    }

}

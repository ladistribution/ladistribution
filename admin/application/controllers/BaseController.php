<?php

require_once 'Zend/Controller/Action.php';

/**
 * Base controller
 */
class BaseController extends Zend_Controller_Action
{
    protected static $_accept = array(
        'application/json' => 'json'
    );
    
    public function init()
    {
        $registry = Zend_Registry::getInstance();
        
        $this->view->sites = $this->sites = $registry['sites'];
        
        $site = $this->getRequest()->getParam('site', 'default');
        
        if (empty($this->sites[$site])) {
            throw new Exception('Unknown site.');
        }
        
        $config = $this->sites[$site];
        $config['id'] = $site;
        
        if ($config['type'] == 'local') {
            $this->view->site = $this->site = new Ld_Site_Local($config);
        } else {
            $this->view->site = $this->site = new Ld_Site_Remote($config);
        }
        
        $this->_redirector = $this->_helper->getHelper('Redirector');
    }
    
    /**
     * preDispatch
     */
    public function preDispatch()
    {
        $registry = Zend_Registry::getInstance();
        
        $this->view->title = $title = empty($registry['config']['title']) ? 'App Slotter' : $registry['config']['title'];
        $this->view->headTitle($title, 'SET');
        
        $this->view->setHelperPath(APPLICATION . '/views/helpers/', 'View_Helper');
        
        $this->_handleFormat();
        
        if ($this->getRequest()->isPost()) {
            $this->_contentType = $_SERVER['CONTENT_TYPE'];
            if ($this->_contentType == 'application/json') {
                $params = Zend_Json::decode( $this->getRequest()->getRawBody() );
                $this->getRequest()->setParams($params);
            }
        }
    }
    
    public function postDispatch()
    {
        switch ($this->_format) {
            case 'json':
                $this->_helper->viewRenderer->setNoRender(true);
                $this->view->jsonRenderer($this->view->getVars(), true);
                break;
            default:
                break;
        }
    }
    
    protected function _handleFormat()
    {
        $this->_format = $this->getRequest()->getParam('format');
        if (empty($this->_format)) {
            $this->_format = 'xhtml';
        }
        
        if (isset($_SERVER['HTTP_ACCEPT'])) {
          $accept = $_SERVER['HTTP_ACCEPT'];
          if (isset(self::$_accept[$accept])) {
              $this->_format = self::$_accept[$accept];
          }
        }
        
        if ($this->_format != 'xhtml') {
          $viewRenderer = Zend_Controller_Action_HelperBroker::getExistingHelper('viewRenderer');
          $viewRenderer->setViewSuffix($this->_format . ".php");
          $this->_helper->layout->disableLayout();
        }
    }

}
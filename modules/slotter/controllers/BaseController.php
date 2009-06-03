<?php

require_once 'Zend/Controller/Action.php';

/**
 * Base controller
 */
class BaseController extends Ld_Controller_Action
{

    protected static $_accept = array(
        'application/json' => 'json'
    );

    public function init()
    {
        parent::init();

        $this->view->site = $this->site = $this->_registry['site'];

        $this->view->setHelperPath(dirname(__FILE__) . '/../views/helpers/', 'View_Helper');

        $this->view->baseUrl = $this->view->url(
            array('module' => 'slotter', 'controller' => 'index', 'action' => 'index'), 'default', true);

        $this->_setTitle('Slotter');
    }

    public function restrict()
    {
        $users = $this->site->getUsers();
        if (empty($users)) {
            return true;
        }

        if ($this->authenticated == false) {
            $this->_forward('index', 'auth', 'default');
            return true;
        }
    }

    /**
     * preDispatch
     */
    public function preDispatch()
    {
        $this->restrict();

        if ($this->getRequest()->isPost()) {
            $this->_contentType = $_SERVER['CONTENT_TYPE'];
            // TEMP: handle a particular context when interacting with requests in oAuth library
            if ($this->_contentType == 'application/octet-stream') {
                $this->_contentType == 'application/json';
            }
            if ($this->_contentType == 'application/json') {
                $params = Zend_Json::decode( $this->getRequest()->getRawBody() );
                $this->getRequest()->setParams($params);
                // TEMP: useful too with oAuth library
                if (!$this->_hasParam('format')) {
                    $this->_setParam('format', 'json');
                }
            }
        }
        
        $this->_handleFormat();
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
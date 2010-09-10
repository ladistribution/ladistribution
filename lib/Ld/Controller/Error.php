<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Controller
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Controller_Error extends Zend_Controller_Action
{

    /**
     * Error action.
     */
    public function errorAction()
    {
        $this->view->host = $_SERVER['HTTP_HOST'];

        $this->view->details = '';

        $errors = $this->_getParam('error_handler');

        $e = $errors->exception;

        $log_message = get_class($e) . ': ' . $e->getMessage() . ' (file ' .  $e->getFile() . ') (line ' . $e->getLine() . ')';

        if (defined('LD_DEBUG') && constant('LD_DEBUG')) {
            $this->view->details .= '<li>' . $log_message . '<pre>' . $e->getTraceAsString() . '</pre>' . '</li>';
        }

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                $this->getResponse()->setRawHeader('HTTP/1.1 404 Not Found');
                $this->view->status = 'Not found';
                $this->view->message = 'The requested document was not found on this server.';
                break;
            default:
                $this->view->status = 'An error occured';
                $this->view->message = $e->getMessage();
                error_log($log_message);
        }

        $this->_helper->viewRenderer->setNoRender(true);

        $this->getResponse()
            ->appendBody( sprintf('<h2>%s</h2>', $this->view->status)  )
            ->appendBody( sprintf('<p>%s</p>',   $this->view->message) )
            ->appendBody( sprintf('<ul>%s</ul>', $this->view->details) );
    }

}

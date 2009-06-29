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

/**
 * @see Zend_Controller_Action
 */
require_once 'Zend/Controller/Action.php';

class Ld_Controller_Error extends Zend_Controller_Action
{

    /**
     * Error action.
     */
    public function errorAction()
    {
        $this->view->host = $_SERVER['HTTP_HOST'];

        $this->view->details = '';

        foreach ($this->getResponse()->getException() as $e) {

            $log_message = get_class($e) . ': error ' . $e->getCode() . ': ' . $e->getMessage() .
                ' (file ' .  $e->getFile() . ') (line ' . $e->getLine() . ')';

            if (constant('LD_DEBUG')) {
                $this->view->details .= '<li>' . $log_message . '<pre>' . $e->getTraceAsString() . '</pre>' . '</li>';
            } else {
                error_log($log_message);
            }

            switch ($e->getCode()) {
                case '404':
                    $this->view->status = 'Not found';
                    $this->view->message = 'The requested document was not found on this server.';
                    break;
                default:
                    $this->view->status = 'An error occured';
                    $this->view->message = $e->getMessage();
            }
        }
        
        $this->_helper->viewRenderer->setNoRender(true);
        
        $this->getResponse()
            ->appendBody( sprintf('<h2>%s</h2>', $this->view->status)  )
            ->appendBody( sprintf('<p>%s</p>',   $this->view->message) )
            ->appendBody( sprintf('<ul>%s</ul>', $this->view->details) );
    }

}

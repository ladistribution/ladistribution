<?php

require_once 'Zend/Controller/Action.php';

/**
 * Error controller
 */
class ErrorController extends Zend_Controller_Action
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

            if (DEBUG) {
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
                    $this->view->message = 'You can contact the server administrator about this problem.';
            }
        }
    }
}

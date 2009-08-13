<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Controller
 * @subpackage Ld_Controller_Action_Helper
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

/**
 * @see Zend_Controller_Action_Helper_Abstract
 */
require_once 'Zend/Controller/Action/Helper/Abstract.php';

class Ld_Controller_Action_Helper_Abstract extends Zend_Controller_Action_Helper_Abstract
{

    protected function _getParam($paramName, $default = null)
    {
        $value = $this->getRequest()->getParam($paramName);
        if ((null == $value) && (null !== $default)) {
            $value = $default;
        }

        return $value;
    }

    protected function _setParam($paramName, $value)
    {
        $this->getRequest()->setParam($paramName, $value);

        return $this;
    }

    protected function _hasParam($paramName)
    {
        return null !== $this->getRequest()->getParam($paramName);
    }

}

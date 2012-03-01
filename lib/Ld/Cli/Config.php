<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Cli
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2012 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Cli_Config extends Ld_Cli
{

    protected function _getArgs()
    {
        $args = parent::_getArgs();
        array_shift($args);
        return $this->_args = $args;
    }

    public function get()
    {
        $site = $this->getSite();
        $keyName = isset($this->_args[1]) ? $this->_args[1] : $this->_prompt('Key Name');
        $value = $site->getConfig($keyName);
        if ($value === null) {
            $this->_write('NULL');
        } else {
            $this->_write( (string)$value );
        }
    }

    public function set()
    {
        $site = $this->getSite();
        $keyName = isset($this->_args[1]) ? $this->_args[1] : $this->_prompt('Key Name');
        $keyValue = isset($this->_args[2]) ? $this->_args[2] : $this->_prompt('Key Value');
        $site->setConfig($keyName, $keyValue);
        $this->_write('Ok.');
    }

    public function delete()
    {
        $site = $this->getSite();
        $keyName = isset($this->_args[1]) ? $this->_args[1] : $this->_prompt('Key Name');
        $value = $site->deleteConfig($keyName);
        $this->_write('Ok.');
    }

}

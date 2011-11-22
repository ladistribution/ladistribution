<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Model
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2011 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Model_Config extends Ld_Model_Collection
{

    protected $_collectionId = 'config';

    protected function _getRessource($name)
    {
        if (defined('LD_MONGO_BACKEND') && constant('LD_MONGO_BACKEND') && class_exists('Mongo')) {
            $storage = $this->getBackend()->getCollection()->findOne(array('key' => $name));
            if (isset($storage['data'])) {
                $data = $storage['data'];
            }
        }
        if (empty($data)) {
            $data = Ld_Files::getJson($this->getSite()->getDirectory('dist') . '/' . $name . '.json');
            if (defined('LD_MONGO_BACKEND') && constant('LD_MONGO_BACKEND') && class_exists('Mongo')) {
                $this->_setRessource($name, $data);
            }
        }
        return $data;
    }

    protected function _setRessource($name, $data)
    {
        if (defined('LD_MONGO_BACKEND') && constant('LD_MONGO_BACKEND') && class_exists('Mongo')) {
            $this->getBackend()->getCollection()->update(array('key' => $name), array('key' => $name, 'data' => $data), array('upsert' => true));
        } else {
            Ld_Files::putJson($this->getSite()->getDirectory('dist') . '/' . $name . '.json', $data);
        }
    }

    public function getConfig() { return $this->_getRessource('config'); }

    public function setConfig($config) { $this->_setRessource('config', $config); }

    public function getColors() { return $this->_getRessource('colors'); }

    public function setColors($colors) { $this->_setRessource('colors', $colors); }

    public function getLocales() { return $this->_getRessource('locales'); }

    public function setLocales($locales) { $this->_setRessource('locales', $locales); }

}

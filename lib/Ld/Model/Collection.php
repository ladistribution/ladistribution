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

class Ld_Model_Collection
{

    protected $_collectionId = null;

    protected $_site = null;

    protected $_backend = null;

    public function __construct($collectionId = null)
    {
        if (isset($collectionId)) {
            $this->_collectionId = $collectionId;
        }
    }

    public function getCollectionId()
    {
        return $this->_collectionId;
    }

    public function getBackend()
    {
        if (empty($this->_backend)) {
            if (defined('LD_MONGO_BACKEND') && constant('LD_MONGO_BACKEND') && class_exists('Mongo')) {
                $this->_backend = new Ld_Model_Backend_Mongo($this->_collectionId);
                // Subsite no supported
            } else {
                $this->_backend = new Ld_Model_Backend_Json($this->_collectionId);
                $this->_backend->setSite( $this->getSite() );
            }
        }
        return $this->_backend;
    }

    public function setBackend($backend)
    {
        return $this->_backend = $backend;
    }

    public function getSite()
    {
        return isset($this->_site) ? $this->_site : $this->_site = Zend_Registry::get('site');
    }

    public function setSite($site)
    {
        $this->_site = $site;
    }

    public function getAll()
    {
        $items = $this->getBackend()->getAll();
        if (empty($items) && defined('LD_MONGO_BACKEND') && constant('LD_MONGO_BACKEND') && class_exists('Mongo')) {
            $items = Ld_Files::getJson($this->getSite()->getDirectory('dist') . '/' . $this->_collectionId . '.json');
            foreach ($items as $id => $item) {
                $item['json_id'] = $id;
                unset($item['id']);
                $this->getBackend()->create($item);
            }
        }
        return $items;
    }

    public function getOneByKey($key, $value)
    {
        $results = $this->searchByKey($key, $value);
        if (count($results) > 0) {
            return array_shift($results);
        }
    }

    public function searchByKey($key, $value)
    {
        return $this->getBackend()->searchByKey($key, $value);
    }

    public function create($params)
    {
        return $this->getBackend()->create($params);
    }

    public function read($id)
    {
        return $this->getBackend()->read($id);
    }

    public function update($id, $params, $merge = true)
    {
        if ($merge) {
            $params = array_merge($this->read($id), $params);
        }
        return $this->getBackend()->update($id, $params);
    }

    public function delete($id)
    {
        return $this->getBackend()->delete($id);
    }

    public function get($id)
    {
        return $this->read($id);
    }

    public function add($params)
    {
        return $this->create($params);
    }

}

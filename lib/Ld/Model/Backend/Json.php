<?php

class Ld_Model_Backend_Json
{

    protected $_site = null;

    public function __construct($id)
    {
        $this->_id = $id;
    }

    public function getSite()
    {
        return isset($this->_site) ? $this->_site : $this->_site = Zend_Registry::get('site');
    }

    public function setSite($site)
    {
        $this->_site = $site;
    }

    protected function getFileName()
    {
        if ($site = $this->getSite()) {
            $dir = $site->getDirectory('dist');
        } else {
            $dir = Zend_Registry::get('dir') . '/dist';
        }
        return $dir . '/' . $this->_id . '.json';
    }

    public function getAll()
    {
        $items = Ld_Files::getJson($this->getFileName());
        if (empty($items)) {
            $items = array();
        }
        return $items;
    }

    public function searchByKey($key, $value)
    {
        $results = array();
        $all = $this->getAll();
        foreach ($all as $id => $item) {
            if (isset($item[$key]) && $item[$key] == $value) {
                $results[$id] = $item;
            }
        }
        return $results;
    }

    protected function updateAll($items)
    {
        Ld_Files::putJson($this->getFileName(), $items);
    }

    public function create($params = array())
    {
        $items = $this->getAll();
        $id = Ld_Utils::getUniqId();
        $items[$id] = $params;
        $this->updateAll($items);
        $params['id'] = $id;
        return $params;
    }

    public function read($id)
    {
        $items = $this->getAll();
        if (isset($items[$id])) {
            return $items[$id];
        }
    }

    public function update($id, $params = array())
    {
        $items = $this->getAll();
        if (isset($items[$id])) {
            foreach ($params as $key => $value) {
                $items[$id][$key] = $value;
            }
            $this->updateAll($items);
        }
    }

    public function delete($id)
    {
        $items = $this->getAll();
        if (isset($items[$id])) {
            unset($items[$id]);
            $this->updateAll($items);
        }
    }

}

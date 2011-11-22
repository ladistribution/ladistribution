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

class Ld_Model_Instances extends Ld_Model_Collection
{

    protected $_collectionId = 'instances';

    protected $_instances = array();

    public function getInstances($params = array())
    {
        if (empty($this->_instances)) {
            $this->_instances = $this->getAll();
            uasort($this->_instances, array("Ld_Utils", "sortByOrder"));
        }

        return $this->_instances;
    }

    public function getInstancesBy($filterKey, $filterValue)
    {
        $instances = $this->getInstances();

        // Filter by type
        if (isset($filterValue)) {
            foreach ($instances as $id => $instance) {
                if (empty($instance[$filterKey]) || $filterValue != $instance[$filterKey]) {
                    unset($instances[$id]);
                }
            }
        }

        return $instances;
    }

    public function resetCache()
    {
        $this->_instances = array();
    }

    public function addInstance($params)
    {
        $result = $this->add($params);
        $this->resetCache();
        return $result;
    }

    public function updateInstance($id, $params)
    {
        $result = $this->update($id, $params);
        $this->resetCache();
        return $result;
    }

    public function deleteInstance($id)
    {
        $result = $this->delete($id);
        $this->resetCache();
        return $result;
    }

    public function getOneInstanceBy($filterKey, $filterValue)
    {
        $instances = $this->getInstancesBy($filterKey, $filterValue);
        if (count($instances) > 0) {
            $keys = array_keys($instances);
            $id = $keys[0];
            $instances[$id]['id'] = $id;
            return $instances[$id];
        }
    }

}

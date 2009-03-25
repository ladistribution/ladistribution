<?php

abstract class Ld_Site_Abstract
{

    abstract public function getInstances();

    abstract public function getInstance($id);

    abstract public function createInstance($packageId, $preferences = array());

    abstract public function deleteInstance($instance);

    abstract public function getUsers();

}

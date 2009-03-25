<?php

abstract class Ld_Repository_Abstract
{

    public $id = null;

    public $name = null;

    public $type = null;

    public $types = array(
        'libraries'  => array('shared', 'lib', 'css', 'js'),
        'extensions' => array('theme', 'plugin')
    );

}

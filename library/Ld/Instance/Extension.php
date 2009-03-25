<?php

class Ld_Instance_Extension extends Ld_Instance
{
    
    public function setPath($path)
    {
        $this->absolutePath = LD_ROOT . '/' . $path;
        $this->instanceJson = $this->absolutePath . '/dist/instance.json';
    }
}

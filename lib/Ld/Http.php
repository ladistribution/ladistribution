<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Http
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Http
{

    public static function context()
    {
        $context = stream_context_create(array(
            'http' => array(
                'method'  => 'GET',
                'header'  => "User-Agent: La Distribution Http Library\r\n"
            )
        ));
        return $context;
    }

    public static function get($url)
    {
        $contents = '';
        $remote = fopen($url, "r", false, self::context());
        while ( ($buffer = fread($remote, 8192)) != '' ) {
            $contents .= $buffer;
        }
        fclose($remote);
        return $contents;
    }

    public static function download($url, $filename)
    {
        $local = fopen($filename, "w+");
        $remote = fopen($url, "r", false, self::context());
        while ( ($buffer = fread($remote, 8192)) != '' ) {
            fwrite($local, $buffer);
        }
        fclose($local);
        fclose($remote);
    }

    public static function upload()
    {
        $dir = LD_TMP_DIR . '/uploads';
        Ld_Files::createDirIfNotExists($dir);

        $adapter = new Zend_File_Transfer_Adapter_Http();
        $adapter->setDestination($dir);
        $result = $adapter->receive();

        return $adapter->getFileName();
    }

}

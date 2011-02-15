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
        $httpParams = array(
            'method'  => 'GET',
            'user_agent' => 'La Distribution HTTP Library',
            'timeout' => 5
        );
        if (method_exists('Zend_Registry', 'isRegistered') && Zend_Registry::isRegistered('site')) {
            $siteUrl = Zend_Registry::get('site')->getUrl();
            $httpParams['header'] = "Referer: $siteUrl\r\n";
        }
        $context = stream_context_create(array('http' => $httpParams));
        return $context;
    }

    public static function get($url)
    {
        // Ld_Files::log('Ld_Http::get', "$url");
        $contents = '';
        $remote = fopen($url, "r", false, self::context());
        if (!$remote) {
            return false;
        }
        while ( ($buffer = fread($remote, 8192)) != '' ) {
            $contents .= $buffer;
        }
        fclose($remote);
        return $contents;
    }

    public static function download($url, $filename)
    {
        // Ld_Files::log('Ld_Http::download', "$url");
        $local = fopen($filename, "w+");
        $remote = fopen($url, "r", false, self::context());
        if (!$local || !$remote) {
            return false;
        }
        while ( ($buffer = fread($remote, 8192)) != '' ) {
            fwrite($local, $buffer);
        }
        fclose($local);
        fclose($remote);
        return true;
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

<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Http
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2011 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Http
{

    public static $userAgent = 'La Distribution HTTP Library';

    public static function context()
    {
        $httpParams = array(
            'method'  => 'GET',
            'user_agent' => self::$userAgent,
            'timeout' => 5
        );
        if (method_exists('Zend_Registry', 'isRegistered') && Zend_Registry::isRegistered('site')) {
            $siteUrl = Zend_Registry::get('site')->getUrl();
            $httpParams['header'] = "Referer: $siteUrl\r\n";
        }
        $context = stream_context_create(array('http' => $httpParams));
        return $context;
    }

    public static function curl_context($ch)
    {
        curl_setopt($ch, CURLOPT_USERAGENT, self::$userAgent . ' (curl)');
        if (method_exists('Zend_Registry', 'isRegistered') && Zend_Registry::isRegistered('site')) {
            $referer = Zend_Registry::get('site')->getUrl();
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    }

    public static function get($url)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            self::curl_context($ch);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $content = curl_exec($ch);
            curl_close($ch);
        } else {
            $content = file_get_contents($url, false, self::context());
        }
        return $content;
    }

    public static function download($url, $filename)
    {
        $local = fopen($filename, "w+");
        if (!$local) {
            return false;
        }
        if (function_exists('curl_init')) {
            $ch = curl_init();
            self::curl_context($ch);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FILE, $local);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_exec($ch);
            curl_close($ch);
        } else {
            $remote = fopen($url, "r", false, self::context());
            if (!$remote) {
                return false;
            }
            while ( ($buffer = fread($remote, 8192)) != '' ) {
                fwrite($local, $buffer);
            }
            fclose($remote);
        }
        fclose($local);
        return true;
    }

    public static function upload()
    {
        $dir = LD_TMP_DIR . '/uploads';
        Ld_Files::createDirIfNotExists($dir);

        $adapter = new Zend_File_Transfer_Adapter_Http();
        $adapter->setDestination($dir);

        if (!$adapter->receive()) {
            foreach ($adapter->getMessages() as $code => $message) {
                throw new Exception($message);
            }
        }

        return $adapter->getFileName();
    }

    public static function jsonRequest($url, $method = 'GET', $params = array())
    {
        $httpClient = new Zend_Http_Client($url);
        $httpClient->setConfig(array('useragent' => self::$userAgent));
        if (!empty($params)) {
            if ($method == 'GET') {
                $httpClient->setParameterGet($params);
            } elseif ($method == 'POST') {
                $httpClient->setParameterPost($params);
            }
        }
        $response = $httpClient->request($method);
        $body = $response->getBody();
        $result = Zend_Json::decode($body);
        if (isset($result['error'])) {
            $error = $result['error'];
            $error_description = $result['error_description'];
            throw new Exception("$error_description ($error)");
        }
        return $result;
    }

}

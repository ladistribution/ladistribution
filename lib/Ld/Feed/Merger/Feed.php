<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Feed_Merger
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Feed_Merger_Feed
{

    protected $_url = null;

    protected $_application = null;

    protected $_type = null;

    protected $_httpClient = null;

    protected $_feedReader = null;

    public function __construct($url, $application = null, $type = 'public')
    {
        $this->_url = $url;
        $this->_application = $application;
        $this->_type = $type;
    }

    public function getUrl()
    {
        return $this->_url;
    }

    public function getApplication()
    {
        return $this->_application;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function getHttpClient()
    {
        if (isset($this->_httpClient)) {
            return $this->_httpClient;
        }

        $httpClient = new Zend_Http_Client();
        $httpClient->setConfig(array('maxredirects' => 5, 'timeout' => 10, 'useragent' => 'La Distribution Feed Merger'));

        return $this->_httpClient = $httpClient;
    }

    public function getFeedReader()
    {
        if (isset($this->_feedReader)) {
            return $this->_feedReader;
        }

        $httpClient = $this->getHttpClient();
        if ($this->getType() == 'personal' && Ld_Auth::isAuthenticated()) {
            $httpClient->setCookie('ld-auth', $_COOKIE['ld-auth']);
        } else {
            $httpClient->setCookie('ld-auth', null);
        }

        Zend_Feed_Reader::setHttpClient($httpClient);

        if (!Zend_Feed_Reader::isRegistered('Ld')) {
            Zend_Feed_Reader::addPrefixPath('Ld_Feed_Reader_Extension', LD_LIB_DIR . '/Ld/Feed/Reader/Extension');
            Zend_Feed_Reader::registerExtension('Ld');
            Zend_Feed_Reader::registerExtension('Media');
        }

        if ($cache = $this->getCache()) {
            Zend_Feed_Reader::setCache($cache);
            // Zend_Feed_Reader::useHttpConditionalGet();
        }

        $feedReader = Zend_Feed_Reader::import($this->_url);

        return $this->_feedReader = $feedReader;
    }

    public function getTitle()
    {
        return $this->getFeedReader()->getTitle();
    }

    public function getEntries()
    {
        if ($this->getType() == 'personal' && Ld_Auth::isAuthenticated()) {
            $this->_url .= '#' . Ld_Auth::getUsername();
        }

        $cache = $this->getCache();
        if ($cache) {
            $cacheKey = 'Ld_Feed_Merger_Feed_' . md5($this->_url);
            if ($cache->test($cacheKey)) {
                return $cache->load($cacheKey);
            }
        }

        $instanceId = $this->getApplication()->getId();;
        $packageId = $this->getApplication()->getPackageId();

        $entries = array();
        foreach ($this->getFeedReader() as $entry) {
            $mergerEntry = new Ld_Feed_Merger_Entry($entry, $packageId, $instanceId);
            $entries[] = $mergerEntry->toArray();
        }

        if ($cache) {
            $lifeTime = 90 + ( rand(0, 100) * 90 / 100 );
            $cache->save($entries, $cacheKey, array(), $lifeTime);
        }

        return $entries;
    }

    // Utils

    public static function getCache()
    {
        if (Zend_Registry::isRegistered('cache')) { return Zend_Registry::get('cache'); }
    }

}

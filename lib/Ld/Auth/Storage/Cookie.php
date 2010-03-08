<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Auth
 * @subpackage Ld_Auth_Storage
 * @author     Matthieu Huguet (http://bigornot.blogspot.com/)
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Auth_Storage_Cookie implements Zend_Auth_Storage_Interface
{

    /* Instance of Ld_Cookie  */

    protected $_cookieManager = null;

    /* Cookie configuration (see setcookie() for details) */

    protected $_cookieName = 'auth';

    protected $_cookieExpire = 0;

    protected $_cookiePath = '/';

    protected $_cookieDomain = '';

    protected $_cookieSecure = false;

    protected $_cookieHttpOnly = false;

    /* Local "cache" to store cookie value, in order to not waste
       time in cryptographic functions of cookieManager->getCookieValue() */

    protected $_cache = null;

    /**
     * Constructor
     *
     * Available configuration options are :
     * cookieName, cookieExpire, cookiePath, cookieDomain,  cookieSecure, cookieHttpOnly
     * (see native setcookie() documentation for more details)
     *
     * @param Ld_Cookie $cookieManager the secure cookie manager
     * @param array|Zend_Config $config configuration
     */
    public function __construct($cookieManager, $config = array())
    {
        $this->_cookieManager = $cookieManager;

        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }

        $this->setOptions($config);
    }
    
    public function setOptions($options)
    {
        if (is_array($options)) {
            $keys = array('cookieName', 'cookieExpire', 'cookiePath',
                'cookieDomain', 'cookieSecure', 'cookieHttpOnly');
            foreach ($keys as $key) {
                if (array_key_exists($key, $options)) {
                    $property = "_$key";
                    $this->$property = $options[$key];
                }
            }
        }
    }

    /**
     * Defined by Zend_Auth_Storage_Interface
     *
     * @return boolean
     */
    public function isEmpty()
    {
        if ($this->_cache) {
            return (false);
        } elseif ($content = $this->_cookieManager->getCookieValue($this->_cookieName)) {
            $this->_cache = unserialize($content);
            return (false);
        } else {
            return (true);
        }
    }

    /**
     * Defined by Zend_Auth_Storage_Interface
     *
     * @return mixed
     */
    public function read()
    {
        if ($this->_cache) {
            return ($this->_cache);
        } elseif ($this->_cookieManager->cookieExists($this->_cookieName)) {
            if ($content = $this->_cookieManager->getCookieValue($this->_cookieName)) {
                $this->_cache = unserialize($content);
                return ($this->_cache);
            }
        } else {
            return (null);
        }
    }

    /**
     * Defined by Zend_Auth_Storage_Interface
     *
     * @param  mixed $contents
     * @return void
     */
    public function write($contents)
    {
        $this->_cache = $contents;
        $serializedContent = serialize($contents);
        $userIdentifier = substr(md5($serializedContent), 0, 10);
        $this->_cookieManager->setCookie(
            $this->_cookieName,
            $serializedContent,
            $userIdentifier,
            $this->_cookieExpire,
            $this->_cookiePath,
            $this->_cookieDomain,
            $this->_cookieSecure,
            $this->_cookieHttpOnly
        );
    }

    /**
     * Defined by Zend_Auth_Storage_Interface
     *
     * @return void
     */
    public function clear()
    {
        $this->_cache = null;
        $this->_cookieManager->deleteCookie(
            $this->_cookieName,
            $this->_cookiePath,
            $this->_cookieDomain,
            $this->_cookieSecure,
            $this->_cookieHttpOnly
        );
    }

}

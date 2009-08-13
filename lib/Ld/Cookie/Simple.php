<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Cookie
 * @author     François Hodierne (http://h6e.net)
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Cookie_Simple
{

    public function deleteCookie($name, $path = '/', $domain = '', $secure = false, $httponly = null)
    {
        // delete cookie only once
        if (isset($this->deleted)) return; else $this->deleted = true;
        /* 1980-01-01 */
        $expire = 315554400;
        setcookie($name, '', $expire, $path, $domain, $secure, $httponly);
    }

    public function getCookieValue($cookiename, $deleteIfInvalid = true)
    {
        if ( get_magic_quotes_gpc() && empty($this->stripslashed) ) {
            $_COOKIE = array_map(array('self', '_stripslashesDeep'), $_COOKIE);
            $this->stripslashed = true;
        }
        if ($this->cookieExists($cookiename)) {
            return $_COOKIE[$cookiename];
        }
        return (false);
    }

    protected static function _stripslashesDeep($value)
    {
        $value = is_array($value) ? array_map(array('self', '_stripslashesDeep'), $value) : stripslashes($value);
        return $value;
    }

    public function setCookie($cookiename, $value, $username, $expire = 0, $path = '', $domain = '', $secure = false, $httponly = null)
    {
        /* httponly option is only available for PHP version >= 5.2 */
        if ($httponly === null) {
            setcookie($cookiename, $value, $expire, $path, $domain, $secure);
        } else {
            setcookie($cookiename, $value, $expire, $path, $domain, $secure, $httponly);
        }
    }

    public function cookieExists($cookiename)
    {
        return (isset($_COOKIE[$cookiename]));
    }

}

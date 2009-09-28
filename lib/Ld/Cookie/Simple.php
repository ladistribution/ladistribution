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
        if ($this->cookieExists($cookiename)) {
            return stripslashes($_COOKIE[$cookiename]);
        }
        return (false);
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

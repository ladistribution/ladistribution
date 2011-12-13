<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Utils
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2011 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Utils
{

    public static function sortByOrder($a, $b)
    {
        if (isset($a['order']) && isset($b['order'])) {
            return ($a['order'] < $b['order']) ? -1 : 1;
        } else if (isset($a['order'])) {
            return -1;
        }
        return 1;
    }

    public static function getDbConnection($db, $type = 'zend')
    {
        if (strpos($db['host'], ':')) {
            list($db['host'], $db['port']) = explode(':', $db['host']);
        }
        if (!isset($db['port'])) {
            $db['port'] = null;
        }
        switch ($type) {
            case 'php':
                $con = mysqli_init();
                $con->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2.5);
                if (defined('LD_DEBUG') && constant('LD_DEBUG')) {
                    $result = $con->real_connect($db['host'], $db['user'], $db['password'], $db['name'], $db['port']);
                } else {
                    $result = @$con->real_connect($db['host'], $db['user'], $db['password'], $db['name'], $db['port']);
                }
                if (!$result) {
                    return null;
                }
                break;
             case 'zend':
             default:
                $params = array(
                    'host' => $db['host'],
                    'username' => $db['user'],
                    'password' => $db['password'],
                    'dbname' => $db['name'],
                    'port' => $db['port']
                );
                $con = Zend_Db::factory('Mysqli', $params);
                break;
        }
        return $con;
    }

    public static function getUniqId()
    {
        return uniqid();
    }

    public static function getCurrentScheme()
    {
        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)) {
            return 'https';
        } else if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            return 'https';
        }
        return 'http';
    }

    public static function getCurrentUrl($ignoreParams = array())
    {
      $protocol = self::getCurrentScheme() . '://';

      $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
      $parts = parse_url($currentUrl);

      $query = '';
      if (!empty($parts['query'])) {
        // ignore some params
        $params = explode('&', $parts['query']);
        $retained_params = array();
        foreach ($params as $param) {
            $explode = explode('=', $param);
            if (count($explode) == 2) {
                list($key, $value) = $explode;
                if (in_array($key, $ignoreParams)) {
                    continue;
                }
            }
            $retained_params[] = $param;
        }
        if (!empty($retained_params)) {
          $query = '?'. implode('&', $retained_params);
        }
      }

      // use port if non default
      $port =
        isset($parts['port']) &&
        (($protocol === 'http://' && $parts['port'] !== 80) ||
         ($protocol === 'https://' && $parts['port'] !== 443))
        ? ':' . $parts['port'] : '';

      // rebuild
      return $protocol . $parts['host'] . $port . $parts['path'] . $query;
    }

}

<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Utils
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009 h6e / François Hodierne (http://h6e.net/)
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
        switch ($type) {
            case 'php':
                $con = mysqli_init();
                $con->options(MYSQLI_OPT_CONNECT_TIMEOUT, 2.5);
                $result = $con->real_connect($db['host'], $db['user'], $db['password'], $db['name'], isset($db['port']) ? $db['port'] : null);
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
                    'port' => isset($db['port']) ? $db['port'] : null
                );
                $con = Zend_Db::factory('Mysqli', $params);
                break;
        }
        return $con;
    }

    public function getUniqId()
    {
        return uniqid();
    }

}

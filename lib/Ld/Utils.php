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

}

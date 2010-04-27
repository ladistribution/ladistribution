<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Translate
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Translate
{

    protected static $_translator = null;

    public static function getTranslator()
    {
        if (empty(self::$_translator)) {
            if (Zend_Registry::isRegistered('Zend_Translate')) {
                self::$_translator = Zend_Registry::get('Zend_Translate');
            }
        }
        return self::$_translator;
    }

    public function translate($string)
    {
        $translator = self::getTranslator();
        if ($translator) {
            return $translator->translate($string);
        }
        return $string;
    }

}

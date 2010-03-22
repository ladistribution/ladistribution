<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Plugin
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Plugin
{

    protected static $current_filter = array();

    protected static $ld_filter = array();

    protected static $merged_filters = array();

    protected static $actions = array();

    static function add($tag, $function_to_add, $priority = 10, $accepted_args = 1)
    {
        $idx = self::build_unique_id($tag, $function_to_add, $priority);
        self::$ld_filter[$tag][$priority][$idx] = array('function' => $function_to_add, 'accepted_args' => $accepted_args);
        unset( self::$merged_filters[ $tag ] );
        return true;
    }

    static function addAction($tag, $function_to_add, $priority = 10, $accepted_args = 1)
    {
        self::add($tag, $function_to_add, $priority, $accepted_args);
    }

    static function addFilter($tag, $function_to_add, $priority = 10, $accepted_args = 1)
    {
        self::add($tag, $function_to_add, $priority, $accepted_args);
    }

    static function applyFilters($tag, $value)
    {
        return call_user_func_array(array('self', 'apply'), func_get_args());
    }

    static function apply($tag, $value)
    {
        $args = array();
        self::$current_filter[] = $tag;

        if ( !isset(self::$ld_filter[$tag]) ) {
            array_pop(self::$current_filter);
            return $value;
        }

        // Sort
        if ( !isset( self::$merged_filters[ $tag ] ) ) {
            ksort(self::$ld_filter[$tag]);
            self::$merged_filters[ $tag ] = true;
        }

        reset( self::$ld_filter[ $tag ] );

        if ( empty($args) ) {
            $args = func_get_args();
        }

        do {
            foreach( (array) current(self::$ld_filter[$tag]) as $the_ )
                if ( !is_null($the_['function']) ) {
                    $args[1] = $value;
                    $value = call_user_func_array($the_['function'], array_slice($args, 1, (int) $the_['accepted_args']));
                }
        } while ( next(self::$ld_filter[$tag]) !== false );

        array_pop( self::$current_filter );

        return $value;
    }

    static function build_unique_id($tag, $function, $priority)
    {
        static $filter_id_count = 0;
        if ( is_string($function) ) {
            return $function;
        } else if (is_object($function[0]) ) {
            // Object Class Calling
            if ( function_exists('spl_object_hash') ) {
                return spl_object_hash($function[0]) . $function[1];
            } else {
                $obj_idx = get_class($function[0]).$function[1];
                if ( !isset($function[0]->wp_filter_id) ) {
                    if ( false === $priority )
                        return false;
                    $obj_idx .= isset(self::$ld_filter[$tag][$priority]) ? count((array)self::$ld_filter[$tag][$priority]) : $filter_id_count;
                    $function[0]->wp_filter_id = $filter_id_count;
                    ++$filter_id_count;
                } else {
                    $obj_idx .= $function[0]->wp_filter_id;
                }
                return $obj_idx;
            }
        } else if ( is_string($function[0]) ) {
            // Static Calling
            return $function[0].$function[1];
        }
    }

    function doAction($tag, $arg = '')
    {
        if ( is_array(self::$actions) )
            self::$actions[] = $tag;
        else
            self::$actions = array($tag);

        self::$current_filter[] = $tag;

        if ( !isset(self::$ld_filter[$tag]) ) {
            array_pop(self::$current_filter);
            return;
        }

        $args = array();
        if ( is_array($arg) && 1 == count($arg) && is_object($arg[0]) ) // array(&$this)
            $args[] =& $arg[0];
        else
            $args[] = $arg;
        for ( $a = 2; $a < func_num_args(); $a++ )
            $args[] = func_get_arg($a);

        // Sort
        if ( !isset( self::$merged_filters[ $tag ] ) ) {
            ksort(self::$ld_filter[$tag]);
            self::$merged_filters[ $tag ] = true;
        }

        reset( self::$ld_filter[ $tag ] );

        do {
            foreach ( (array) current(self::$ld_filter[$tag]) as $the_ )
                if ( !is_null($the_['function']) )
                    call_user_func_array($the_['function'], array_slice($args, 0, (int) $the_['accepted_args']));

        } while ( next(self::$ld_filter[$tag]) !== false );

        array_pop(self::$current_filter);
    }

}

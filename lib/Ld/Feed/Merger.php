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

class Ld_Feed_Merger
{

    public static function getSites()
    {
        $mainsite = self::getSite();
        $sites = array($mainsite);
        foreach ($mainsite->getSites() as $id => $config) {
            $subsite = new Ld_Site_Child($config);
            $subsite->setParentSite($mainsite);
            $sites[] = $subsite;
        }
        return $sites;
    }

    public static function getInstances()
    {
        $instances = array();
        foreach (self::getSites() as $site) {
            foreach ($site->getInstances('application') as $id => $infos) {
                $instance = $site->getInstance($id);
                if ($instance) {
                    $instances[$id] = $instance;
                }
            }
        }
        return $instances;
    }

    public static function getFeeds($feedType = 'public')
    {
        $config = self::getSite()->getConfig('merger');
        $config = $config[$feedType];

        $types = array('application/rss+xml', 'application/atom+xml');

        $rels = array('feed', 'alternate');
        $rels[] = $feedType == 'public' ? 'public-feed' : 'personal-feed';

        $feeds = array();
        foreach (self::getInstances() as $id => $instance) {
            $package = $instance->getPackageId();
            foreach ($instance->getLinks() as $link) {
                if (in_array($link['rel'], $rels) && in_array($link['type'], $types)) {
                    $feedId = $link['id'];
                    if (!isset($config[$id][$feedId]) || $config[$id][$feedId] == 1) {
                        // $parseType = $feedType == 'personal' && $link['rel'] == 'personal-feed' ? 'personal' : 'public';
                        $feeds[] = new Ld_Feed_Merger_Feed($link['href'], $instance, $feedType);
                    }
                }
            }
        }
        return $feeds;
    }

    public static function getEntries($feeds)
    {
        $entries = array();
        foreach ($feeds as $feed) {
            try {
                $feedEntries = $feed->getEntries();
                $entries = array_merge($entries, $feedEntries);
            } catch (Exception $e) {
                echo "Error with " . $feed->getUrl() . ".<br>" . $e->getMessage() . "<br>";
            }
        }
        usort($entries, array('self', 'cmpEntries'));
        return $entries;
    }

    public static function cmpEntries($a , $b)
    {
        $a_time = $a['timestamp'];
        $b_time = $b['timestamp'];
        if ($a_time == $b_time) {
            return 0;
        }
        return ($a_time > $b_time) ? -1 : 1;
    }

    public static function getSite()
    {
        return Zend_Registry::get('site');
    }

}

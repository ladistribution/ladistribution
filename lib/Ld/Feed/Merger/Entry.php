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

class Ld_Feed_Merger_Entry extends ArrayObject
{

    public $package = null;

    public $instance = null;

    protected $_entry = null;

    protected static $_site = null;

    public function __construct($entry, $package, $instance)
    {
        $this->_entry = $entry;
        $this->package = $package;
        $this->instance = $instance;
        $this->parseEntry();
        $this->normaliseEntry();
    }

    public function parseEntry()
    {
        $this->title = $this->_entry->getTitle();
        $this->link = $this->_entry->getLink();
        $this->type = $this->_entry->getPostType();
        $this->action = $this->_entry->getAction();
        $this->enclosure = $this->_entry->getEnclosure();
        $this->content = $this->_entry->getContent();
        $this->summary = $this->_entry->getDescription();

        $this->timestamp = $this->getTimestamp();
        $this->user = $this->getUser();
        $this->screenName = $this->getScreenName();
        $this->userUrl = $this->getUserUrl();
        $this->avatarUrl = $this->getAvatarUrl();
    }

    public function normaliseEntry()
    {
        switch ($this->package) {
            case 'blogmarks':
                $this->type = 'link';
                $this->action = 'posted a link';
                break;
            case 'statusnet':
                $this->type = 'status';
                break;
            case 'dokuwiki':
                if (strpos($this->title, ' - created') !== false) {
                    $this->title = str_replace(' - created', '', $this->title);
                    $this->action = 'created a page';
                } else {
                    $this->action = 'modified a page';
                }
                break;
            case 'bbpress':
                $this->title = str_replace($this->screenName . ' on', '', $this->title);
                $this->title = trim($this->title, " \"");
                $this->action = 'created a topic';
                break;
            case 'wordpress':
                $this->action = 'published a post';
                break;
        }
    }

    public function getTimestamp()
    {
        if ($date = $this->_entry->getDateCreated()) {
            return $date->getTimestamp();
        }
    }

    public function getUser()
    {
        if (isset($this->user)) {
            return $this->user;
        }
        if ($username = $this->_entry->getUsername()) {
            if ($user = $this->getSite()->getUser($username)) {
                return $this->user = $user;
            }
        }
        if ($author = $this->_entry->getAuthor()) {
            $name = $author['name'];
            if ($user = $this->getSite()->getUser($name)) {
                return $this->user = $user;
            }
        }
    }

    public function getScreenName()
    {
        if ($user = $this->getUser()) {
            return empty($user['fullname']) ? $user['username'] : $user['fullname'];
        }
        if ($author = $this->_entry->getAuthor()) {
            return $author['name'];
        }
    }

    public function getUserUrl()
    {
        if ($user = $this->getUser()) {
            return Ld_Ui::getAdminUrl(array('module' => 'merger', 'username' => $user['username']), 'merger-user');
        }
        return $this->_entry->getUserUrl();
    }

    public function getAvatarUrl()
    {
        if ($user = $this->getUser()) {
            return Ld_Ui::getAvatarUrl($user);
        }
        if ($avatarUrl = $this->_entry->getAvatarUrl()) {
            return $avatarUrl;
        }
        return Ld_Ui::getDefaultAvatarUrl();
    }

    // Utils

    public static function getSite() { return Zend_Registry::get('site'); }

    // Array Access

    public function offsetExists($index) { return isset($this->$index); }

    public function offsetGet($index) { return $this->offsetExists($index) ? $this->$index : null; }

    public function offsetUnset($index) { $this->$index = null; }

    public function offsetSet($index, $value) { $this->$index = $value; }

}

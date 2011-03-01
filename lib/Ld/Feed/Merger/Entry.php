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
        $this->id = $this->_entry->getId();
        $this->title = $this->_entry->getTitle();
        $this->link = $this->_entry->getLink();
        $this->type = $this->_entry->getPostType();
        $this->action = $this->_entry->getAction();
        $this->enclosure = $this->_entry->getEnclosure();
        $this->content = $this->_entry->getContent();
        $this->summary = $this->_entry->getDescription();
        $this->categories = $this->_entry->getCategories();
        $this->thumbnails = $this->_entry->getThumbnails();

        $this->timestamp = $this->getTimestamp();
        $this->user = $this->getUser();
        $this->screenName = $this->getScreenName();
        $this->userUrl = $this->getUserUrl();
        $this->avatarUrl = $this->getAvatarUrl();
    }

    public function toArray()
    {
        return array(
            'id'         => $this->id,
            'hash'       => $this->hash,
            'instance'   => $this->instance,
            'title'      => $this->title,
            'link'       => $this->link,
            'type'       => $this->type,
            'action'     => $this->action,
            'enclosure'  => $this->enclosure,
            'content'    => $this->content,
            'summary'    => $this->summary,
            'categories' => $this->categories,
            'thumbnails' => $this->thumbnails,
            'timestamp'  => $this->timestamp,
            'user'       => $this->user,
            'screenName' => $this->screenName,
            'userUrl'    => $this->userUrl,
            'avatarUrl'  => $this->avatarUrl
        );
    }

    public function normaliseEntry()
    {
        switch ($this->package) {
            case 'blogmarks':
                $this->type = 'link';
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
            case 'moonmoon':
                $this->content = null;
                break;
        }

        if (empty($this->action)) {
            switch ($this->type) {
                case 'link':
                    $this->action = 'posted a link';
                    break;
            }
        }

        if (empty($this->hash)) {
            if (isset($this->id)) {
                $this->hash = substr(md5($this->id), 0, 6);
            }
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
        if ($author = $this->_entry->getAuthor()) {
            if (isset($author['uri'])) {
                return $author['uri'];
            }
        }
        return $this->_entry->getUserUrl();
    }

    public function getAvatarUrl()
    {
        if ($user = $this->getUser()) {
            return Ld_Ui::getAvatarUrl($user);
        }
        if ($link = $this->_entry->getAvatarLink()) {
            return $link->url;
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

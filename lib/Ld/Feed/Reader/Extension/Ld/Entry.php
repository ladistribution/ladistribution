<?php

/**
 * La Distribution PHP libraries
 *
 * @category   Ld
 * @package    Ld_Feed_Reader_Extension
 * @author     François Hodierne <francois@hodierne.net>
 * @copyright  Copyright (c) 2009-2010 h6e.net / François Hodierne (http://h6e.net/)
 * @license    Dual licensed under the MIT and GPL licenses.
 * @version    $Id$
 */

class Ld_Feed_Reader_Extension_Ld_Entry extends Zend_Feed_Reader_Extension_EntryAbstract
{

  /**
   * Get username
   *
   * @return string|null
   */
  public function getUsername()
  {
      return $this->_getData('username');
  }

  /* Deprecated */
  public function getAvatarUrl()
  {
      return $this->_getData('avatar');
  }

  /* Deprecated */
  public function getUserUrl()
  {
      return $this->_getData('userurl');
  }

  /**
   * Get post type
   *
   * @return string|null
   */
  public function getPostType()
  {
      $nodeList = $this->getXpath()->query($this->getXpathPrefix() . '/activity:object-type');

      if ($nodeList->length == 0) {
          $nodeList = $this->getXpath()->query($this->getXpathPrefix() . '/activity:object/activity:object-type');
      }

      if ($nodeList->length > 0) {
          $value = $nodeList->item(0)->nodeValue;
          switch ($value) {
              case 'http://activitystrea.ms/schema/1.0/bookmark':
                return 'link';
              case 'http://activitystrea.ms/schema/1.0/note':
                return 'status';
          }
      }

      return $this->_getData('type');
  }

  /**
   * Get action
   *
   * @return string|null
   */
  public function getAction()
  {
      return $this->_getData('action');
  }

  /**
   * Get the entry data specified by name
   *
   * @param  string $name
   * @param  string $type
   * @return mixed|null
   */
  protected function _getData($name)
  {
      $key = "ld-$name";

      if (array_key_exists($key, $this->_data)) {
          return $this->_data[$key];
      }

      $data = $this->_xpath->evaluate('string(' . $this->getXpathPrefix() . '/ld:' . $name . ')');

      if (!$data) {
          $data = null;
      }

      $this->_data[$key] = $data;

      return $data;
  }

  /**
   * Get the entry avatar
   *
   * @return string
   */
  public function getAvatarLink()
  {
      if (array_key_exists('avatar', $this->_data)) {
          return $this->_data['avatar'];
      }

      $avatar = null;

      $nodeList = $this->getXpath()->query($this->getXpathPrefix() . '/atom:link[@rel="avatar"]');

      if ($nodeList->length == 0) {
          $nodeList = $this->getXpath()->query($this->getXpathPrefix() . '/activity:actor/atom:link[@rel="avatar"][@media:width="32"]');
      }

      if ($nodeList->length == 0) {
          $nodeList = $this->getXpath()->query($this->getXpathPrefix() . '/activity:actor/atom:link[@rel="avatar"]');
      }

      if ($nodeList->length > 0) {
          $avatar = new stdClass();
          $avatar->url    = $nodeList->item(0)->getAttribute('href');
          $avatar->type   = $nodeList->item(0)->getAttribute('type');
      }

      $this->_data['avatar'] = $avatar;

      return $this->_data['avatar'];
  }
 
  /**
   * Register Ld namespace
   *
   * @return void
   */
  protected function _registerNamespaces()
  {
      $this->_xpath->registerNamespace('ld', 'http://ladistribution.net/#ns');
      $this->_xpath->registerNamespace('activity', 'http://activitystrea.ms/spec/1.0/');
      $this->_xpath->registerNamespace('media', 'http://purl.org/syndication/atommedia');
  }

}

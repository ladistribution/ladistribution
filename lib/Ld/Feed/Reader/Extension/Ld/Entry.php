<?php

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

  /**
   * Get avatar
   *
   * @return string|null
   */
  public function getAvatarUrl()
  {
      return $this->_getData('avatar');
  }

  /**
   * Get user url
   *
   * @return string|null
   */
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
   * Register Ld namespace
   *
   * @return void
   */
  protected function _registerNamespaces()
  {
      $this->_xpath->registerNamespace('ld', 'http://ladistribution.net/#ns');
  }

}

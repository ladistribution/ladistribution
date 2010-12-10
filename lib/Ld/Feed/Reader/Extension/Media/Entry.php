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

class Ld_Feed_Reader_Extension_Media_Entry extends Zend_Feed_Reader_Extension_EntryAbstract
{

    /**
     * Get all thumbnails
     */
    public function getThumbnails()
    {
        if (array_key_exists('thumbnails', $this->_data)) {
            return $this->_data['thumbnails'];
        }

        $list = $this->getXpath()->query($this->getXpathPrefix() . '//media:thumbnail');

        $thumbnails = array();

        if ($list->length) {
            foreach ($list as $thumbnail) {
                $thumbnails[] = array(
                    'url'    => $thumbnail->getAttribute('url'),
                    'width'  => $thumbnail->getAttribute('width'),
                    'height' => $thumbnail->getAttribute('height')
                );
            }
        }

        $this->_data['thumbnails'] = $thumbnails;

        return $this->_data['thumbnails'];
    }

    /**
     * Register Atom Thread Extension 1.0 namespace
     *
     * @return void
     */
    protected function _registerNamespaces()
    {
        $this->_xpath->registerNamespace('media', 'http://search.yahoo.com/mrss/');
    }

}

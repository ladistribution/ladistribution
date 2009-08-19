<?php

/**
 * Copyright (c) 2008 Netvibes (http://www.netvibes.org/).
 *
 * This file is part of Netvibes Widget Platform.
 *
 * Netvibes Widget Platform is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Netvibes Widget Platform is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Netvibes Widget Platform.  If not, see <http://www.gnu.org/licenses/>.
 */

 /**
  * La Distribution PHP libraries
  *
  * @category   Ld
  * @package    Ld_Preference
  * @copyright  Copyright (c) 2008 Netvibes (http://www.netvibes.org/)
  * @license    Licensed under the LGPL license.
  * @version    $Id$
  */

class Ld_Preference
{
    /**
     * Preference type.
     *
     * @var string
     */
    protected $_type;

    /**
     * Preference name.
     *
     * @var string
     */
    protected $_name;

    /**
     * Preference label.
     *
     * @var string
     */
    protected $_label;

    /**
     * Preference default value.
     *
     * @var string
     */
    protected $_defaultValue;

    /**
     * Preference options for 'list' type.
     *
     * @var array
     */
    protected $_listOptions;

    /**
     * Preference options for 'range' type.
     *
     * @var array
     */
    protected $_rangeOptions;

    /**
     * Supported types
     *
     * @var array
     */    
    protected $_supportedTypes = array('text', 'password', 'boolean', 'hidden',
        'range', 'list', 'color', 'textarea', 'email', 'user', 'lang');

    /**
     * Preference constructor.
     *
     * @param string $type
     */
    public function __construct($type)
    {
        if (in_array((string) $type, $this->_supportedTypes)) {
            $this->_type = (string) $type;
        } else {
            $this->_type = 'text';
        }
    }

    /**
     * Returns the preference type.
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * Sets the preference name.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->_name = (string) $name;
    }

    /**
     * Returns the preference name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Sets the preference label.
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->_label = (string) $label;
    }

    /**
     * Returns the preference label.
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->_label;
    }

    /**
     * Sets the preference default value.
     *
     * @param string $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        $this->_defaultValue = (string) $defaultValue;
    }

    /**
     * Returns the preference default value.
     *
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->_defaultValue;
    }

    /**
     * Sets the preference list options.
     *
     * @param array $options
     */
    public function setListOptions(array $options)
    {
        $this->_listOptions = $options;
    }

    /**
     * Add a new option to the list.
     *
     * @param string $value
     * @param string $label
     */
    public function addListOption($value, $label = '')
    {
        if (!is_array($this->_listOptions)) {
            $this->_listOptions = array();
        }
        if (empty($label)) {
            $label = $value;
        }
        $this->_listOptions[(string) $label] = $value;
    }

    /**
     * Returns the preference list options.
     *
     * @return array
     */
    public function getListOptions()
    {
        return $this->_listOptions;
    }

    /**
     * Sets the preference range options.
     *
     * @param int $step
     * @param int $min
     * @param int $max
     */
    public function setRangeOptions($step, $min, $max)
    {
        $this->_rangeOptions = array('step' => (int) $step,
                                     'min'  => (int) $min,
                                     'max'  => (int) $max);
    }

    /**
     * Returns the preference range options.
     *
     * @return array
     */
    public function getRangeOptions()
    {
        return $this->_rangeOptions;
    }

    /**
     * Retrieves the preferences attributes in an array.
     *
     * @return array
     */
    public function toArray()
    {
        $preference = array();
        $preference['type'] = $this->_type;
        if (!empty($this->_name)) {
            $preference['name'] = $this->_name;
        }
        if (!empty($this->_label)) {
            $preference['label'] = $this->_label;
        }
        if (isset($this->_defaultValue)) {
            $preference['defaultValue'] = $this->_defaultValue;
        }
        if (!empty($this->_listOptions)) {
            $options = array();
            foreach ($this->_listOptions as $label => $value) {
                $options[] = array('label' => $label, 'value' => $value);
            }
            $preference['options'] = $options;
        }
        if (!empty($this->_rangeOptions)) {
            $preference['step'] = (string) $this->_rangeOptions['step'];
            $preference['min']  = (string) $this->_rangeOptions['min'];
            $preference['max']  = (string) $this->_rangeOptions['max'];
        }
        return $preference;
    }
}

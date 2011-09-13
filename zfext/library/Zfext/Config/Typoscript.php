<?php
/**
 * Zfext - Zend Framework for TYPO3
 *
 * LICENSE
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 *
 * @copyright  Copyright (c) 2010 Christian Opitz - Netzelf GbR (http://netzelf.de)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @version    $Id$
 */

/**
 * @category   TYPO3
 * @package    Zend_Db
 * @subpackage Adapter
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Config_Typoscript extends Zend_Config
{
    /**
     * The raw TypoScript
     * @var array
     */
    protected $_rawData;

    /**
     * The current object type (e.g. TEXT)
     * @var string
     */
    protected $_type = null;

    /**
     * The options to be shared between this and subsequent instances
     * @var stdClass
     */
    protected $_options;

    protected $_defaultOptions = array(
        'throwExceptions' => false
    );

    /**
     * Constructor - translate the incoming array to Zend_Config-array
     *
     * @param array $array
     * @param boolean $allowModifications (untested)
     * @param string $type
     * @param array $options
     */
    public function __construct(array $array, $allowModifications = false, $type = null, $options = null)
    {
        if (is_object($options)) {
            $this->_options = $options;
        } else {
            $this->_options = (object) array_merge($this->_defaultOptions, (array) $options);
        }

        parent::__construct(array(), $allowModifications);

        $this->_type = $type;
        $this->_rawData = $array;
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $typeKey = substr($key, 0, -1);
                $type = array_key_exists($typeKey, $array) ? $array[$typeKey] : null;
                $this->_data[$typeKey] = new self($value, $allowModifications, $type, $this->_options);
            } elseif (!array_key_exists($key.'.', $array)) {
                $this->_data[$key] = $value;
            }
        }
        $this->_count = count($this->_data);
    }

    /**
     * Get the type of the current object
     * @return string
     */
    public function __toString()
    {
        return $this->_type;
    }

    /**
     * Get the type of the current object
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /* (non-PHPdoc)
     * @see Zend_Config::toArray()
     */
    public function toArray()
    {
        return $this->_rawData;
    }

    /* (non-PHPdoc)
     * @see Zend_Config::get()
     */
    public function get($name, $default = null)
    {
        if ($default === null && !$this->__isset($name) && $this->_options->throwExceptions) {
            throw new Zfext_Exception($name.' was not found');
        }
        return parent::get($name, $default);
    }

    /**
     * Whether to throw an exception when a value is empty and no
     * default was provided
     *
     * @param boolean $flag
     */
    public function throwExceptions($flag = true)
    {
        $this->_options->throwExceptions = $flag;
    }
}
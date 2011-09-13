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
 * @package    Zfext_Plugin
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Plugin
{
	/**
	 * @var tslib_pibase
	 */
	protected static $_currentInstance;

	/**
	 * The configs of the current instance
	 * @var array
	 */
	protected static $_configs = array();

	/**
	 * Set current plugin instance
	 *
	 * @param tslib_pibase $instance
	 */
	public static function setInstance(tslib_pibase $instance)
	{
		self::$_currentInstance = $instance;
	}

	/**
	 * Get current plugin instance
	 *
	 * @return tslib_pibase
	 */
	public static function getInstance()
	{
		return self::$_currentInstance;
	}

	/**
	 * Get the config of the current plugin
	 *
	 * @return Zfext_Config_Typoscript
	 */
	public static function getConfig($key = null)
	{
	    $plugin = self::getInstance();
	    if (!$plugin) {
	        throw new Zfext_Exception('No plugin set');
	    }
	    if (!array_key_exists($plugin->prefixId, self::$_configs)) {
	        self::$_configs[$plugin->prefixId] = new Zfext_Config_Typoscript(
	            // Exclude zfext-config:
	            array_diff_key($plugin->conf, array('zfext' => 1, 'zfext.' => 1))
	        );
	    }
	    return $key ? self::$_configs[$plugin->prefixId]->get($key) : self::$_configs[$plugin->prefixId];
	}
}
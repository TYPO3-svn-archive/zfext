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
	 * Latest instance (can be Zfext_Module also)
	 * @var Zfext_Plugin
	 */
	protected static $_instance;

	/**
	 * The latest loaded plugin instance
	 * @var tslib_pibase
	 */
	protected $_plugin;

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
	public function __construct(tslib_pibase $instance = null)
	{
		self::$_instance = $this;
		$this->_plugin = $instance;
	}

	/**
	 * Proxy to plugin instance
	 *
	 * @param string $method
	 * @param array $arguments
	 * @throws Zfext_Exception
	 * @return mixed
	 */
	public function __call($method, $arguments)
	{
	    if (!is_callable(array($this->_getPlugin(), $method))) {
	        throw new Zfext_Exception('Could not call "'.$method.'"');
	    }
	    return call_user_func_array(array($this->_getPlugin(), $method), $arguments);
	}

	/**
	 * Proxy to plugin instance
	 *
	 * @param string $param
	 * @return mixed
	 */
	public function __get($param)
	{
	    return isset($this->_getPlugin()->$param) ? $this->_getPlugin()->$param : null;
	}

	/**
	 * Proxy to plugin instance
	 *
	 * @param string $param
	 * @param mixed $value
	 */
	public function __set($param, $value)
	{
	    $this->_getPlugin()->$param = $value;
	}

	/**
	 * Proxy to plugin instance
	 *
	 * @param string $param
	 */
	public function __isset($param)
	{
	    return isset($this->_getPlugin()->$param);
	}

	/**
	 * Return current plugin instance (wrapped to eventually do
	 * further checks later)
	 *
	 * @return tslib_pibase
	 */
	protected function _getPlugin()
	{
	    return $this->_plugin;
	}

	/**
	 * Launch an Application for the current plugin/module
	 */
	public function run()
	{
	    Zfext_Manager::loadLibrary('zfext');
        Zfext_Manager::loadLibrary($this->extKey);
		$application = new Zend_Application($this->extKey);
		$application
		->setOptions(Zfext_Manager::getConfig($this->extKey))
		->bootstrap();
		$application->run();
	}

	/**
	 * Get current plugin instance
	 *
	 * @return tslib_pibase
	 */
	public static function getInstance()
	{
		return self::$_instance;
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
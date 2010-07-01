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
 * This frontcontroller plugin registers an autoloader for the plugins
 * if they want one (@see Zfext_ExtMgm::$_defaultPluginOptions) 
 * 
 * @category   TYPO3
 * @package    Zfext_Controller
 * @subpackage Plugin
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Controller_Plugin_Autoloader extends Zend_Controller_Plugin_Abstract
{
	/**
	 * @var array The module names that are already registered
	 */
	protected $_registeredModules = array();
	
	/* (non-PHPdoc)
	 * @see Controller/Plugin/Zend_Controller_Plugin_Abstract#routeShutdown()
	 */
	public function routeShutdown(Zend_Controller_Request_Abstract $request)
	{
		$module = $request->getModuleName();
		if (!Zfext_ExtMgm::getPluginOption($module, 'autoloader')
			|| in_array($module, $this->_registeredModules))
		{
			return;
		}
		
		$dir = Zend_Controller_Front::getInstance()
			->getDispatcher()
			->getControllerDirectory($module);
		
		$dir = str_replace(array("\\",'/'), DIRECTORY_SEPARATOR, $dir);
		$parts = explode(DIRECTORY_SEPARATOR, $dir);
		array_pop($parts);
		
		new Zfext_Application_Module_Autoloader(array(
			'namespace' => Zfext_ExtMgm::getPluginNamespace($module),
			'basePath' => implode(DIRECTORY_SEPARATOR, $parts)
		));
		
		$this->_registeredModules[] = $module;
	}
}
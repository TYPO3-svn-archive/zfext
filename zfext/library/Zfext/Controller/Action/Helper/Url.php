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
 * @version    $Id: Plugin.php 28 2010-06-27 20:53:56Z copitz $
 */

/**
 * Overrides the simple method from Zend Helper with a call to
 * the regular url()-method that invokes the router.
 * @see Zfext_Bootstrap::_initZfextActionHelper
 * 
 * @category   TYPO3
 * @package    Zfext_Controller
 * @subpackage Action
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Controller_Action_Helper_Url extends Zend_Controller_Action_Helper_Url
{
	/**
	 * Overrides parent simple method
	 * 
	 * @param string $action
	 * @param string $controller
	 * @param string $module
	 * @param array $params
	 * @return string
	 */
	public function simple($action, $controller = null, $module = null, array $params = null) 
	{
		$request = $this->getRequest();
		
		if (!is_array($params))
		{
			$params = array();
		}
		if ($module !== null)
		{
			$params[$request->getModuleKey()] = $module;
		}
		if ($controller !== null) 
		{
			$params[$request->getControllerKey()] = $controller;
		}
		$params[$request->getActionKey()] = $action;
		
		return $this->url($params);
	}
}
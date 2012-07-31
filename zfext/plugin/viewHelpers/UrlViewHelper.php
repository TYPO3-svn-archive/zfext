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
 * @version    $Id: Bootstrap.php 36506 2010-08-08 15:46:09Z metti $
 */

/**
 * @category   Fluid
 * @package    ViewHelper
 * @subpackage Helper
 * @author     Christian Opitz <co@netzelf.de>
 */
class Tx_Zfext_ViewHelper_UrlViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper
{
	/**
	 * @param string $action Action
	 * @param string $controller Controller
	 * @param string $module Module
	 * @param array $params Additional params
	 * @param string $route Name of the route to use
	 * @param boolean $reset If to reset current params
	 * @param boolean $encode If to encode the link
	 * @return string Rendered link
	 */
	public function render($action = NULL, $controller = NULL, $module = NULL, array $params = array(), $route = null, $reset = false, $encode = true)
	{
	    $front = Zend_Controller_Front::getInstance();
		$request = $front->getRequest();
		if ($action) {
		    $params[$request->getActionKey()] = $action;
		}
		if ($controller) {
		    $params[$request->getControllerKey()] = $controller;
		}
		if ($module) {
		    $params[$request->getModuleKey()] = $module;
		}

		return $front->getRouter()->assemble($params, $route, $reset, $encode);
	}
}
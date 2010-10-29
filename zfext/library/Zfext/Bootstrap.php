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
 * The default bootstraper for Zfext plugin
 * 
 * @category   TYPO3
 * @package    Zfext
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{	
	protected static $_firstRun = true;
	
	/**
	 * Sticks request, response and dispatcher together
	 */
	protected function _initZfextFrontcontroller()
	{		
	    if (!self::$_firstRun) {
	    	Zend_Controller_Front::getInstance()->resetInstance();
	    }else{
	    	self::$_firstRun = false;
	    }
		Zend_Controller_Front::getInstance()
		->setResponse(new Zfext_Controller_Response_Plugin())
		->setDispatcher(new Zfext_Controller_Dispatcher_Plugin());
	}
	
	/**
	 * Sets the router for TYPO3
	 */
	protected function _initRouter()
	{
		Zend_Controller_Front::getInstance()
		->setRouter(new Zfext_Controller_Router_Typo3());
	}
	
	/**
	 * Override default action helper while its not using router in
	 * its simple/direct method
	 */
	protected function _initZfextActionHelper() 
	{
		Zend_Controller_Action_HelperBroker::removeHelper('url');
		Zend_Controller_Action_HelperBroker::addHelper(new Zfext_Controller_Action_Helper_Url());
	}
}
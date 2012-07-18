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
 * This class only lets you set options for the error-handler controller plugin.
 * Options are:
 * enable:     Enable error handler (Actually needed to invoke this resource with standard
 *             settings, sets resources.frontcontroller.params.noErrorHandler to true when false)
 * class:      Set a class that should be used as error handler (which must extend
 *             Zend_Controller_Plugin_Abstract; default is Zend_Controller_Plugin_ErrorHandler)
 * module:     The module name for the error handler. Zfext error handler will be set as default
 *             if this is empty (with the module name)
 * controller: The controller name for the error handler
 * action:     The action name for the error handler
 *
 * All options are passed to the constructor of the handler - so if it comes to that
 * it has more options than module/controller/action you can pass them this way too.
 *
 * @category   TYPO3
 * @package    Zfext_Application
 * @subpackage Resource
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Application_Resource_Errorhandler extends Zend_Application_Resource_ResourceAbstract
{
	/**
     * Initialize Front Controller
     *
     * @return Zend_Controller_Front
     */
    public function init()
    {
    	$options = $this->getOptions();
    	if (!count($options)) {
    		return;
    	}
    	$this->getBootstrap()->bootstrap('frontcontroller');
    	$front = Zend_Controller_Front::getInstance();

    	if (!$options['enable']) {
    		$front->setParam('noErrorHandler', true);
    		return;
    	}

    	if (!empty($options['class'])) {
    		$front->setParam('noErrorHandler', true);
    		$class = (string) $options['class'];
    	}else{
    		$class = 'Zend_Controller_Plugin_ErrorHandler';
    	}
    	unset($options['class'], $options['disable']);

    	$handler = new $class($options);

    	$front->registerPlugin($handler, 100);
    }
}
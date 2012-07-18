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
 * This plugin sets the error handler for TYPO3 exceptional errors to it's own handler,
 * which simply throws ErrorExceptions which will hopefully be handled by
 * Zend_Controller_Plugin_ErrorHandler.
 *
 * @category   TYPO3
 * @package    Zfext_Controller
 * @subpackage Plugin
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Controller_Plugin_ErrorHandler extends Zend_Controller_Plugin_Abstract
{
    public function routeStartup($request)
    {
        set_error_handler(array($this, 'errorHandler'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors']);
        parent::routeStartup($request);
    }

    public function dispatchLoopShutdown()
    {
        parent::dispatchLoopShutdown();
        restore_error_handler();
    }

	/**
	 * Catch errors and throw an error exception so that ZF can catch it and output
	 * it with the errorHandler-plugin.
	 *
	 * @param integer $errno
	 * @param string $errstr
	 * @param string $errfile
	 * @param integer $errline
	 * @param string $errcontext
	 */
	public function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
	{
		switch ( $errno ) {
			case E_USER_ERROR:
				$type = 'Fatal Error';
				$exit = TRUE;
			break;
			case E_USER_WARNING:
			case E_WARNING:
				$type = 'Warning';
			break;
			case E_USER_NOTICE:
			case E_NOTICE:
			case @E_STRICT:
				$type = 'Notice';
			break;
			case @E_RECOVERABLE_ERROR:
				$type = 'Catchable';
			break;
			default:
				$type = 'Unknown Error';
				$exit = true;
			break;
		}

		// deprecated erkennen
		if($errno==E_USER_NOTICE && preg_match('/^.*\(\)\sis\sdeprecated$/U', $errstr))
		{
			$stack		= debug_backtrace();
			$deprecated	= 'Deprecated: Function ' . $stack[1]['args'][0] . ' in ' . $stack[2]['file'] . ' on line ' . $stack[2]['line'];
			$file		= $stack[2]['file'];
			$line		= $stack[2]['line'];

			throw new ErrorException($deprecated, 0, $errno, $errfile, $errline);
			return;
		}

		throw new ErrorException($type.': '.$errstr, 0, $errno, $errfile, $errline);
	}
}
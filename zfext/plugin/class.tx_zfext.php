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

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(PATH_tslib.'class.tslib_fe.php');


/**
 * @category   TYPO3
 * @package    TYPO3
 * @subpackage tx_zfext
 * @author     Christian Opitz <co@netzelf.de>
 */
class tx_zfext extends tslib_pibase
{
	public $prefixId	  = 'tx_zfext';
	public $scriptRelPath = 'pi1/class.tx_zfext_pi1.php';	// Path to this script relative to the extension dir.
	public $extKey        = 'zfext';	// The extension key.

	/**
	 * @var array Holds the setup of the current extension
	 * @see $selfConf
	 */
	public $conf = array();

	/**
	 * @var array Respones are held here on plugin level and retrieved when a special response segment is required
	 */
	protected static $_responseRegister = array();

	/**
	 * We override the constructor and call it later from setup
	 * because we have to set the prefixId before
	 */
	public function __construct() {}

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	public function main($content, $conf)
	{
		$this->setupPlugin($conf);

		$responseSegment = 'default';
		if (!empty($conf['zfext.']['responseSegment'])) {
			$responseSegment = $conf['zfext.']['responseSegment'];
			if (strtolower($responseSegment) == 'false') {
				$responseSegment = false;
			}
		}

		if (isset(self::$_responseRegister[$this->prefixId])) {
		    $response = self::$_responseRegister[$this->prefixId];
		}else{
			$this->setConf($conf);

			set_error_handler(array($this, 'errorHandler'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['exceptionalErrors']);

			Zfext_ExtMgm::loadLibrary('zfext');

			Zfext_ExtMgm::loadLibrary($this->extKey);
		    Zfext_Plugin::setInstance($this);

			$application = new Zend_Application(
				t3lib_extMgm::extPath($this->extKey).'pi1',
				$this->extractOptions($this->conf['zfext.'])
			);
			$application->bootstrap()->run();

			restore_error_handler();

			$response = Zend_Controller_Front::getInstance()->getResponse();
			self::$_responseRegister[$this->prefixId] = $response;
		}


		$layout = Zend_Layout::getMvcInstance();
		if (!in_array($responseSegment, array('default', false)) && $layout->isEnabled()) {
		    $content = $layout->$responseSegment;
		}else{
			$content = $response->getBody($responseSegment);
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 * Detects the prefixId, extKey and $scriptRelPath (latter only points to
	 * the plugin directory in the hope that nobody needs the script itself)
	 *
	 * Detects if this is a USER_INT or USER plugin (sets checkCHash accordingly)
	 *
	 * Calls parent constructor and sets default piVars. (LoadLL is not yet
	 * done here but propably later in the translator adapter)
	 *
	 * @param unknown_type $conf
	 */
	protected function setupPlugin($conf)
	{
	    $signature = explode('.', $conf['zfext.']['signature']);
	    $this->extKey = $signature[0];
	    $this->prefixId = $signature[1];

	    $controllerPath = t3lib_div::getFileAbsFileName(
	    	$conf['zfext.']['resources.']['frontcontroller.']['controllerdirectory.'][$this->prefixId]);
		$extPath = realpath(t3lib_extMgm::extPath($this->extKey));
		$this->scriptRelPath = substr($controllerPath, strlen($extPath) + 1);

	    $type = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId];

		$this->pi_USER_INT_obj = ($type == 'USER_INT');
		$this->pi_checkCHash = ($type == 'USER');

		parent::tslib_pibase();
		parent::pi_setPiVarDefaults();
	}

	/**
	 * Set config and merge with referenced conf if so
	 *
	 * @param array $conf
	 */
	protected function setConf($conf)
	{
		if ($conf['zfext'] == '< plugin.tx_zfext.zfext') {
			// Actually same as in elseif but faster
			$conf['zfext.'] = t3lib_div::array_merge_recursive_overrule(
				$GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_zfext.']['zfext.'], $conf['zfext.']);
		}
		elseif ($conf['zfext'] && strpos($conf['zfext'], '<') === 0) {
			$conf['zfext.'] = $this->parseReferencedTS($conf['zfext'], $conf['zfext.']);
		}
		$this->conf = $conf;
	}

	protected function parseReferencedTS($ref, $conf)
	{
		$parts = explode('.', trim($ref, "\t <"));

		if (count($parts)) {
			$first = array_shift($parts);
			if (is_array($GLOBALS['TSFE']->tmpl->setup[$first.'.'])) {
				$refConf = $GLOBALS['TSFE']->tmpl->setup[$first.'.'];
				foreach ($parts as $part) {
					if ($refConf[$part] && strpos($refConf[$part], '<') === 0) {
						$refConf = $this->parseReferencedTS($refConf[$part], (array) $refConf[$part.'.']);
					}elseif (is_array($refConf[$part.'.'])) {
						$refConf = $refConf[$part.'.'];
					}else{
						$refConf = null;
						break;
					}
				}
			}
		}
		if (is_array($refConf)) {
			$conf = t3lib_div::array_merge_recursive_overrule($refConf, $conf);
		}
		return $conf;
	}

	/**
	 * Catch errors and throw an error exception so that ZF can catch it and output
	 * it neatly with the errorHandler-plugin.
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

	/**
	 * Extracts the options from the TypoScript-Array (removes the dots
	 * in the keys and replaces EXT: by the extpath if found in values)
	 *
	 * @param array $conf
	 * @return array
	 */
	protected function extractOptions($conf)
	{
		$dotless = array();
		foreach ($conf as $key => $value)
		{
			if (is_array($value))
			{
				$dotless[trim($key,'.')] = $this->extractOptions($value);
			}
			else
			{
				if (strpos($value, 'EXT:') === 0)
				{
					$pathParts = explode('/', substr($value,4));
					$value = t3lib_extMgm::extPath(array_shift($pathParts));
					$value .= implode(DIRECTORY_SEPARATOR,$pathParts);
				}
				$dotless[trim($key,'.')] = $value;
			}
		}
		return $dotless;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/zfext/pi1/class.tx_zfext.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/zfext/pi1/class.tx_zfext.php']);
}

?>
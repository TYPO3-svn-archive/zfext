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
	 * @var Zend_Application
	 */
	protected static $_application;
	
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
		if (!$this->setupApplication()) {
    	    return '';
		}
		
		$this->setupPlugin($conf);
    		
		self::$_application->run();
		
		return $this->pi_wrapInBaseClass(
			Zend_Controller_Front::getInstance()->getResponse()->getBody()
		);
	}
	
	/**
	 * Sticks the application together and returns true if everything
	 * seems to be okay
	 * 
	 * @return boolean
	 */
	protected function setupApplication()
	{
		if (self::$_application)
		{
			Zend_Controller_Front::getInstance()->getResponse()->clearBody();
		    return true;
		}
		
		$conf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_zfext.']['zfext.'];
		
		if (!empty($conf['includePaths.']['zfLibrary']) && 
			is_string($conf['includePaths.']['zfLibrary']))
		{
			$path = realpath(t3lib_div::getFileAbsFileName($conf['includePaths.']['zfLibrary']));
    		if ($path) {
    			set_include_path($path.PATH_SEPARATOR.get_include_path());
    		} else {
    			t3lib_div::devLog($path.' seems not to exist. Did not add it!', $this->extKey);
    			return false;
    		}
		}
		unset($conf['includePaths.']['zfLibrary']);
		
		require_once('Zend/Application.php');
		
		self::$_application = new Zend_Application(
			t3lib_extMgm::extPath($this->extKey).'pi1', 
			$this->extractOptions($conf)
		);
		self::$_application->bootstrap();
		
		return true;
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
	 * Registers its instance to Zfext_Plugin which later will be used by the 
	 * Zfext_Controller_Router_Typo3 that will set the moduleName to the prefixId
	 * 
	 * @param unknown_type $conf
	 */
	protected function setupPlugin($conf)
	{
	    $this->conf = $conf;
	    
	    $signature = explode('.', $conf['zfext']);
	    $this->extKey = $signature[0];
	    $this->prefixId = $signature[1];
	    
	    $controllerPath = realpath(
	        Zend_Controller_Front::getInstance()
	        ->getControllerDirectory($this->prefixId)
	    );
		$extPath = realpath(t3lib_extMgm::extPath($this->extKey));
		$this->scriptRelPath = substr($controllerPath, strlen($extPath) + 1);
	    
	    $type = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId];
		
		$this->pi_USER_INT_obj = ($type == 'USER_INT');
		$this->pi_checkCHash = ($type == 'USER');
		
		parent::tslib_pibase();
		parent::pi_setPiVarDefaults();
		
	    Zfext_Plugin::setInstance($this);
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
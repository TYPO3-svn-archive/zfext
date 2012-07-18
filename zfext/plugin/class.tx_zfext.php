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
	protected static $_responseRegistry = array();

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

		if (isset(self::$_responseRegistry[$this->prefixId])) {
		    $response = self::$_responseRegistry[$this->prefixId];
		}else{
			$plugin = new Zfext_Plugin($this);
            $plugin->run();

			$response = Zend_Controller_Front::getInstance()->getResponse();
			if (!in_array(Zend_Controller_Front::getInstance()->getParam('keepResponse'), array(0, '0', false, 'false'), true)) {
			    self::$_responseRegistry[$this->prefixId] = $response;
			}
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
		$this->scriptRelPath = t3lib_extMgm::extRelPath($this->extKey);
		$this->conf = $conf;

	    $type = $GLOBALS['TSFE']->tmpl->setup['plugin.'][$this->prefixId];

		$this->pi_USER_INT_obj = ($type == 'USER_INT');
		$this->pi_checkCHash = ($type == 'USER');

		parent::tslib_pibase();
		parent::pi_setPiVarDefaults();
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/zfext/pi1/class.tx_zfext.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/zfext/pi1/class.tx_zfext.php']);
}

?>
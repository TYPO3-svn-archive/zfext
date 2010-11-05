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
 * Access to cObject->cObjGetSingle
 * 
 * @category   TYPO3
 * @package    Zend_View
 * @subpackage Helper
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zend_View_Helper_CObject extends Zend_View_Helper_Abstract
{
	/**
	 * Renders the TypoScript object in the given TypoScript setup path.
	 *
	 * @param string $typoscriptObjectPath The TypoScript setup path or a TS-Object
	 * @param array $mySetup Your setup - either to merge with that from the path or as conf for your object
	 * @return string the content of the rendered TypoScript object
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Niels Pardon <mail@niels-pardon.de>
	 * @author Christian Opitz <co@netzelf.de>
	 */
	public function cObject($typoscriptObjectPath, $mySetup = null)
	{
		$pathSegments = explode('.', $typoscriptObjectPath);
		$typoscriptObject = $pathSegments[count($pathSegments) - 1];
		
		if (count($pathSegments) > 1) {
			$setup = $GLOBALS['TSFE']->tmpl->setup;
			$lastPart = array_pop($pathSegments);
			
			foreach ($pathSegments as $segment) {
				if (!array_key_exists($segment . '.', $setup)) {
					throw new Zend_View_Exception('TypoScript object path "' . htmlspecialchars($typoscriptObjectPath) . '" does not exist');
				}
				$setup = $setup[$segment . '.'];
			}
			
			$typoscriptObject = $setup[$lastPart];
			$setup = $setup[$lastPart.'.'];
		}else{
			$typoscriptObject = $typoscriptObjectPath;
			$setup = array();
		}
		if (is_array($mySetup)) {
			$setup = t3lib_div::array_merge_recursive_overrule($setup, $mySetup);
		}
		Zend_Debug::dump($typoscriptObject);
		Zend_Debug::dump($setup);
		return Zfext_Plugin::getInstance()->cObj->cObjGetSingle($typoscriptObject, $setup);
	}
}
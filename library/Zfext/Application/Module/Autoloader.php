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
 * @category   TYPO3
 * @package    Zfext_Application
 * @subpackage Module
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Application_Module_Autoloader extends Zend_Application_Module_Autoloader
{
	/**
	 * Overriding the parent method because it behaves wrong when using
	 * prefixed namespaces.
	 * <code>
	 * $loader = new Zend_Application_Module_Autoloader(array(
	 *     'namespace' => 'Tx_MyExt',
	 *     'basePath'  => '/path/to/tx_myextension/plugin/',
	 * ))
	 * </code>
	 * The class names in there are f.i. Tx_MyExt_Model_DbTable_Pages
	 * Problem:
	 * Parent method detects 'Tx' to be it's namespace and not 'Tx_MyExt'
	 * Hacked that here.
	 * 
	 * @param string $class
	 * @return string|boolean False if not matched other wise the correct path
	 */
	public function getClassPath($class)
	{
		$namespace = $this->getNamespace();
		if (strpos($class, $namespace) !== 0)
		{
			return false;
		}
		$this->_namespace = null;
		$classPath = parent::getClassPath($class);
		$this->setNamespace($namespace);
		return $classPath;
	}
}
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
 * @package    Zend_Db
 * @subpackage Adapter
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zend_Db_Adapter_Typo3 extends Zend_Db_Adapter_Mysql
{
	/**
	 * Constructor.
     *
     * $config is an array of key/value pairs or an instance of Zend_Config
     * containing configuration options.  We have to set some values for
     * dbname, password, username because 
     * @link Zend_Db_Adapter_Abstract#_checkRequiredOptions gathers them.
     * 
	 * @param array|Zend_Config $config An array or instance of Zend_Config having configuration data
	 */
	public function __construct($config)
	{
		if ($config instanceof Zend_Config)
		{
			$config = $config->toArray();
		}
		$config['dbname'] = 'dummyDbName';
		$config['password'] = 'dummyDbPassword';
		$config['username'] = 'dummyDbUsername';
		
		parent::__construct($config);
	}
	
	/* (non-PHPdoc)
	 * @see Zend_Db_Adapter_Mysql#_connect()
	 */
	protected function _connect()
	{
		if ($this->_connection)
		{
			return;
		}
		if (!$GLOBALS['TYPO3_DB'] instanceof t3lib_DB)
		{
			throw new Zend_Db_Adapter_Exception(
				'Could not use $GLOBALS[\'TYPO3_DB\'] (is no t3lib_DB)');
			return;
		}
		if (!isset($GLOBALS['TYPO3_DB']->link))
		{
			throw new Zend_Db_Adapter_Exception(
				'Could not find a connection link in $GLOBALS[\'TYPO3_DB\']');
			return;
		}
		$this->_connection = $GLOBALS['TYPO3_DB']->link;
	}
}
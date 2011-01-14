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
 * This is a hacky class that makes the dispatcher use class names in the way we
 * need it for TYPO3-Extensions - e.g.:
 * Tx_MyExt_Module_IndexController or
 * Tx_MyExt_Suffix_Module_IndexController or
 * Tx_MyExt_IndexController ...
 * 
 * The trick is to override the 'prefixDefaultModule'-param to force the standard
 * dispatcher to call the formatClassName-method which is overridden too and
 * serves the right class names.
 * 
 * @category   TYPO3
 * @package    Zfext_Controller
 * @subpackage Dispatcher
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Controller_Dispatcher_Plugin extends Zend_Controller_Dispatcher_Standard
{
	/**
	 * @var boolean The real param
	 */
	protected $_prefixDefaultModule = false;
	
	/* (non-PHPdoc)
	 * @see Controller/Dispatcher/Zend_Controller_Dispatcher_Abstract#setParam()
	 */
	public function setParam($name, $value)
    {
        if ($name == 'prefixDefaultModule') {
        	$this->_prefixDefaultModule = $value;
        	$value = true;
        }
        return parent::setParam($name, $value);
    }

    /* (non-PHPdoc)
     * @see Controller/Dispatcher/Zend_Controller_Dispatcher_Abstract#setParams()
     */
    public function setParams(array $params)
    {
    	if (isset($params['prefixDefaultModule'])) {
			$this->_prefixDefaultModule = $params['prefixDefaultModule'];
		}
		$params['prefixDefaultModule'] = true;
		return parent::setParams($params);
    }
	
	/* (non-PHPdoc)
	 * @see Controller/Dispatcher/Zend_Controller_Dispatcher_Standard#formatClassName()
	 */
	public function formatClassName($moduleName, $className)
	{
		if ($moduleName == 'zfext') {
			return 'Tx_Zfext_'.$className;
		}
		
		$formatedClass = Zfext_ExtMgm::getPluginNamespace(Zfext_Plugin::getInstance()->prefixId);
		
		if ($this->_defaultModule != $moduleName || $this->_prefixDefaultModule) {
			$formatedClass .= '_'.$this->formatModuleName($moduleName);
		}
		
		$formatedClass .= '_'.$className;
		
		return $formatedClass;
	}
	
	/* (non-PHPdoc)
	 * @see Controller/Dispatcher/Zend_Controller_Dispatcher_Standard#classToFilename()
	 */
	public function classToFilename($class)
	{
	    $parts = explode('_', $class);
	    return array_pop($parts).'.php';
	}
}
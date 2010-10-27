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
 * Resource that sets the plugin options to Zfext_ExtMgm - use
 * config.tx_zfext.resources.zfext.{pluginKey} to set the options.
 * 
 * @category   TYPO3
 * @package    Zend_Application
 * @subpackage Resource
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zend_Application_Resource_Zfext extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * The method thats called from the bootstraper
     */
    public function init()
    {
        $options = $this->getOptions();
        if (isset($options['autoloadNamespaces']))
        {
            $this->_registerNamespaces((string) $options['autoloadNamespaces']);
            unset($options['autoloadNamespaces']);
        }
        
        //Not needed anymore - ExtMgm gathers options itself
        //Zfext_ExtMgm::setPluginOptions($options);
    }
    
    /**
     * Registers a list of namespaces
     * 
     * @param string $nsList
     */
    protected function _registerNamespaces($nsList)
    {
        if (!strlen(trim($nsList,',')))
        {
            return;
        }
        $namespaces = array_unique(explode(',',$nsList));
        Zend_Loader_Autoloader::getInstance()->registerNamespace($namespaces);
    }
}
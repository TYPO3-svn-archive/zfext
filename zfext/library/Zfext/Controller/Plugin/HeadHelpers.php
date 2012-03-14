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
 * This frontcontroller plugin loops through the configured placeholder view helpers,
 * gathers theyr output and puts it in the additionalHeaderData-key for the prefix of
 * the current plugin. After that it resets the placeholder registry and restores it
 * when a particular plugin is called again.
 *
 * @category   TYPO3
 * @package    Zfext_Controller
 * @subpackage Plugin
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Controller_Plugin_HeadHelpers extends Zend_Controller_Plugin_Abstract
{
    /**
     * @var array List of helpers that should be treated here
     */
    protected $_helpers = array
    (
        'Zend_View_Helper_HeadLink',
        'Zend_View_Helper_HeadMeta',
        'Zend_View_Helper_HeadScript',
        'Zend_View_Helper_HeadStyle'
    );

    /**
     * Constructor - set the placeholder registry
     */
    public function __construct()
    {
    }

    /**
     * Restores the registry for a particular plugin if available
     *
     * @param Zend_Controller_Request_Abstract $request
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        $regKey = Zend_View_Helper_Placeholder_Registry::REGISTRY_KEY;
        $prefixId = Zfext_Plugin::getInstance()->prefixId;

        if (Zend_Registry::getInstance()->offsetExists($regKey.$prefixId)) {
            Zend_Registry::set($regKey, Zend_Registry::get($regKey.$prefixId));
        }
    }

    /**
     * Gets the contents of the configured helpers, puts it in tslib_fe->
     * additonalHeaderData, stores current placeholder registry to another key
     * in registry and unsets the regular one.
     * Replaces EXT:extkey with t3lib_extMgm::siteRelPath(extkey).
     */
    public function dispatchLoopShutdown()
    {
        $headItems = array();
        $containers = array();

        $regKey = Zend_View_Helper_Placeholder_Registry::REGISTRY_KEY;
        $prefixId = Zfext_Plugin::getInstance()->prefixId;
        $registry = Zend_Registry::get($regKey);

        foreach ($this->_helpers as $helperName) {
            if (!$registry->containerExists($helperName)) {
                continue;
            }

            /* @var $helper Zend_View_Helper_Placeholder_Container_Standalone */
            $helper = new $helperName;
            $headItems[] = $helper->toString();
        }

        Zend_Registry::set($regKey.$prefixId, $registry);
        Zend_Registry::getInstance()->offsetUnset($regKey);

        $headerData = implode(PHP_EOL, $headItems);

        $GLOBALS['TSFE']->additionalHeaderData[Zfext_Plugin::getInstance()->prefixId] =
        preg_replace('/EXT:(.*?)\/(.*?)/e', 't3lib_extMgm::siteRelPath($1)$2', $headerData);
    }
}
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
 * @version    $Id: Typo3.php 62259 2012-05-15 14:13:12Z metti $
 */

/**
 * Class to access zend view helpers from Fluid
 *
 * @category   TYPO3
 * @package    View_Fluid
 * @subpackage Helpers
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_View_Fluid_Helpers
{
    /**
     * @var Zend_View_Interface
     */
    protected $_view;

    /**
     * @var Zfext_View_Fluid_Helpers
     */
    protected static $_instance;

    /**
     * Set current instance, view and load translation data to extbase
     *
     * @param Zend_View_Interface $view
     */
    public function __construct(Zend_View_Interface $view)
    {
        $this->_view = $view;
        self::$_instance = $this;

        if (class_exists('Tx_Extbase_Utility_Localization')) {
            // Load the locallang.xml to Extbase localization utility
            Zfext_View_Fluid_Helpers_Translate_ExtbaseBridge::load(Zfext_Plugin::getInstance()->extKey);
        }
    }

    /**
     * @return Zend_View_Interface
     */
    public function getView()
    {
        return $this->_view;
    }

    /**
     * I hate things like that but as injecting anything to Fluid is so f**cking
     * hard this is the by far easiest way to access Zend view helpers from Fluid
     * view helpers.
     * (Yeah, I know - I could assign a helpers object ($this) to fluid and get it
     * from helpers and that's what I did and do but
     * <code>
     * $this->templateVariableContainer->get(Zfext_View_Fluid::HELPERS_KEY)
     * </code>
     * doesn't work within sections - dough!
     *
     * @return Zfext_View_Fluid_Helpers
     */
    public static function getCurrentInstance()
    {
        return self::$_instance;
    }

    /**
     * Called from
     * @see Tx_Extbase_Reflection_ObjectAccess::getProperty()
     * @see Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode::getPropertyPath()
     *
     * @param string $method
     * @param array $args
     * @throws Zfext_Exception
     */
    public function __call($method, $args)
    {
        if (substr($method, 0, 3) != 'get') {
            throw new Zfext_Exception('Unrecognized usage');
        }
        $helper = substr($method, 3);
        return $this->_view->{$helper}();
    }
}
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
 * Extension of Fluids standalone view
 *
 * @internal
 * @category   TYPO3
 * @package    Zend_View
 * @subpackage Fluid
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_View_Fluid_View extends Tx_Fluid_View_StandaloneView
{
    public function __construct()
    {
        parent::__construct();
        $extensionName = t3lib_div::underscoredToLowerCamelCase(Zfext_Plugin::getInstance()->extKey);
        $this->controllerContext->getRequest()->setControllerExtensionName($extensionName);
    }

    /**
     * @internal
     * @return Tx_Fluid_Core_ViewHelper_TemplateVariableContainer
     */
    public function getTemplateVariableContainer()
    {
        return $this->baseRenderingContext->getTemplateVariableContainer();
    }
}
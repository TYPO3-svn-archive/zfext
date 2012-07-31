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
 * @category   Fluid
 * @package    ViewHelper
 * @subpackage Helper
 * @author     Christian Opitz <co@netzelf.de>
 */
class Tx_Zfext_ViewHelper_Head_ScriptViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper
{
    /**
     * @param  string $mode Script or file
     * @param  string $src Script/url
     * @param  string $placement Append, prepend, or set
     * @param  array $attribs Array of script attributes
     * @param  string $type Script type and/or array of script attributes
     */
    public function render($mode = Zend_View_Helper_HeadScript::FILE, $src = null, $placement = 'APPEND', array $attribs = array(), $type = 'text/javascript')
    {
        Zfext_View_Fluid_Helpers::getCurrentInstance()->getView()->headScript($mode, $src, $placement, $attribs, $type);
    }
}
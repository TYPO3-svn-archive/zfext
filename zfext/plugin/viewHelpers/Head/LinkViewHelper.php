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
class Tx_Zfext_ViewHelper_Head_LinkViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper
{
    /**
     * Add/set a link to the HEAD section
     *
     * @param string $position Where to inject
     * @param string $rel stylesheet, alternate or sth. else
     * @param string $href The source of the link
     * @param string $hreflang HTML-Attribute
     * @param string $charset HTML-Attribute
     * @param string $id HTML-Attribute
     * @param string $media HTML-Attribute
     * @param string $rev HTML-Attribute
     * @param string $type HTML-Attribute
     * @param string $title HTML-Attribute
     * @param string $extras HTML-Attribute
     * @return string
     */
    public function render($position = 'append', $rel = null, $href = null, $hreflang = null, $charset = null, $id = null, $media = null, $rev = null, $type = null, $title = null, $extras = null)
    {
        $item = new stdClass();
        foreach (array('charset', 'href', 'hreflang', 'id', 'media', 'rel', 'rev', 'type', 'title', 'extras') as $var) {
            if ($$var !== null) {
                $item->$var = $$var;
            }
        }

        $helper = Zfext_View_Fluid_Helpers::getCurrentInstance()->getView()->headLink();

        switch ($position) {
            case 'prepend':
            case 'append':
            case 'set':
                $helper->{$position}($item);
                break;
            default:
                $helper->offsetSet($position, $item);
                break;
        }

        return '';
    }
}
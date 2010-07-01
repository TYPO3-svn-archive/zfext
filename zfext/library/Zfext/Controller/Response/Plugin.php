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
 * Extending the regular response to avoid output with echo
 * 
 * @category   TYPO3
 * @package    Zfext_Controller
 * @subpackage Response
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Controller_Response_Plugin extends Zend_Controller_Response_Abstract
{
	/**
     * Avoid echo
     */
	public function outputBody() {}
	
	/**
     * Return the body content
     *
     * If $spec is false, returns the concatenated values of the body content
     * array. If $spec is boolean true, returns the body content array. If
     * $spec is a string and matches a named segment, returns the contents of
     * that segment; otherwise, returns null.
     *
     * @param boolean $spec
     * @return string|array|null
     */
	public function getBody($spec = false)
	{
		if (false === $spec) {
			return implode('', $this->_body);
		}else{
			return parent::getBody($spec);
		}
	}
	
	/* (non-PHPdoc)
	 * @see Controller/Response/Zend_Controller_Response_Abstract#clearBody()
	 */
	public function clearBody($name = null)
	{
	    if ($name === null){
	        $this->_exceptions = array();
	    }
	    return parent::clearBody($name);
	}
}
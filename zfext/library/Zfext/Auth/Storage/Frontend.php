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
 * @version    $Id: Errorhandler.php 64684 2012-07-18 18:23:45Z metti $
 */

/**
 * Storage for TYPO3 frontend session - proxy to $GLOBALS['TSFE']->fe_user
 *
 * @category   TYPO3
 * @package    Zfext_Auth
 * @subpackage Adapter
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Auth_Storage_Frontend implements Zend_Auth_Storage_Interface
{
    /* (non-PHPdoc)
     * @see Zend_Auth_Storage_Interface::isEmpty()
     */
    public function isEmpty()
    {
        return $GLOBALS['TSFE']->fe_user->user ? false : true;
    }

    /* (non-PHPdoc)
     * @see Zend_Auth_Storage_Interface::read()
     */
    public function read()
    {
        return $GLOBALS['TSFE']->fe_user->user;
    }

    /* (non-PHPdoc)
     * @see Zend_Auth_Storage_Interface::write()
     */
    public function write($contents)
    {
        $GLOBALS['TSFE']->fe_user->user = $contents;
    }

    /* (non-PHPdoc)
     * @see Zend_Auth_Storage_Interface::clear()
     */
    public function clear()
    {
        $trace = debug_backtrace();
        if ($trace[1]['class'] == 'Zend_Auth' && $trace[1]['function'] == 'clearIdentity' &&
            $trace[2]['class'] == 'Zend_Auth' && $trace[2]['function'] == 'authenticate') {
            // This is probably a call right after authentication which would
            // log out the user just after he was loged in
            // @see Zend_Auth::authenticate()
            return;
        }
        $GLOBALS['TSFE']->fe_user->logoff();
    }
}
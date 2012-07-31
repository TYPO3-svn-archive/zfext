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
 * Adapter to authenticate an user with TYPO3
 *
 * @category   TYPO3
 * @package    Zfext_Auth
 * @subpackage Adapter
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Auth_Adapter_Frontend implements Zend_Auth_Adapter_Interface
{
    /**
     * @var string
     */
    protected $_identity;

    /**
     * @var string
     */
    protected $_credential;

	/* (non-PHPdoc)
     * @see Zend_Auth_Adapter_Interface::authenticate()
     */
    public function authenticate()
    {
        /* @var $feUser tslib_feUserAuth */
        $feUser = $GLOBALS['TSFE']->fe_user;
        $feUser->checkPid = ''; //do not use a particular pid
        $feUser->getMethodEnabled = true;

        $_GET[$feUser->formfield_uident] = $this->_credential;
        $_GET[$feUser->formfield_uname] = $this->_identity;
        $_GET[$feUser->formfield_status] = 'login';

        $feUser->start();
		$feUser->unpack_uc('');
		$feUser->fetchSessionData();

		unset($_GET[$feUser->formfield_uident], $_GET[$feUser->formfield_uname], $_GET[$feUser->formfield_status]);

        return new Zend_Auth_Result($feUser->user ? Zend_Auth_Result::SUCCESS : Zend_Auth_Result::FAILURE, $feUser->user);
    }

	/**
     * @param string $identity
     * @return Zfext_Auth_Adapter_Frontend
     */
    public function setIdentity($identity)
    {
        $this->_identity = $identity;
        return $this;
    }

	/**
     * @param string $credential
     * @return Zfext_Auth_Adapter_Frontend
     */
    public function setCredential($credential)
    {
        $this->_credential = $credential;
        return $this;
    }



}
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

require_once(PATH_tslib.'class.tslib_fe.php');
require_once(PATH_tslib.'class.tslib_content.php');
require_once(PATH_t3lib.'class.t3lib_userauth.php');
require_once(PATH_t3lib.'class.t3lib_page.php');
require_once(PATH_t3lib.'class.t3lib_befunc.php');
require_once(PATH_tslib.'class.tslib_feuserauth.php');
require_once(t3lib_extMgm::extPath('zfext').'plugin/class.tx_zfext.php');

/**
 * @category   TYPO3
 * @package    TYPO3
 * @subpackage tx_zfext
 * @author     Christian Opitz <co@netzelf.de>
 */
class tx_zfext_eid extends tx_zfext
{
    /**
     * @var tslib_fe
     */
    protected $_tsfe;

    /* (non-PHPdoc)
     * @see plugin/tx_zfext#main()
     */
    public function main()
    {
        $conf = t3lib_div::_GET('tx_zfext');
        if (empty($conf['eid'])) {
            return;
        }

        /* $this->_tsfe = $GLOBALS['TSFE'] = t3lib_div::makeInstance(
        	'tslib_fe',
            $GLOBALS['TYPO3_CONF_VARS'],
            (integer) t3lib_div::_GET('id'),
            (integer) t3lib_div::_GP('type')
        ); */
        //Compatible to 4.2:
        $temp_TSFEclassName = t3lib_div::makeInstanceClassName('tslib_fe');
		$this->_tsfe = $GLOBALS['TSFE'] = new $temp_TSFEclassName(
			$GLOBALS['TYPO3_CONF_VARS'],
            (integer) t3lib_div::_GET('id'),
            (integer) t3lib_div::_GP('type')
        );

        $this->_tsfe->connectToDB();
        $this->_tsfe->initTemplate();

        $this->_tsfe->initFEuser();
        $this->_tsfe->checkAlternativeIdMethods();
	    $this->_tsfe->determineId();
        $this->_tsfe->getPageAndRootline();

        //Seems to be needed prior to 4.3 to only:
        $this->_tsfe->getConfigArray();

        if (empty($this->_tsfe->tmpl->setup['plugin.'][$conf['eid'].'.']['zfext'])) {
	        return;
	    }

        $this->_tsfe->settingLanguage();
	    $this->_tsfe->settingLocale();

        $this->cObj = t3lib_div::makeInstance('tslib_cObj');
        if (!empty($conf['ceid'])) {
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_content', 'uid='.(int) $conf['ceid']);
            $this->cObj->data = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
        }

	    return parent::main('', $this->_tsfe->tmpl->setup['plugin.'][$conf['eid'].'.']);
    }

    /* (non-PHPdoc)
     * @see typo3/sysext/cms/tslib/tslib_pibase#pi_wrapInBaseClass()
     */
    public function pi_wrapInBaseClass($str)
    {
        return $str;
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/zfext/pi1/class.tx_zfext_eid.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/zfext/pi1/class.tx_zfext_eid.php']);
}
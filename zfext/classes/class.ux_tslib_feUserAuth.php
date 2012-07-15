<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Class to workarround http://forge.typo3.org/issues/29727
 * @author Christian Opitz <co@netzelf.de>
 */
class ux_tslib_feUserAuth extends tslib_feUserAuth {

    /* (non-PHPdoc)
     * @see tslib_feUserAuth::start()
     */
    public function start() {
        $v = explode('.', TYPO3_version);
        if ($v[0] == 4) {
            if ($v[1] == 3 && $v[2] >= 12 || $v[1] == 4 && $v[2] >= 9 || $v[1] == 5 && $v[2] >= 4 || $v[1] > 5) {
                //atm session_start() is always called so we have to too:
                Zfext_Manager::loadLibrary(Zfext_Manager::ZF_LIBRARY);
                $libraries = (array) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zfext']['libraries'];
                foreach ($libraries as $extKey => $options) {
                    Zfext_Manager::loadLibrary($extKey);
                }
                require_once 'Zend/Session.php';
                Zend_Session::start();
            }
        }
        return parent::start();
    }
}
<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Christian Opitz Netzelf
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
 * Extend realurl to allow dynamic post var sets without configuration
 *
 * @todo Find out, if this works with older versions of realurl than 1.9.4
 * @todo Find a way to trigger 404-error when there are unmatched post vars and
 * 		 there's no zfext-plugin on the current page or it doesn't match either
 *
 * @author Christian Opitz <co@netzelf.de>
 */
class ux_tx_realurl extends tx_realurl
{
    protected $_failureMode = null;

    protected $_unmatchedPathParts = null;

    /* (non-PHPdoc)
     * @see tx_realurl::decodeSpURL_settingPostVarSets()
     */
    protected function decodeSpURL_settingPostVarSets(&$pathParts, $postVarSetCfg) {
        $this->_failureMode = $this->extConf['init']['postVarSet_failureMode'];
        $this->extConf['init']['postVarSet_failureMode'] = 'ignore';
        $result = parent::decodeSpURL_settingPostVarSets($pathParts, $postVarSetCfg);
        // @see Zfext_Controller_Router_Typo3::route()
        $GLOBALS['ux_tx_realurl.unmatchedPath'] = implode('/', $pathParts);
        $pathParts = array();
        return $result;
    }

    /* (non-PHPdoc)
     * @see tx_realurl::encodeSpURL_gettingPostVarSets()
     */
    protected function encodeSpURL_gettingPostVarSets(&$paramKeyValues, &$pathParts, $postVarSetCfg) {
        // Save current state and let realurl assemble
        $previous = array_values($pathParts);
        $result = parent::encodeSpURL_gettingPostVarSets($paramKeyValues, $pathParts, $postVarSetCfg);

        if (class_exists('Zfext_Plugin') && Zfext_Plugin::getInstance()) {
            // Get the newly created path parts
            $postParts = array_slice(array_values($pathParts), count($previous));
            $prefixId = Zfext_Plugin::getInstance()->prefixId;
            $request = Zend_Controller_Front::getInstance()->getRequest();
            // Inject module/controller/action BEFORE the post vars
            foreach (array('Module', 'Controller', 'Action') as $key) {
                $key = $prefixId.'['.$request->{'get'.$key.'Key'}().']';
                if (isset($paramKeyValues[$key])) {
                    $previous[] = rawurlencode($paramKeyValues[$key]);
                    unset($paramKeyValues[$key]);
                }
            }
            // Inject other, unmatched params AFTER the post vars
            foreach ($paramKeyValues as $key => $value) {
                if (preg_match('/'.$prefixId.'\[([^\]]+)\]/', $key, $match)) {
                    $postParts[] = rawurlencode($match[1]);
                    $postParts[] = rawurlencode($value);
                    unset($paramKeyValues[$key]);
                }
            }
            // And put it all together
            $pathParts = array_merge($previous, $postParts);
        }
        return $result;
    }
}
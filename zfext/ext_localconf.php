<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

//t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_zfext_pi1.php', '_pi1', '', 0);

include_once(t3lib_extMgm::extPath('zfext').'library/Zfext/ExtMgm.php');
Zfext_ExtMgm::addLibrary($_EXTKEY, 'library');

$TYPO3_CONF_VARS['FE']['eID_include']['zfext'] = 'EXT:zfext/plugin/index.php';

// @see http://forge.typo3.org/issues/29727
$GLOBALS['TYPO3_CONF_VARS']['FE']['XCLASS']['tslib/class.tslib_feuserauth.php'] = t3lib_extMgm::extPath($_EXTKEY).'classes/class.ux_tslib_feUserAuth.php';
?>
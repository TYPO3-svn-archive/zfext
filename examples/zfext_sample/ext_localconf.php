<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

include_once(t3lib_extMgm::extPath('zfext').'library/ZfExt/ExtMgm.php');

Zfext_ExtMgm::addPItoST43($_EXTKEY, array(
	'directory' => 'pi1',
	'suffix' => '_pi1',
	'cached' => false
));
?>
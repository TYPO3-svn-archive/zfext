<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

//t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_zfext_pi1.php', '_pi1', '', 0);

include_once(t3lib_extMgm::extPath('zfext').'library/ZfExt/ExtMgm.php');

Zfext_ExtMgm::addPItoST43($_EXTKEY, array(
	'directory' => 'plugin',
	'suffix' => '',
	'cached' => true,
	'autoloader' => false
));

Zfext_ExtMgm::addLibrary($_EXTKEY, 'library', true);
?>
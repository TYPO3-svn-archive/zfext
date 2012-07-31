<?php
if (!defined ('TYPO3_MODE')) {
 	die ('Access denied.');
}

//t3lib_extMgm::addPItoST43($_EXTKEY, 'pi1/class.tx_zfext_pi1.php', '_pi1', '', 0);

include_once(t3lib_extMgm::extPath('zfext').'library/Zfext/Manager.php');
Zfext_Manager::addLibrary($_EXTKEY, 'library');

$TYPO3_CONF_VARS['FE']['eID_include']['zfext'] = 'EXT:zfext/plugin/index.php';

// @see http://forge.typo3.org/issues/29727
$GLOBALS['TYPO3_CONF_VARS']['FE']['XCLASS']['tslib/class.tslib_feuserauth.php'] = t3lib_extMgm::extPath($_EXTKEY).'classes/class.ux_tslib_feUserAuth.php';

// Rewriting currently causes more problems than it solves :(
#$GLOBALS['TYPO3_CONF_VARS']['FE']['XCLASS']['ext/realurl/class.tx_realurl.php'] = t3lib_extMgm::extPath($_EXTKEY).'classes/class.ux_tx_realurl.php';
#$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting'][] = 'EXT:zfext/classes/class.tx_zfext_hooks.php:tx_zfext_hooks->isOutputting';

Zfext_Manager::configure($_EXTKEY, array(
	'bootstrap' => array(
        'path' => 'EXT:zfext/library/Zfext/Bootstrap.php',
        'class' => 'Zfext_Bootstrap'
    ),
	'pluginpaths' => array(
        'Zfext_Application_Resource' => 'EXT:zfext/library/Zfext/Application/Resource',
    ),
	'resources' => array(
        'view' => array(
            'helperPath' => array(
                'Netzelf_View_Helper' => 'EXT:zfext/library/Netzelf/View/Helper',
	            'Zfext_View_Helper' => 'EXT:zfext/library/Zfext/View/Helper',
            )
        ),
	    'errorhandler' => array(
            'enable' => true,
            'module' => 'zfext',
        ),
        'frontcontroller' => array(
            'controllerdirectory' => array(
                'zfext' => 'EXT:zfext/plugin/controllers'
            ),
            'defaultmodule' => 'default',
            'plugins' => array(
            	'pluginErrorHandler' => 'Zfext_Controller_Plugin_ErrorHandler', // Handles $TYPO3_CONF_VARS['SYS']['exceptionalErrors'] also
                'pluginAutoloader' => 'Zfext_Controller_Plugin_Autoloader',
                'pluginHeadHelpers' => 'Zfext_Controller_Plugin_HeadHelpers'
            )
        ),
        'db' => array(
            'adapter' => 'TYPO3',
            'params' => array(
                'adapterNamespace' => 'Zfext_Db_Adapter'
            )
        ),
        'locale' => array(
            'bindWithTypo3' => true,
            'force' => true
        ),
        'translate' => array(
            'adapter' => 'Zfext_Translate_Adapter_Typo3',
            'data' => 'default',
            'disableNotices' => true
        )
    )
));
?>
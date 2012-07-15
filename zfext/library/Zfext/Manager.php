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
 * @deprecated
 * @category   TYPO3
 * @package    Zfext
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_ExtMgm extends Zfext_Manager
{
}

/**
 * Everything related to extension configuration
 *
 * @category   TYPO3
 * @package    Zfext
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Manager
{
    const ZF_LIBRARY = 'zfLibrary';

	protected static $_loadedLibraries = array();

	protected static $_ignoreNamespaces = array('Zend','ZendX');

	protected static $_loadedConfigs = array();

	/**
	 * Registers a library for a given extension.
	 *
	 * When autoload is set to true, this will scan the specified directory for
	 * dirs that look like potential namespaces for autoloading (First letter of
	 * dir is uppercase and no dot) and add them to the autoloadNamespace-list.
	 * @see Zend_Application_Resource_Zfext#init()
	 *
	 * Options are:
	 * - autoload:
	 *   If potential libraries should be detected and registered to
	 *   Zend_Loader_Autoload (defaults to true)
	 * - loadAtSessionStart:
	 *   Load the libraries before a session is started. This is needed when you
	 *   put classes from that library into session (defaults to false)
	 *   (@link http://forge.typo3.org/issues/29932)
	 * - depends:
	 *   Comma separated list of libraries this library depends on - the Zend
	 *   library is loaded anyway, so you don't need to add it (defaults to '')
	 *
	 * @param string $extKey The extension key
	 * @param string $directory OPTIONAL The directory relative to the extension root
	 * @param array $options OPTIONAL Options - see above
	 */
	public static function addLibrary($extKey, $directory = null, $options = array())
	{
	    if (!is_array($options)) {
	        // Legacy - the third argument was autoload before:
	        $options = array('autoload' => $options);
	    }

	    $options['path'] = 'EXT:'.$extKey;
	    if ($directory != null && is_string($directory)) {
	        $directory = trim(str_replace("\\",'/',$directory), '/');
	        $options['path'] .= '/'.$directory;
	    }

	    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zfext']['libraries'][$extKey] = $options;
	}

	/**
	 * Add a library to the include path and init autoload for it if required -
	 * loads Zend Framework when added with Zfext_Manager::addLibrary
	 *
	 * @param string $extKey Extension key or the ZF library only when Zfext_Manager::ZF_LIBRARY
	 */
	public static function loadLibrary($extKey)
	{
	    if ($extKey != self::ZF_LIBRARY) {
	        self::loadLibrary(self::ZF_LIBRARY);
	    }

	    if (self::isLibraryLoaded($extKey)) {
	        return;
	    }

		$options = (array) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zfext']['libraries'][$extKey];
		if (empty($options)) {
		    return;
		}
		$options += array(
		    'autoload' => true,
		    'loadAtSessionStart' => false,
		    'depends' => ''
		);

		if ($options['depends']) {
		    $depends = explode(',', (string) $options['depends']);
		    foreach ($depends as $key) {
		        if ($key != $extKey) {
		            self::loadLibrary($key);
		        }
		    }
		}

		$path = t3lib_div::getFileAbsFileName($options['path']);
		set_include_path(implode(PATH_SEPARATOR, array($path, get_include_path())));

		if ($options['autoload'] && $extKey != self::ZF_LIBRARY) {
		    try {
	            $iterator = new DirectoryIterator($path);
	        }catch(Exception $e) {
	            $iterator = array();
	        }
	        $namespaces = array();
	        foreach ($iterator as $item) {
	            if ($item->isDir()) {
	                $first = substr($item->getFilename(),0,1);
	                if (preg_match('/[A-Z]/',$first) &&
	                    !in_array($item->getFilename(), self::$_ignoreNamespaces)) {
	                    $namespaces[] = $item->getFilename();
	                }
	            }
	        }
        	if (count($namespaces)) {
        		require_once 'Zend/Loader/Autoloader.php';
		        Zend_Loader_Autoloader::getInstance()->registerNamespace($namespaces);
        	}
		}

		self::$_loadedLibraries[$extKey] = 1;
	}

	/**
	 * Returns if this particular library is already loaded
	 *
	 * @param string $extKey Extension key or the ZF library only when Zfext_Manager::ZF_LIBRARY
	 * @return boolean
	 */
	public static function isLibraryLoaded($extKey)
	{
	    return array_key_exists($extKey, self::$_loadedLibraries);
	}

	/**
	 * Adds an cliKey to $TYPO3_CONF_VARS and registers the manifest(s)
	 *
	 * @param string $extKey Extension key (required to load libraries for this cliKey)
	 * @param string $cliKey CLI-Key
	 * @param string|array $manifest Manifest classname or array of manifest classnames
	 * @param string $user The _cli_*-BE-user required for this cliKey
	 */
	public static function addToolManifest($extKey, $cliKey, $manifest, $user)
	{
	    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][$cliKey] = array(
			'EXT:zfext/cli/dispatch.php',
			$user
		);
		$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zfext']['toolManifests'][$cliKey] = array(
		    'extKey' => $extKey,
		    'manifest' => $manifest
		);
	}

	/**
	 * Returns canonicalized absolute pathname for TYPO3-paths
	 *
	 * @param string $path
	 * @return string
	 */
	public static function realpath($path)
	{
		// Check if path is extension root path (EXT:extKey) because t3lib_div::getFileAbsFileName
		// does not return anything for that :S
		if (substr($path, 0, 4) == 'EXT:' && strpos($extKey = trim(substr($path, 4),'/'), '/') === false) {
			$path = t3lib_extMgm::extPath($extKey);
		}else{
			$path = t3lib_div::getFileAbsFileName($path);
		}
		return realpath($path);
	}

	/**
	 * Proxy for @link t3lib_extMgm::addPItoST43() - Generates the
	 * setup that will proxy plugins over @link tx_zfext::main()
	 *
	 * @param string $extKey The extension key
	 * @param array $options
	 */
	public static function addPlugin($extKey, array $options = array())
	{
		$options = array_merge(
		    array(
        		'suffix' => '',
        		'type' => 'list_type',
        		'cached' => false,
        		'defaults' => array(),
        	),
			$options
		);
		$extKey = strtolower($extKey);
		self::_addPiToSt43(
			$extKey,
			'',
			$options['suffix'],
			$options['type'],
			$options['cached']
		);

		$deprecatedKeys = array('directory', 'moduleDirectory', 'modules', 'controllerDirectory', 'defaultModule', 'prefixDefaultModule');
		foreach ($deprecatedKeys as $deprecatedKey) {
		    if (isset($options[$deprecatedKey])) {
		        t3lib_div::deprecationLog('Option "'.$deprecatedKey.'" is deprecated in '.__CLASS__.'::'.__METHOD__);
		    }
		}

		foreach (array('Module', 'Controller', 'Action') as $key) {
		    if (isset($options['default'.$key])) {
		        $options['defaults'][strtolower($key)] = $options['default'.$key];
		    }
		}

		if (count($options['defaults'])) {
	        $prefixId = t3lib_extMgm::getCN($extKey).$options['suffix'];
		    t3lib_extMgm::addTypoScript($extKey, $prefixId.'._DEFAULT_PI_VARS '.self::_array2ts($options['defaults']));
		}
	}

	protected static function _array2ts($array)
	{
	    $ts = '{';
	    foreach ($array as $key => $value) {
	        $ts .= "\n".$key.' ';
	        $ts .= is_array($value) ? self::_array2ts($value) : '= '.$value;
	    }
	    return $ts."\n}";
	}

	protected static function _addPiToSt43($key, $classFile = '', $prefix = '', $type = 'list_type', $cached = 0)
	{
	    global $TYPO3_LOADED_EXT;

	    $prefixId = t3lib_extMgm::getCN($key).$prefix;
	    $comment = '# Setting '.$key.' plugin TypoScript';
	    $EOL = "\n";

			// General plugin:
		t3lib_extMgm::addTypoScript(
		    $key,
		    'setup',
		    $comment.$EOL.
    		'plugin.'.$prefixId.' = USER'.($cached ? '' : '_INT').$EOL.
            'plugin.'.$prefixId.' {'.$EOL.
            'includeLibs = '.$TYPO3_LOADED_EXT['zfext']['siteRelPath'].'plugin/class.tx_zfext.php'.$EOL.
            'userFunc = tx_zfext->main'.$EOL.
		    '# ZfExt related settings - dont\'t touch this unless you know what you\'re doing!'.$EOL.
		    'zfext.signature = '.$key.'.'.$prefixId.$EOL.
            '}'
		);

			// After ST43:
		switch($type) {
			case 'list_type':
				$addLine = 'tt_content.list.20.'.$key.$prefix.' = < plugin.'.$prefixId;
			break;
			case 'menu_type':
				$addLine = 'tt_content.menu.20.'.$key.$prefix.' = < plugin.'.$prefixId;
			break;
			case 'splash_layout':
				$addLine = 'tt_content.splash.'.$key.$prefix.' = < plugin.'.$prefixId;
			break;
			case 'CType':
				$addLine =
				'tt_content.'.$key.$prefix.' = COA'.$EOL.
                'tt_content.'.$key.$prefix.' {'.$EOL.
				'10 = < lib.stdheader'.$EOL.
                '20 = < plugin.'.$prefixId.$EOL.
                '}';
			break;
			case 'header_layout':
				$addLine = 'lib.stdheader.10.'.$key.$prefix.' = < plugin.'.$prefixId;
			break;
			case 'includeLib':
				$addLine = 'page.1000 = < plugin.'.$prefixId;
			break;
			default:
				$addLine = '';
			break;
		}
		if ($addLine) {
			t3lib_extMgm::addTypoScript($key, 'setup', $comment.$EOL.$addLine, 43);
		}
	}

	public static function getConfig($extKey)
	{
	    if (isset(self::$_loadedConfigs[$extKey])) {
	        return self::$_loadedConfigs[$extKey];
	    }
	    $configs = array();
	    $mode = TYPO3_MODE == 'BE' ? 'backend' : 'frontend';
	    foreach (array('default', $mode) as $key) {
    	    if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zfext']['config'][$extKey][$key])) {
    	        $configs[] = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zfext']['config'][$extKey][$key];
    	    }
	    }

	    if (!count($configs)) {
			throw new RuntimeException('Extension '.$extKey.' is not properly configured for ZFext in '.$mode.' mode');
	    }

	    $mergedConfig = ($extKey != 'zfext') ? self::getConfig('zfext') : array_shift($configs);

	    foreach ($configs as $config) {
	        $mergedConfig = t3lib_div::array_merge_recursive_overrule($mergedConfig, $config);
	    }

	    return self::$_loadedConfigs[$extKey] = self::_parseConfig($mergedConfig);
	}

	public static function configure($extKey, $modeOrConfig, array $config = array())
	{
	    if (is_array($modeOrConfig)) {
	        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zfext']['config'][$extKey]['default'] = $modeOrConfig;
	    } else {
	        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zfext']['config'][$extKey][$modeOrConfig] = $config;
	    }
	}

	protected function _parseConfig($config)
	{
	    $parsed = array();
		foreach ($config as $key => $value) {
			if (is_array($value)) {
				$parsed[$key] = self::_parseConfig($value);
			} else {
			    if ($value === null) {
			        // Assume $key should be removed
			        continue;
			    }
				if (is_string($value) && strpos($value, 'EXT:') === 0) {
					$pathParts = explode('/', substr($value,4));
					$value = t3lib_extMgm::extPath(array_shift($pathParts));
					$value .= implode(DIRECTORY_SEPARATOR,$pathParts);
				}
				$parsed[$key] = $value;
			}
		}
		return $parsed;
	}

	/**
	 * Registers an Extbase module (main or sub) to the backend interface.
	 * FOR USE IN ext_tables.php FILES
	 *
	 * (adapted from Tx_Extbase_Utility_Extension::registerModule)
	 *
	 * @param string $extensionName The extension name (in UpperCamelCase) or the extension key (in lower_underscore)
	 * @param string $mainModuleName The main module key, $sub is the submodule key. So $main would be an index in the $TBE_MODULES array and $sub could be an element in the lists there. If $main is not set a blank $extensionName module is created
	 * @param string $subModuleName The submodule key. If $sub is not set a blank $main module is created
	 * @param string $position This can be used to set the position of the $sub module within the list of existing submodules for the main module. $position has this syntax: [cmd]:[submodule-key]. cmd can be "after", "before" or "top" (or blank which is default). If "after"/"before" then submodule will be inserted after/before the existing submodule with [submodule-key] if found. If not found, the bottom of list. If "top" the module is inserted in the top of the submodule list.
	 * @param array $moduleConfiguration The configuration options of the module (icon, locallang.xml file)
	 * @return void
	 */
	public static function addModule($extensionName, $mainModuleName = '', $subModuleName = '', $position = '', array $moduleConfiguration = array()) {
		require_once dirname(__FILE__).'/Module.php';
	    if (empty($extensionName)) {
			throw new InvalidArgumentException('The extension name must not be empty', 1239891989);
		}
		$extensionKey = t3lib_div::camelCaseToLowerCaseUnderscored($extensionName);
		$extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));

		$defaultModuleConfiguration = array(
			'access' => 'admin',
			'icon' => 'EXT:zfext/ext_icon.gif',
			'labels' => '',
			'extRelPath' => t3lib_extMgm::extRelPath($extensionKey),
		    'extKey' => $extensionKey
		);
		$moduleConfiguration = t3lib_div::array_merge_recursive_overrule($defaultModuleConfiguration, $moduleConfiguration);

		if ((strlen($mainModuleName) > 0) && !array_key_exists($mainModuleName, $GLOBALS['TBE_MODULES'])) {
			$mainModuleName = $extensionName . t3lib_div::underscoredToUpperCamelCase($mainModuleName);
		} else {
			$mainModuleName = (strlen($mainModuleName) > 0) ? $mainModuleName : 'web';
		}
		$moduleSignature = $mainModuleName;

		if ((strlen($subModuleName) > 0)) {
			$subModuleName = $extensionName . t3lib_div::underscoredToUpperCamelCase($subModuleName);
			$moduleSignature .= '_' . $subModuleName;
		}

		$moduleConfiguration['name'] = $moduleSignature;
		$moduleConfiguration['zfext'] = true;
		$moduleConfiguration['script'] = 'mod.php?M=' . rawurlencode($moduleSignature);
		$moduleConfiguration['extensionName'] = $extensionName;
		$moduleConfiguration['configureModuleFunction'] = array(__CLASS__, 'configureModule');

		$GLOBALS['TBE_MODULES']['_configuration'][$moduleSignature] = $moduleConfiguration;

		t3lib_extMgm::addModule($mainModuleName, $subModuleName, $position);
	}

	/**
	 * This method is called from t3lib_loadModules::checkMod and it replaces old conf.php.
	 *
	 * (adapted from Tx_Extbase_Utility_Extension::configureModule)
	 *
	 * @param string $moduleSignature The module name
	 * @param string $modulePath Absolute path to module (not used by Extbase currently)
	 * @return array Configuration of the module
	 */
	public function configureModule($moduleSignature, $modulePath) {
		$moduleConfiguration = $GLOBALS['TBE_MODULES']['_configuration'][$moduleSignature];
		$iconPathAndFilename = $moduleConfiguration['icon'];
		if (substr($iconPathAndFilename, 0, 4) === 'EXT:') {
			list($extensionKey, $relativePath) = explode('/', substr($iconPathAndFilename, 4), 2);
			$iconPathAndFilename = t3lib_extMgm::extPath($extensionKey) . $relativePath;
		}
		// TODO: skin support

		$moduleLabels = array(
			'tabs_images' => array(
				'tab' => $iconPathAndFilename,
			),
			'labels' => array(
				'tablabel' => $GLOBALS['LANG']->sL($moduleConfiguration['labels'] . ':mlang_labels_tablabel'),
				'tabdescr' => $GLOBALS['LANG']->sL($moduleConfiguration['labels'] . ':mlang_labels_tabdescr'),
			),
			'tabs' => array(
				'tab' => $GLOBALS['LANG']->sL($moduleConfiguration['labels'] . ':mlang_tabs_tab')
			)
		);
		$GLOBALS['LANG']->addModuleLabels($moduleLabels, $moduleSignature . '_');

		return $moduleConfiguration;
	}
}
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
 * A little brother of t3lib_extMgm
 *
 * @category   TYPO3
 * @package    Zfext
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_ExtMgm
{
    const TS_PATH = 'plugin.tx_zfext';

    const ZF_LIBRARY = 'zfLibrary';

	/**
	 * @var array All plugin options
	 */
	protected static $_pluginOptions = array();

	protected static $_loadedLibraries = array();

	/**
	 * @var array Default attributes for addPItoST43()
	 */
	protected static $_defaultPluginAttributes = array
	(
		'directory' => '',
		'suffix' => '',
		'type' => 'list_type',
		'cached' => false,
		'controllerDirectory' => 'controllers',
	    'modulesDirectory' => false,
		'modules' => false
	);

	/**
	 * @var array Default options - added to zfext-resource if altered
	 */
	protected static $_defaultPluginOptions = array
	(
		'defaultModule' => null,
		'defaultController' => null,
		'defaultAction' => null,
	    'namespace' => null, //Can be overriden
		'suffixInClassName' => true,
		'autoloader' => true,
		'prefixDefaultModule' => false
	);

	protected static $_ignoreNamespaces = array('Zend','ZendX');

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
	 * loads Zend Framework when added with Zfext_ExtMgm::addLibrary
	 *
	 * @param string $extKey Extension key or the ZF library only when Zfext_ExtMgm::ZF_LIBRARY
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
	 * @param string $extKey Extension key or the ZF library only when Zfext_ExtMgm::ZF_LIBRARY
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
	 * @param array $options Options and arguments (@see $_defaultPluginAttributes
	 * and $_defaultPluginOptions)
	 */
	public static function addPlugin($extKey, array $options = array())
	{
		$options = array_merge(
			self::$_defaultPluginAttributes,
			$options
		);
		$extKey = strtolower($extKey);

		$prefixId = t3lib_extMgm::getCN($extKey).$options['suffix'];

		self::_addPiToSt43(
			$extKey,
			'',
			$options['suffix'],
			$options['type'],
			$options['cached']
		);

		$pluginOptions = array_intersect_key(
			$options,
			self::$_defaultPluginOptions
		);

		$setup = "plugin.{$prefixId}.zfext {\n";

		$dir = trim(str_replace('\\', '/', $options['directory']), "/");
		$setup .= 'resources.frontcontroller.';
		if ($options['modules'] && !$options['moduleDirectory']) {
			$setup .= 'moduledirectory = EXT:'.$extKey.'/'.$dir;
		}else{
			$cDir = trim(str_replace('\\', '/', $options['controllerDirectory']), "/");
			$setup .= 'controllerdirectory = EXT:'.$extKey.'/'.$dir.'/'.$cDir;
		    if ($options['moduleDirectory']) {
		        $mDir = is_string($options['moduleDirectory']) ? trim(str_replace('\\', '/', $options['moduleDirectory']), "/") : 'modules';
			    $setup .= "\nresources.frontcontroller.moduledirectory = EXT:".$extKey.'/'.$dir.'/'.$mDir;
		    }
		}

		if (!empty($options['defaultModule'])) {
			$setup .= "\nresources.frontcontroller.defaultmodule = ".$options['defaultModule'];
		}

		if (!empty($options['prefixDefaultModule'])) {
			$setup .= "\nresources.frontcontroller.params.prefixDefaultModule = ".$options['prefixDefaultModule'];
		}

		if (count($pluginOptions)) {
			foreach ($pluginOptions as $key => $value)
			{
				$setup .= "\n".$key.' = '.($value === false ? 0 : strval($value));
			}
		}
		$setup .= "\n}";

		t3lib_extMgm::addTypoScriptSetup($setup);
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
		    (($key != 'zfext') ? 'zfext = < '.self::TS_PATH.'.zfext'.$EOL : '').
		    'zfext.signature = '.$key.'.'.$prefixId.$EOL.
            '}'
		);

			// After ST43:
		switch($type)
		{
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
		if ($addLine)
		{
			t3lib_extMgm::addTypoScript($key, 'setup', $comment.$EOL.$addLine, 43);
		}
	}

	/**
	 * Get the namespace for the plugin (fi. Tx_Zfext)
	 *
	 * @param string $key Extension key
	 * @param string|null $suffix The suffix to use (eg. _pi1)
	 * @return string
	 */
	public static function getPluginNamespace($prefixId)
	{
		$useNamespace = (string) self::getPluginOption($prefixId, 'namespace');
	    if (strlen($useNamespace))
	    {
	        return $useNamespace;
	    }


		$extKey = self::getPluginOption($prefixId, 'extKey');
	    $keyParts = explode('_', $extKey);
		if (strtolower($keyParts[0]) == 'tx')
		{
			unset($keyParts[0]);
		}
		$namespace = 'Tx_';
		foreach ($keyParts as $part)
		{
			$namespace .= ucfirst($part);
		}

		if (self::getPluginOption($prefixId, 'suffixInClassName'))
		{
			$cn = t3lib_extMgm::getCN($extKey);
		    $suffix = str_replace($cn, '', $prefixId);

		    if (strlen($suffix))
		    {
		        $namespace .= '_'.ucfirst(trim($suffix,'_'));
		    }
		}
		self::setPluginOption($prefixId, 'namespace', $namespace);
		return $namespace;
	}

	/**
	 * Sets all plugin options where the keys of the first level
	 * are the prefix ids and theyr values are the options for
	 * that plugin. Merges this options with the default options.
	 *
	 * @param array $options
	 */
	protected static function _checkPluginOptions($prefixId)
	{
		if (is_array(self::$_pluginOptions[$prefixId])) {
			return;
		}

		$options = (array) $GLOBALS['TSFE']->tmpl->setup['plugin.'][$prefixId.'.']['zfext.'];

		if (empty($options['signature'])) {
			throw new Zfext_Exception($prefixId.' is not a ZfExt-plugin!');
	    }

	    $parts = explode('.', $options['signature']);
	    $options['extKey'] = $parts[0];

		foreach (self::$_defaultPluginOptions as $key => $val)
		{
		    if (!empty($val) && !isset($options[$key])) {
		        $options[$key] = $val;
		    }
		}
	    self::$_pluginOptions[$prefixId] = $options;
	}

	/**
	 * Set an option for a specific plugin
	 *
	 * @param string $prefixId
	 * @param string $key Option key
	 * @param string $value Option value
	 */
	public static function setPluginOption($prefixId, $key, $value)
	{
	    self::_checkPluginOptions($prefixId);

		if (!is_array(self::$_pluginOptions[$prefixId]))
	    {
	        self::$_pluginOptions[$prefixId] = self::$_defaultPluginOptions;
	    }
	    self::$_pluginOptions[$prefixId][$key] = $value;
	}

	/**
	 * Returns the merged options for a plugin identified by prefixId
	 *
	 * @param string $prefixId
	 * @return array
	 */
	public static function getPluginOptions($prefixId, $filterOutEmpty = true)
	{
		self::_checkPluginOptions($prefixId);

		return self::$_pluginOptions[$prefixId];
	}

	/**
	 * Returns an option for a plugin identified by prefixId
	 *
	 * @param string $prefixId
	 * @param string $key
	 * @return mixed|null
	 */
	public static function getPluginOption($prefixId, $key)
	{
		self::_checkPluginOptions($prefixId);

	    if (!isset(self::$_pluginOptions[$prefixId][$key]))
	    {
	        return null;
	    }
		return self::$_pluginOptions[$prefixId][$key];
	}
}
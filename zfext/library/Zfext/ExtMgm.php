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
    
	/**
	 * @var array All plugin options
	 */
	protected static $_pluginOptions = array();
	
	protected static $_zfLoaded = false;
	
	/**
	 * @var array Default attributes for addPItoST43()
	 */
	protected static $_defaultPluginAttributes = array
	(
		'directory' => '',
		'suffix' => '',
		'type' => 'list_type',
		'cached' => false,
		'controllerDirectory' => 'controllers'
	);
	
	/**
	 * @var array Default options - added to zfext-resource if altered
	 */
	protected static $_defaultPluginOptions = array
	(
		'defaultController' => null,
		'defaultAction' => null,
	    'namespace' => null, //Can be overriden
		'suffixInClassName' => true,
		'autoloader' => true
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
	 * @param string $extKey The extension key
	 * @param string $directory OPTIONAL The directory relative to the extension root
	 * @param boolean $autoload OPTIONAL If potential libraries should be detected 
	 * 									 and registered to Zend_Loader_Autoload
	 */
	public static function addLibrary($extKey, $directory = null, $autoload = true)
	{
	    $libraryPath = 'EXT:'.$extKey;
	    //$libraryKey = t3lib_extMgm::getCN($extKey);
	    $libraryKey = $extKey;
	    if ($directory != null && is_string($directory))
	    {
	        $directory = trim(str_replace("\\",'/',$directory), '/');
	        //$libraryKey .= '_'.str_replace('/','_',$directory);
	        $libraryPath .= '/'.$directory;
	    }
	    
	    $setup = self::TS_PATH.'.includePaths.'.$libraryKey.' = '.$libraryPath;
	    if ($autoload)
	    {
	    	try {
	            $iterator = new DirectoryIterator(t3lib_extMgm::extPath($extKey).$directory);
	        }catch(Exception $e)
	        {
	            return;
	        }
	        $namespaces = array();
	        foreach ($iterator as $item)
	        {
	            if ($item->isDir())
	            {
	                $first = substr($item->getFilename(),0,1);
	                if (preg_match('/[A-Z]/',$first) && 
	                    !in_array($item->getFilename(), self::$_ignoreNamespaces))
	                {
	                    $namespaces[] = $item->getFilename();
	                }
	            }
	        }
	        if (count($namespaces))
	        {
	            $setup .= "\n".self::TS_PATH.'.autoloadNamespaces '.
	                      ':= addToList('.implode(',',$namespaces).')';
	        }
	    }
	    t3lib_extMgm::addTypoScriptSetup($setup);
	}
	
	public static function loadLibrary($extKey)
	{
		$paths = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_zfext.']['includePaths.'];
		$loadPaths = array();
		
		if (!self::$_zfLoaded) {
			if (!empty($paths['zfLibrary']) && is_string($paths['zfLibrary'])) {
				$loadPaths[] = realpath(t3lib_div::getFileAbsFileName($paths['zfLibrary']));
			}
		}
		
		if (!empty($extKey)) {
			$loadPaths[] = realpath(t3lib_div::getFileAbsFileName($paths[$extKey]));
		}
		
		if (count($loadPaths)) {
			$loadPaths[] = get_include_path();
			set_include_path(implode(PATH_SEPARATOR, $loadPaths));
		}
		
		if (!self::$_zfLoaded) {
			$nsList = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_zfext.']['autoloadNamespaces'];
        	if (strlen(trim($nsList,','))) {
        		require_once 'Zend/Loader/Autoloader.php';
		        $namespaces = array_unique(explode(',',$nsList));
		        Zend_Loader_Autoloader::getInstance()->registerNamespace($namespaces);
        	}
		}
		
		self::$_zfLoaded = true;
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
			trim($options['directory'],"\\/").'/class.'.$prefixId.'.php',
			$options['suffix'],
			$options['type'],
			$options['cached']
		);
		
		$pluginOptions = array_intersect_key(
			$options,
			self::$_defaultPluginOptions
		);
		
		$setup = "plugin.{$prefixId} {\n";
		
		//Add controller directory
		$setup .= 'zfext.resources.frontcontroller.controllerdirectory.'.
		$prefixId .' = EXT:'.$extKey.'/'.
		trim($options['directory'],"/\\").'/'.
		trim($options['controllerDirectory'],"/\\")."\n";
		
		if (count($pluginOptions)) {
			foreach ($pluginOptions as $key => $value)
			{
				$setup .= $key.' = '.($value === false ? 0 : strval($value))."\n";
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
		    if (!empty($val) && empty($options[$key])) {
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
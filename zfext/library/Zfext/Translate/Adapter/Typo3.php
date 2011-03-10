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
 * @category   TYPO3
 * @package    Zfext_Translate
 * @subpackage Adapter
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Translate_Adapter_Typo3 extends Zend_Translate_Adapter
{
    protected $_loadedPlugins = array();
    
    protected $_dataCharsets = array();
    
    protected $_langKeys;
    
    /**
     * Load translation data
     * 
     * @param  mixed              $data
     * @param  string|Zend_Locale $locale
     * @param  array              $options (optional)
     * @return array
     */
    protected function _loadTranslationData($data, $locale, array $options = array())
    {
        $langKeys = array(current(explode('_', $locale)));
        
        // Look up alternative languages and setup routing:
        if ($GLOBALS['TSFE']->config['config']['language_alt'])	{
            $langKeys[] = $GLOBALS['TSFE']->config['config']['language_alt'];
            $route = array(
                $langKeys[0] => $langKeys[1],
                $langKeys[1] => 'en'
            );
		}else{
		    $route = array($langKeys[0] => 'en');
		}
        $this->setOptions(array('route' =>$route));
		
        // Try to load the configured files
        if ($data === 'default') {
            $paths = array('locallang.xml');
        }elseif(is_string($data)) {
            $paths = array($paths);
        }elseif(is_array($data)) {
            $paths = $data;
        }else{
            throw new Zfext_Exception('No valid translation data provided');
        }
        
        $plugin = Zfext_Plugin::getInstance();
        $rootPath = t3lib_extMgm::extPath($plugin->extKey);
        $translationData = array();        
        
        foreach ($paths as $path) {
            $basePath = $rootPath.ltrim($path, '\\/');
            foreach ($langKeys as $langKey) {
		        $translationData = array_merge(
		            $translationData,
		            (array) t3lib_div::readLLfile($basePath, $langKey, $GLOBALS['TSFE']->renderCharset)
		        );
            }
        }
        
		// Overlaying labels from TypoScript (including fictitious language keys for non-system languages!):
		if (is_array($plugin->conf['_LOCAL_LANG.']))	{
			reset($plugin->conf['_LOCAL_LANG.']);
			while(list($k,$lA)=each($plugin->conf['_LOCAL_LANG.']))	{
				if (is_array($lA))	{
					$k = substr($k,0,-1);
					foreach($lA as $llK => $llV) {
						if (!is_array($llV)) {
						    // For labels coming from the TypoScript (database) the charset is assumed to be
						    // "forceCharset" and if that is not set, assumed to be that of the individual system languages
						    // TODO: Really invoke this in translator somehow:
						    if ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']) {
						        $translationDataCharsets[$k][$llK] = $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'];
						    }else{
						        $translationDataCharsets[$k][$llK] = $GLOBALS['TSFE']->csConvObj->charSetArray[$k];
						    }
							$translationData[$k][$llK] = $llV;
						}
					}
				}
			}
		}
		
		// Merge 'default' keys to english as Zend_Translate doesn't
		// accept 'default' as locale
		if (array_key_exists('default', $translationData)) {
		    if (array_key_exists('en', $translationData)) {
		        $translationData['en'] = array_merge(
		            $translationData['default'],
		            $translationData['en']
		        );
		    }else{
		        $translationData['en'] = $translationData['default'];
		    }
		    unset($translationData['default']);
		}
		
		// When $locale was full qualified, provide it:
		if ($langKeys[0] != $locale) {
		    $translationData[$locale] = (array) $translationData[$langKeys[0]];
		}
		
		return $translationData;
    }
    
    /**
     * Returns the adapter name
     *
     * @return string
     */
    public function toString()
    {
        return 'Typo3';
    }
}
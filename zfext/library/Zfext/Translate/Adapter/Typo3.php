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
    
    /**
     * Generates the adapter.
     *
     * @param  string		      $type    OPTIONAL Can be "simple" or "advanced"
     * @param  string|Zend_Locale $locale  OPTIONAL Locale/Language to set, identical with Locale
     *                                     identifiers see Zend_Locale for more information
     * @param  array              $options OPTIONAL Options for the adaptor
     * @throws Zend_Translate_Exception
     * @return void
     */
    public function __construct($type = null, $locale = null, array $options = array())
    {
        //noop
    }
    
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
        //noop
    }
    
    /**
     * Translates the given string and returns the translation
     *
     * @see Zend_Locale
     * @param  string|array $messageId Translation string
     * @param  string $alt Alternative string to return IF no value is found set for the key
     * @return string
     */
    public function translate($messageId, $alt = '')
    {
        if (is_array($messageId))
        {
            trigger_error('Plurals are not yet supported by TYPO3 translation adapter.', E_USER_NOTICE);
            $messageId = (string) $messageId[0];
        }
        
        if (!Zfext_Plugin::getInstance()->LOCAL_LANG_loaded)
        {
            $dir = Zend_Controller_Front::getInstance()->getDispatcher()->getControllerDirectory();
            $dir = realpath($dir.'/../');
            if (is_readable($dir.'/locallang.php') || is_readable($dir.'/locallang.xml')) {
        		Zfext_Plugin::getInstance()->pi_loadLL();
            }else{
            	Zfext_Plugin::getInstance()->LOCAL_LANG_loaded = true;
            }
        }
        
        return Zfext_Plugin::getInstance()->pi_getLL($messageId, $alt);
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
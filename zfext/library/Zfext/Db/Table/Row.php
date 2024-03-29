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
 * @package    Zend_Db
 * @subpackage Table
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Db_Table_Row extends Netzelf_Db_Table_Row
{
    public function __construct(array $config = array())
    {
        parent::__construct($config);
        $table = $this->getTable();
        if ($table instanceof Zfext_Db_Table && $table->autoTranslationEnabled()) {
            $this->translate();
        }
    }

    public function translate($languageUid = null)
    {
		global $TCA;
		$table = $this->getTable()->info(Zend_Db_Table_Abstract::NAME);

        if (!$TCA[$table] || !$TCA[$table]['ctrl']['languageField'] || !$TCA[$table]['ctrl']['transOrigPointerField']) {
            return false;
        }
        /* @var $tsfe tslib_fe */
        $tsfe = $GLOBALS['TSFE'];
        if ($languageUid === null) {
            $languageUid = $tsfe->sys_language_content;
        }
        if ($this->_data[$TCA[$table]['ctrl']['languageField']] != $languageUid) {
            $data = $this->_data;
            $translated = $tsfe->sys_page->getRecordOverlay(
                $this->getTable()->info('name'),
                $data,
                $languageUid,
                $tsfe->sys_language_mode
            );
            if (isset($translated['_LOCALIZED_UID'])) {
                $translated['uid'] = $translated['_LOCALIZED_UID'];
                unset($translated['_LOCALIZED_UID']);
                $this->_data = $translated;
                $this->_cache = array();
                return true;
            }
        }
        return false;
    }

    /* (non-PHPdoc)
     * @see Netzelf_Db_Table_Row::__get()
     */
    public function __get($columnName)
    {
        $value = parent::__get($columnName);

        if (array_key_exists($columnName, $this->_overloaded)) {
            // value is from a getter or reference
            return $value;
        }

        $cacheKey = '__get_'.$columnName.'_'.$value;
        if ($this->_cacheHas($cacheKey)) {
            return $this->_cacheGet($cacheKey);
        }

        // Postprocess the values from DB in respect to the TCA configs

        $columnName = $this->_transformColumn($columnName);
        $tableName = $this->getTable()->info('name');
        t3lib_div::loadTCA($tableName);
        $config = $GLOBALS['TCA'][$tableName]['columns'][$columnName]['config'];
        switch (true) {
            case $config['type'] == 'group' && $config['internal_type'] == 'file':
                // Files: Prefix them with the uploadfolder
                $value = $value !== '' && $value !== null ? explode(',', $value) : array();
                if ($config['uploadfolder']) {
                    foreach ($value as $i => $v) {
                        $value[$i] = $config['uploadfolder'].'/'.$v;
                    }
                }
                break;
            case $config['type'] == 'select' && !$config['foreign_table']:
                // Selects - translate 'em
                $value = $value !== '' && $value !== null ? explode(',', $value) : array();
                $items = array();
                if (TYPO3_MODE == 'FE') {
                    $beUser = $GLOBALS['BE_USER'];
                    $lang = $GLOBALS['LANG'];
                    $GLOBALS['LANG'] = t3lib_div::makeInstance('language');
                    $GLOBALS['LANG']->init($GLOBALS['TSFE']->config['config']['language']);
                    $GLOBALS['BE_USER'] = t3lib_div::makeInstance('t3lib_beUserAuth');
                }
                /* @var $tce t3lib_TCEforms */
                $tce = t3lib_div::makeInstance('t3lib_TCEforms');
                $selItems = $tce->addSelectOptionsToItemArray(
        			$tce->initItemArray(array('config' => $config)),
        			array('config' => $config),
        			$tce->setTSconfig($tableName, $columnName),
        			$columnName
        		);
                if (TYPO3_MODE == 'FE') {
                    $GLOBALS['LANG'] = $lang;
            		$GLOBALS['BE_USER'] = $beUser;
                }
        		foreach ($value as $i => $v) {
        		    foreach ($selItems as $item) {
        		        if ($item[1] == $v) {
        		            $value[$i] = new Netzelf_Object(array(
        		            	'data' => array('value' => $item[0], 'key' => $item[1]),
        		                'toStringKey' => 'key'
        		            ));
        		            break;
        		        }
        		    }
        		}
                break;
            case $config['type'] == 'input' && ($config['eval'] == 'date' || $config['eval'] == 'datetime'):
                if ($value) {
                    $value = new Netzelf_Date($value);
                    $value->setDefaultFormat($config['eval'] == 'datetime' ? Zend_Locale_Format::getDateTimeFormat() : Zend_Locale_Format::getDateFormat());
                } else {
                    $value = null;
                }
                break;
        }
        if ($config['maxitems'] < 2 && is_array($value)) {
            $value = array_shift($value);
        }
        return $this->_cacheSet($cacheKey, $value);
    }

    /* (non-PHPdoc)
     * @see Netzelf_Db_Table_Row::__set()
     */
    public function __set($columnName, $value)
    {
        /* if (method_exists($this, $method = 'set'.ucfirst($columnName))) {
            $this->{$method}($value);
            return;
        } */

        $tableName = $this->getTable()->info('name');
        t3lib_div::loadTCA($tableName);
        $config = $GLOBALS['TCA'][$tableName]['columns'][$columnName]['config'];
        switch (true) {
            case $config['type'] == 'input' && ($config['eval'] == 'date' || $config['eval'] == 'datetime') && $value:
                $date = new Zend_Date($value);
                $value = $date->toString('U');
                break;
        }

        parent::__set($columnName, $value);
    }
}
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
class Zfext_Db_Table extends Netzelf_Db_Table
{
    /**
     * Keep information about which table names belong to
     * which class names here
     * @var array
     */
    protected static $_tableToClassNameMap = array();

    /**
     * Keep information about which class names belong to
     * which table names here
     * @var array
     */
    protected static $_classToTableNameMap = array();

    /**
     * @var Zend_Db_Table_Definition
     */
    protected static $_tableDefinition;

    /**
     * Set row class for several TYPO3 specific enhancements
     * @var string
     */
    protected $_rowClass = 'Zfext_Db_Table_Row';

    /**
     * If the record should automatically be translated right after
     * instanciation
     * @var boolean
     */
    protected $_autoTranslation = true;

   /**
     * Constructor - automatically create table definition,
     * cache and pass it to parent
     *
     * @throws Zfext_Db_Table_Exception
     */
    public function __construct()
    {
        if (get_class($this) == __CLASS__) {
            throw new Zfext_Db_Table_Exception('You have to extend '.__CLASS__);
        }
        if (!self::$_tableDefinition) {
            self::$_tableDefinition = new Zend_Db_Table_Definition();
        }
        if (!$this->_name) {
            $this->_name = $this->_findTableName($this);
        }
        if (!self::$_tableDefinition->hasTableConfig($this->_name)) {
            $config = $this->_findReferences();
            self::$_tableDefinition->setTableConfig($this->_name, $config);
        }
        if ($this->_autoTranslation) {
            global $TCA;
            if ($TCA[$this->_name] && $TCA[$this->_name]['ctrl']['languageField'] && $TCA[$this->_name]['ctrl']['transOrigPointerField']) {
                array_unshift($this->_scopes, array(
                	'where' => $TCA[$this->_name]['ctrl']['languageField'].' = 0',
                    'default' => true
                ));
            } else {
                $this->_autoTranslation = false;
            }
        }
        parent::__construct(self::$_tableDefinition->getTableConfig($this->_name));
    }

    /**
     * Find references to and from other tables and create the dependent
     * tables and reference map array from those
     *
     * @todo Add support for MM tables     *
     * @throws Zfext_Db_Table_Exception
     * @return array
     */
    protected function _findReferences()
    {
        $name = $this->_name;
        global $TCA;
        t3lib_div::loadTCA($name);

        $parts = explode('_', $name);
        $prefix = ($parts[0] == 'tx') ? $parts[0].'_'.$parts[1].'_' : null;
        $prefixLength = strlen($prefix);

        $dependentTables = array();
        $referenceMap = array();

        if (!is_array($TCA[$name]['columns'])) {
            throw new Zfext_Db_Table_Exception('No column definitions found for table '.$name);
        }
        foreach ($TCA as $foreignTable => $config) {
            if ($foreignTable != $name) {
                t3lib_div::loadTCA($foreignTable);
            }
            foreach ((array) $TCA[$foreignTable]['columns'] as $refColumn => $refConfig) {
                $refConfig = $refConfig['config'];
                if ($refConfig['type'] == 'group' && $refConfig['internal_type'] == 'db' && $refConfig['allowed'] == $name ||
                    $refConfig['type'] == 'select' && $refConfig['foreign_table'] == $name) {
                    if ($refConfig['MM']) {
                        // Not yet supported
                        continue;
                    }
                    try {
                        $class = $this->_findTableClassName($foreignTable);
                    } catch (Exception $e) {
                        continue;
                    }
                    $key = ($prefix && substr($foreignTable, 0, $prefixLength) == $prefix) ? substr($foreignTable, $prefixLength) : $foreignTable;
                    $dependentTables[$key] = $class;
                    $referenceMap[ucfirst($key)] = array(
                        'columns' => array('uid'),
                        'refTableClass' => $class,
                        'refColumns' => array($refColumn),
                    	'operators' => 'IN'
                    );
                } else {
                    continue;
                }
            }
        }
        foreach ($TCA[$name]['columns'] as $column => $config) {
            $config = $config['config'];
            if ($config['type'] == 'group' && $config['internal_type'] == 'db' && !strpos($config['allowed'], ',')) {
                $foreignTable = $config['allowed'];
            } elseif ($config['type'] == 'select' && $config['foreign_table']) {
                $foreignTable = $config['foreign_table'];
            } else {
                continue;
            }
            if (!$foreignTable || $config['MM']) {
                // Not yet supported
                continue;
            }
            try {
                $class = $this->_findTableClassName($foreignTable);
            } catch (Exception $e) {
                continue;
            }
            if ($config['maxitems'] > 1) {
                $dependentTables[$column] = $class;
                $referenceMap[ucfirst($column).'Reverse'] = array(
                    'columns' => array($column),
                    'refTableClass' => $class,
                    'refColumns' => array('uid'),
                    'operators' => 'CONTAINS'
                );
            } else {
                $referenceMap[ucfirst($column)] = array(
                    'columns' => array($column),
                    'refTableClass' => $class,
                    'refColumns' => array('uid'),
                    'operators' => 'IN'
                );
            }
        }

        return array(
            Zend_Db_Table::DEPENDENT_TABLES => $dependentTables,
            Zend_Db_Table::REFERENCE_MAP => $referenceMap
        );
    }

    /**
     * Find the table name from the table class name.
     * Examples (given the key "my_ext"):
     * - Tx_MyExt_Model_DbTable_TxDamCat -> tx_dam_cat
     * - Tx_MyExt_Model_DbTable_TtContent -> tt_content
     * - Tx_MyExt_Model_DbTable_TxMyextPages -> tx_myext_pages
     * - Tx_MyExt_Model_DbTable_Pages -> tx_myext_pages (when there's a TCA entry for "tx_myext_pages")
     * - Tx_MyExt_Model_DbTable_Pages -> pages (when there's no TCA entry for "tx_myext_pages")
     *
     * @param Zfext_Db_Table $table
     * @throws Zfext_Table_Exception
     * @return Ambigous <string, mixed>|string
     */
    protected function _findTableName(Zfext_Db_Table $table)
    {
        $className = get_class($table);
        if (isset(self::$_classToTableNameMap[$className])) {
            return self::$_classToTableNameMap[$className];
        }
        $classParts = explode('_', $className);
        $filter = new Zend_Filter_Word_CamelCaseToUnderscore();
        $potentialName = strtolower($filter->filter(array_pop($classParts)));
        if (substr($potentialName, 0, 3) == 'tx_') {
            // Definetely prefixed
            $tableName = $potentialName;
        } else {
            if (count($classParts) < 3 || $classParts[0] != 'Tx') {
                throw new Zfext_Db_Table_Exception('Can not determine table name on unprefixed table classes');
            }
            $prefixedName = strtolower($classParts[0].'_'.$classParts[1]).'_'.$potentialName;
            if (isset($GLOBALS['TCA'][$prefixedName])) {
                $tableName = $prefixedName;
            } elseif (isset($GLOBALS['TCA'][$potentialName])) {
                $tableName = $potentialName;
            }
        }
        if (!isset($tableName)) {
            throw new Zfext_Db_Table_Exception('Could not determine name of '.get_class($table));
        }
        self::$_classToTableNameMap[$className] = $tableName;
        return $tableName;
    }

    /**
     * Reverse method of _findTableName()
     *
     * @param string $tableName
     * @throws Zfext_Db_Table_Exception
     * @return string
     */
    protected function _findTableClassName($tableName)
    {
        if (isset(self::$_tableToClassNameMap[$tableName])) {
            return self::$_tableToClassNameMap[$tableName];
        }
        $thisName = get_class($this);
        $front = Zend_Controller_Front::getInstance();
        $dispatcher = $front->getDispatcher();
        $controllerDirectory = $dispatcher->getControllerDirectory();

        if (count($controllerDirectory) > 1) {
            // There are other modules where the potential table class
            // might lie -> Find the module where $this table lies, the
            // prefix inside this module (e.g. '_Models_DbTable') and
            // add all module namespaces to $prefixes so the class for
            // $tableName will be searched in the module where $this
            // table first, then in default module and then in the others

            $defaultModule = $dispatcher->getDefaultModule();

            if (!$dispatcher->getParam('prefixDefaultModule')) {
                // Search for the default module last because it's
                // namespace is probably contained in other modules
                unset($controllerDirectory[$defaultModule]);
                $controllerDirectory[$defaultModule] = null;
            }
            $prefixes = array();
            $prefixInModule = null;
            foreach ($controllerDirectory as $module => $dir) {
                $prefix = $dispatcher->formatClassName($module, '');
                if (!$prefixInModule && substr($thisName, 0, $l = strlen($prefix)) == $prefix) {
                    // $this table is in this $module - get prefix within module (e.g. '_Models_DbTable)
                    $prefixInModule = substr($thisName, $l, strrpos($thisName, '_') - $l);
                    // At first search in the module where $this table was found
                    array_unshift($prefixes, $prefix);
                } elseif ($module == $defaultModule) {
                    // Then in default module
                    array_unshift($prefixes, array_shift($prefixes), $prefix);
                } else {
                    // And then in the remaining modules
                    array_push($prefixes, $prefix);
                }
            }
            if (!$prefixInModule) {
                throw new Zfext_Db_Table_Exception('Could not resolve table namespace');
            }
            foreach ($prefixes as $i => $prefix) {
                $prefixes[$i] .= $prefixInModule;
            }
        } else {
            // There's only one module - just search the class in the
            // same namespace like of $this
            array_pop($classParts);
            $prefixes = array(implode('_', $classParts));
        }

        // Search for table name with and without prefix
        $search = array($tableName);
        $classParts = explode('_', $thisName);
        $tablePrefix = strtolower($classParts[0].'_'.$classParts[1]);
        if (substr($tableName, 0, $l = strlen($tablePrefix)) == $tablePrefix) {
            $search[] = substr($tableName, $l+1);
        }

        $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
        $className = null;
        foreach ($prefixes as $prefix) {
            foreach ($search as $name) {
                $class = $prefix;
                if ($name) {
                    $class .= '_'.$filter->filter($name);
                }
                if (class_exists($class)) {
                    $className = $class;
                    break;
                }
            }
        }
        if (!$className) {
            throw new Zfext_Db_Table_Exception('Could not find class for table '.$tableName);
        }
        self::$_tableToClassNameMap[$tableName] = $className;
        return $className;
    }

    /**
     * Get/set if auto translation should be enabled
     *
     * @param null|boolean $flag
     * @return Zfext_Db_Table|boolean
     */
    public function autoTranslationEnabled($flag = null)
    {
        if ($flag !== null) {
            $this->_autoTranslation = $flag;
            return $this;
        }
        return $this->_autoTranslation;
    }
}
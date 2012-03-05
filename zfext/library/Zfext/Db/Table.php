<?php
class Zfext_Db_Table extends Netzelf_Db_Table
{
    protected static $_tableToClassNameMap = array();

    protected static $_classToTableNameMap = array();

    /**
     * @var Zend_Db_Table_Definition
     */
    protected static $_tableDefinition;

    protected $_rowClass = 'Zfext_Db_Table_Row';

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
        parent::__construct(self::$_tableDefinition->getTableConfig($this->_name));
    }

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
            if ($foreignTable == $name) {
                continue;
            }
            t3lib_div::loadTCA($foreignTable);
            foreach ((array) $TCA[$foreignTable]['columns'] as $refColumn => $refConfig) {
                $refConfig = $refConfig['config'];
                if ($refConfig['type'] == 'group' && $refConfig['internal_type'] == 'db' && $refConfig['allowed'] == $name ||
                    $refConfig['type'] == 'select' && $refConfig['foreign_table'] == $name) {
                    if ($refConfig['MM']) {
                        // Not yet supportet
                        continue;
                    }
                    try {
                        $class = $this->_findTableClassName($foreignTable);
                    } catch (Exception $e) {
                        continue;
                    }
                    $key = ($prefix && substr($foreignTable, 0, $prefixLength) == $prefix) ? substr($foreignTable, $prefixLength) : $foreignTable;
                    $dependentTables[$key] = $class;
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
                $foreignTable = $config['foreignTable'];
            } else {
                continue;
            }
            if (!$foreignTable || $config['MM']) {
                // Not yet supportet
                continue;
            }
            try {
                $class = $this->_findTableClassName($foreignTable);
            } catch (Exception $e) {
                continue;
            }
            if ($config['maxitems'] > 1) {
                $dependentTables[$column] = $class;
            } else {
                $referenceMap[ucfirst($column)] = array(
                    'columns' => array($column),
                    'refTableClass' => $class,
                    'refColumns' => array('uid'),
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
}
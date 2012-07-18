<?php
require_once dirname(__FILE__).'/Plugin.php';

class Zfext_Module extends Zfext_Plugin
{
    public $extKey;

    /**
     * @var Zend_Config
     */
    protected $_moduleConfig;

    protected $_typoScriptSetupCache = array();

    protected $_currentPageId = null;

    /**
     * This method forwards the call to run() (invoked from typo3/mod.php and configured)
	 *
	 * Adapted from Tx_Extbase_Core_Bootstrap::callModule()
	 *
     * @param unknown_type $moduleSignature
     * @throws RuntimeException
     * @return boolean
     */
    public function callModule($moduleSignature)
    {
        if (!isset($GLOBALS['TBE_MODULES']['_configuration'][$moduleSignature])) {
			return FALSE;
		}
        if (!$GLOBALS['TBE_MODULES']['_configuration'][$moduleSignature]['zfext']) {
			return FALSE;
		}
		$config = $GLOBALS['TBE_MODULES']['_configuration'][$moduleSignature];
		$this->extKey = $config['extKey'];

        $GLOBALS['BE_USER']->modAccess($config, TRUE);
		if (t3lib_div::_GP('id')) {
			// Check page access
			$permClause = $GLOBALS['BE_USER']->getPagePermsClause(TRUE);
			$access = is_array(t3lib_BEfunc::readPageAccess((integer) t3lib_div::_GP('id'), $permClause));
			if (!$access) {
				throw new RuntimeException('You don\'t have access to this page', 1289917924);
			}
		}

		Zfext_Manager::loadLibrary();
		require_once 'Zend/Config.php';
		$this->_moduleConfig = new Zend_Config($config);

		$this->run();
		echo Zend_Controller_Front::getInstance()->getResponse();

        return true;
    }

	/**
	 * Returns the page uid of the current page.
	 * If no page is selected, we'll return the uid of the first root page.
	 *
	 * Adapted from Tx_Extbase_Configuration_BackendConfigurationManager::getCurrentPageId()
	 *
	 * @return integer current page id. If no page is selected current root page id is returned
	 */
	public function getCurrentPageId()
	{
	    if (!$this->_currentPageId !== null) {
	        return $this->_currentPageId;
	    }

		$pageId = (integer) t3lib_div::_GP('id');
		if (!$pageId) {
		    /* @var $db t3lib_db */
		    $db = $GLOBALS['TYPO3_DB'];
    		// get current site root
    		$rootPages = $db->exec_SELECTgetRows('uid', 'pages', 'deleted=0 AND hidden=0 AND is_siteroot=1', '', '', '1');
    		if (count($rootPages)) {
    			$pageId = $rootPages[0]['uid'];
    		} else {
        	    // get root template
        		$rootTemplates = $db->exec_SELECTgetRows('pid', 'sys_template', 'deleted=0 AND hidden=0 AND root=1', '', '', '1');
        		if (count($rootTemplates)) {
        			$pageId = $rootTemplates[0]['pid'];
        		} else {
        		    $pageId = 0;
        		}
    		}
		}
		return $this->_currentPageId = $pageId;
	}

	/**
	 * Load the typoscript setup for specific page or the current one
	 *
	 * Adapted from Tx_Extbase_Configuration_BackendConfigurationManager::getTypoScriptSetup()
	 *
	 * @param int|null $pageId
	 * @return array
	 */
	public function getTypoScriptSetup($pageId = null)
    {
        if ($pageId === null) {
            $pageId = $this->getCurrentPageId();
        }
        if (isset($this->_typoScriptSetupCache[$pageId])) {
            return $this->_typoScriptSetupCache[$pageId];
        }

        /* @var $template t3lib_TStemplate */
		$template = t3lib_div::makeInstance('t3lib_TStemplate');
		// do not log time-performance information
		$template->tt_track = 0;
		$template->init();
		// Get the root line
		$sysPage = t3lib_div::makeInstance('t3lib_pageSelect');
		// get the rootline for the current page
		$rootline = $sysPage->getRootLine($pageId);
		// This generates the constants/config + hierarchy info for the template.
		$template->runThroughTemplates($rootline, 0);
		$template->generateConfig();

        return $this->_typoScriptSetupCache[$pageId] = $template->setup;
	}

	/**
	 * Just for documentation purpose (return type)
	 *
	 * @see Zfext_Plugin::getInstance()
	 * @return Zfext_Module
	 */
	public static function getInstance()
	{
	    return self::$_instance;
	}

    /**
     * Get the entire module (!) config or a value for a key from it
     * (to get plugin config use @see getPluginConfig())
     *
     * @param string|null $key
     * @return Ambigous <Zend_Config, mixed, multitype:>
     */
    public static function getConfig($key = null)
    {
	    return $key ? self::getInstance()->_moduleConfig->get($key) : self::getInstance()->_moduleConfig;
    }

    /**
     * Load a plugin by plugin name (plugin.plugin_name) - if your extension
     * has only one plugin you don't need to specify the name
     *
     * @param string|null $pluginName
     * @throws Zfext_Exception
     * @return tslib_pibase
     */
    public function loadPlugin($pluginName = null)
    {
        if ($this->_plugin && (!$pluginName || $this->_plugin->prefixId == $pluginName)) {
            return;
        }

        $setup = $this->getTypoScriptSetup();
        if (!$pluginName) {
            foreach ($setup['plugin.'] as $potentialPluginName => $plugin) {
                if (substr($potentialPluginName, -1) != '.') {
                    continue;
                }
                if (isset($plugin['zfext.']['signature'])) {
                    $parts = explode('.', $plugin['zfext.']['signature']);
                    if ($parts[0] != $this->extKey) {
                        continue;
                    }
                    if ($pluginName) {
                        throw new Zfext_Exception('Extension "'.$this->extKey.'" has more than one plugins - specify which to load');
                    } else {
                        $pluginName = rtrim($potentialPluginName, '.');
                    }
                }
            }
        }

        $config = $setup['plugin.'][$pluginName.'.'];
        if (!is_array($config)) {
            throw new Zfext_Exception('Could not find plugin "'.$pluginName.'"');
        }
        if (!isset($config['zfext.']['signature'])) {
            throw new Zfext_Exception('Plugin "'.$pluginName.'" is not a ZFext plugin');
        }

        require_once(t3lib_extMgm::extPath('zfext').'plugin/class.tx_zfext.php');
        $this->_plugin = t3lib_div::makeInstance('tx_zfext');

	    $signature = explode('.', $config['zfext.']['signature']);
	    $this->_plugin->extKey = $signature[0];
	    $this->_plugin->prefixId = $signature[1];
		$this->_plugin->scriptRelPath = t3lib_extMgm::extRelPath($this->extKey);
		$this->_plugin->conf = $config;

	    $type = $setup['plugin.'][$pluginName];
		$this->_plugin->pi_USER_INT_obj = ($type == 'USER_INT');
		$this->_plugin->pi_checkCHash = ($type == 'USER');
    }

    /**
     * Get a plugins config (loads the plugin)
     *
     * @param string|null $key
     * @param string|null $pluginName
     * @return Ambigous <Zfext_Config_Typoscript, multitype:>
     */
    public function getPluginConfig($key = null, $pluginName = null)
    {
        self::getInstance()->loadPlugin($pluginName);
        return parent::getConfig($key);
    }
}
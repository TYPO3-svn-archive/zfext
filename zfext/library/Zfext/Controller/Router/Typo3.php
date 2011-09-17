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
 * @package    Zfext_Controller
 * @subpackage Router
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Controller_Router_Typo3 extends Zend_Controller_Router_Abstract
{
	const GLOBALS_PATTERN = '/(?:gp|gpvar):(.+)/i';

	const LOCALS_PATTERN = '/my:(.+)/i';

	protected $_defaultRoute = 'local';

	/**
	 * @var tslib_pibase
	 */
	protected $_plugin;

	/**
	 * The select keys from tt_content records with this plugin
	 * @var array
	 */
	protected $_selectKeys;

    /**#@+
     * Array keys to use for module, controller, and action. Should be taken out of request.
     * @var string
     */
    protected $_moduleKey     = 'module';
    protected $_controllerKey = 'controller';
    protected $_actionKey     = 'action';
    /**#@-*/

    /**
     * @var Zend_Controller_Dispatcher_Standard
     */
    protected $_dispatcher;

    /**
     * @var Zend_Controller_Request_Abstract
     */
    protected $_request;

    /*
     * @var array
     */
    protected $_defaults = array();

    /**
     * @var array
     */
    protected $_pageDefaults = null;


    /**
     * Constructor
     *
     * @param array $defaults Defaults for map variables with keys as variable names
     * @param Zend_Controller_Dispatcher_Interface $dispatcher Dispatcher object
     * @param Zend_Controller_Request_Abstract $request Request object
     */
    protected function _init(Zend_Controller_Request_Abstract $request, $plugin)
    {
        $front = Zend_Controller_Front::getInstance();
	    $this->_dispatcher = $front->getDispatcher();
	    $this->_request = $request;
	    $this->_plugin = $plugin;

        $this->_moduleKey     = $this->_request->getModuleKey();
        $this->_controllerKey = $this->_request->getControllerKey();
        $this->_actionKey     = $this->_request->getActionKey();

        $this->_defaults += array(
            $this->_controllerKey => $this->_dispatcher->getDefaultControllerName(),
            $this->_actionKey     => $this->_dispatcher->getDefaultAction(),
            $this->_moduleKey     => $this->_dispatcher->getDefaultModule()
        );

        $pluginOptions = Zfext_ExtMgm::getPluginOptions($this->_plugin->prefixId);
        if (isset($pluginOptions['defaultModule'])) {
            $this->_defaults[$this->_moduleKey] = $pluginOptions['defaultModule'];
        }
        if (isset($pluginOptions['defaultController'])) {
            $this->_defaults[$this->_controllerKey] = $pluginOptions['defaultController'];
        }
        if (isset($pluginOptions['defaultAction'])) {
            $this->_defaults[$this->_actionKey] = $pluginOptions['defaultAction'];
        }
    }

	/**
     * Processes a request and sets its controller and action.  If
     * no route was possible, an exception is thrown.
     *
     * @param  Zend_Controller_Request_Abstract
     * @throws Zend_Controller_Router_Exception
     * @return Zend_Controller_Request_Abstract|boolean
     */
	public function route(Zend_Controller_Request_Abstract $request)
	{
		$request->clearParams();

	    $this->_init($request, Zfext_Plugin::getInstance());

	    $piVars = (array) $this->_plugin->piVars;
	    $defaults = $this->_getPageDefaults(0);
	    $params = array_merge($defaults, $piVars);

	    // Fix eventually non existing module<->controller and controller<->action combinations
	    // We could also do this in assemblage but then we would have a lot longer urls
	    if (isset($piVars[$this->_moduleKey]) && $piVars[$this->_moduleKey] != $defaults[$this->_moduleKey]) {
	        if (!isset($piVars[$this->_controllerKey])) {
	            $params[$this->_controllerKey] = $this->_defaults[$this->_controllerKey];
	        }
	    }
	    if (isset($piVars[$this->_moduleKey]) && $piVars[$this->_moduleKey] != $defaults[$this->_moduleKey] ||
	    isset($piVars[$this->_controllerKey]) && $piVars[$this->_controllerKey] != $defaults[$this->_controllerKey]) {
	        if (!isset($piVars[$this->_actionKey])) {
	            $params[$this->_actionKey] = $this->_defaults[$this->_actionKey];
	        }
	    }

	    // Set params to the request
	    foreach ($params as $param => $value) {
	    	if ($param == $this->_moduleKey) {
	    		$request->setModuleName($value);
	    	}
	        if ($param == $this->_controllerKey) {
	            $request->setControllerName($value);
	        }
	        if ($param == $this->_actionKey) {
	            $request->setActionName($value);
	        }
	        $request->setParam($param, $value);
	    }

		return $request;
	}

	/**
	 * Parse a path exactly like the module route in ZF
	 *
	 * @param string $path
	 * @return array
	 */
	protected function _parsePath($path)
	{
        $values = array();
        $params = array();
	    $path = explode('/', trim($path, '/'));

        if ($this->_dispatcher && $this->_dispatcher->isValidModule($path[0])) {
            $values[$this->_moduleKey] = array_shift($path);
            $this->_moduleValid = true;
        }

        if (count($path) && !empty($path[0])) {
            $values[$this->_controllerKey] = array_shift($path);
        }

        if (count($path) && !empty($path[0])) {
            $values[$this->_actionKey] = array_shift($path);
        }

        if ($numSegs = count($path)) {
            for ($i = 0; $i < $numSegs; $i = $i + 2) {
                $key = urldecode($path[$i]);
                $val = isset($path[$i + 1]) ? urldecode($path[$i + 1]) : null;
                $params[$key] = (isset($params[$key]) ? (array_merge((array) $params[$key], array($val))): $val);
            }
        }

        return $values + $params;
	}

	/**
     * Generates a URL path that can be used in URL creation, redirection, etc.
     *
     * May be passed user params to override ones from URI, Request or even defaults.
     * If passed parameter has a value of null, the current URL will be returned.
     *
     * You can use namespaces in the param-names (array-keys of the params-array).
     * These namespaces decide if the param is a global ($_GET) or local (piVars)
     * param, independent from route. See example below.
     *
     * <code title="Example for param namespaces">
     * //URL-Helper:
     * $this->url(array(
     * 	'my:id' => 3,	//Will be tx_example[id]=3
     * 	'gp:id' => 3	//Will be id=3
     * ));
     * //Returns: index.php?id=3&tx_example[id]=3
     * </code>
     *
     * There are two routes available: "local" and "global". The difference is where
     * the vars that were not filtered out by namespace (see above) will be put:
     * - Local route puts them to the local params (piVars)
     * - Global route puts them to the global params ($_GET)
     * If null is passed as a route name assemble will use $_defaultRoute (local).
     *
     * Also there is a pseudo route called xhr which generates urls to the ZFext-
     * eID script. You can also prefix the above routes with xhr (eg. "xhr.global")
     *
     * Another usefull feature is that the router can find a suitable FE-ID for you
     * when you set gp:id-param to 'auto' (or the 'id'-param with the global route).
     * Therefore it searches for plugin-rows with a suitable select_key (where you
     * can setup the default path) - @see _findPageId()
     *
     * Reset is used to signal that all parameters should be reset to it's defaults.
     * Ignoring all URL specified values. User specified params still get precedence.
     *
     * Encode tells to url encode resulting path parts.
     *
     * @param  array $userParams Options passed by a user used to override parameters
     * @param  string $name The name of a Route to use ("local" or "global")
     * @param  bool $reset Whether to reset to the route defaults ignoring URL params
     * @param  bool $encode NOT IMPLEMENTED - see tslib_pibase::linkTP for that
     * @throws Zend_Controller_Router_Exception
     * @return string Resulting URL path
     */
    public function assemble($userParams, $name = null, $reset = false, $encode = true)
    {
        if (is_string($name) && stripos($name, 'xhr') === 0) {
            $parts = explode('.', $name);
            $name = (isset($parts[1])) ? $parts[1] : null;
            $userParams['gp:eID'] = 'zfext';
            $userParams['gp:tx_zfext'] = array(
                'eid' => $this->_plugin->prefixId,
            	'ceid' => $this->_plugin->cObj->getFieldVal('uid')
            );
        }

        if (!count($userParams)) {
            if ($reset) {
                $this->_plugin->pi_linkTP('|');
                return $this->_plugin->cObj->lastTypoLinkUrl;
            } else {
                return $this->_plugin->pi_linkTP_keepPIvars_url();
            }
        }

        $route = (is_string($name) && strlen($name) && $name != 'default') ? strtolower($name) : $this->_defaultRoute;

        $prefixId = $this->_plugin->prefixId;

        $globalParams = array();

        if (isset($userParams[$prefixId]) &&
            is_array($userParams[$prefixId]))
        {
            $localParams = $userParams[$prefixId];
            unset($userParams[$prefixId]);
        } else {
            $localParams = array();
        }

        //Filter the params:
        foreach ($userParams as $param => $value) {
        	if (preg_match(self::GLOBALS_PATTERN, $param, $matches)) {
        	    $globalParams[$matches[1]] = $value;
        	}
        	elseif (preg_match(self::LOCALS_PATTERN, $param, $matches)) {
        	    $localParams[$matches[1]] = $value;
        	}
        	elseif ($route == 'global') {
        	    $globalParams[$param] = $value;
        	}
        	elseif ($route == 'local') {
        	    $localParams[$param] = $value;
        	}
        }

        if (!$reset) {
            $getParams = $_GET;
            unset($getParams[$prefixId], $getParams['id'],$getParams['eID'], $getParams['tx_zfext']);
            $globalParams = $this->_mergeParams($getParams, $globalParams);
            $localParams = $this->_mergeParams((array) $this->_plugin->piVars, $localParams);

            if ($this->_plugin->pi_autoCacheEn) {
                $cache = $this->_plugin->pi_autoCache($localParams);
            }
        }
        unset($localParams['DATA']);

        $altPageId = 0;
        $cache = 0;

        if (!empty($globalParams['id'])) {
            if ($globalParams['id'] == 'auto') {
                $altPageId = $this->_findPageId($localParams);
            } else {
                $altPageId = $globalParams['id'];
            }
            unset($globalParams['id']);
        }

        $globalParams[$prefixId] = array_diff($localParams, $this->_getPageDefaults($altPageId));

        $this->_plugin->pi_linkTP('|', $globalParams, $cache, $altPageId);

        return $this->_plugin->cObj->lastTypoLinkUrl;
    }

    /**
     * Attempts to find a suitable id for the params
     *
     * @param array $params
     * @return int
     */
    protected function _findPageId($params)
    {
        $params = array_merge($this->_defaults, $params);
        $module = $params[$this->_moduleKey];
        $controller = $params[$this->_controllerKey];
        $action = $params[$this->_actionKey];

        $lookUp = array(
            array(
                $this->_moduleKey => $module,
                $this->_controllerKey => $controller,
                $this->_actionKey => $action
            ),
            array(
                $this->_moduleKey => $module,
                $this->_controllerKey => $controller
            ),
            array(
                $this->_moduleKey => $module
            ),
            array(
                $this->_controllerKey => $controller,
                $this->_actionKey => $action
            ),
            array(
                $this->_controllerKey => $controller
            )
        );


        $allPageDefaults = $this->_getPageDefaults();
        foreach ($lookUp as $i => $values) {
            foreach ($allPageDefaults as $pid => $defaults) {
                $match = 0;
                foreach ($values as $key => $value) {
                    if ($defaults[$key] == $value) {
                        $match++;
                    }
                }
                if ($match == count($values)) {
                    return $pid;
                }
            }
        }

        return 0;
    }

    /**
     * Gets the defaults for the pages where content elements with current
     * type are contained.
     *
     * @param int|null $id
     * @return array Defaults for a page or all defaults when $id is null
     */
    protected function _getPageDefaults($id = null)
    {
        if (!$this->_pageDefaults) {
            $where = 'deleted = 0 AND hidden=0 AND CType=\'list\' AND ';
            $where .= 'list_type = \''.$this->_plugin->cObj->getFieldVal('list_type').'\' AND select_key <> \'\'';

            if (strlen($this->_plugin->cObj->getFieldVal('select_key'))) {
    	    	$path = $this->_plugin->cObj->getFieldVal('select_key');
    	    	$this->_pageDefaults[0] = array_merge($this->_defaults, $this->_parsePath($path));
    	    	$this->_pageDefaults[$GLOBALS['TSFE']->id] = $this->_pageDefaults[0];
    	    	$where .= ' AND pid <> '.$GLOBALS['TSFE']->id;
    	    }

            $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('pid, select_key', 'tt_content', $where);
            foreach ($rows as $row) {
                $this->_pageDefaults[$row['pid']] = array_merge(
                    $this->_defaults,
                    $this->_parsePath($row['select_key'])
                );
            }
        }
        if ($id === null) {
            return $this->_pageDefaults;
        }
        if (!isset($this->_pageDefaults[$id])) {
            return $this->_defaults;
        }
        return $this->_pageDefaults[$id];
    }

    /**
     * Wrapper to recursively merge arrays
     *
     * @param array $params1
     * @param array $params2
     * @return array
     */
    protected function _mergeParams($params1, $params2)
    {
        if (!count($params2)) {
            return $params1;
        }
        return t3lib_div::array_merge_recursive_overrule($params1, $params2);
    }
}
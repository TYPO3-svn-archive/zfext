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
		
	    $this->_plugin = Zfext_Plugin::getInstance();
	    
	    $front = Zend_Controller_Front::getInstance();
	    $dispatcher = $front->getDispatcher();
	    
	    $options = array_merge(
	        array(
	        	'defaultModule' => $dispatcher->getDefaultModule(),
	            'defaultController' => $dispatcher->getDefaultControllerName(),
	            'defaultAction' => $dispatcher->getDefaultAction()
	        ),
	        Zfext_ExtMgm::getPluginOptions($this->_plugin->prefixId)
	    );
	    
	    // Detect if a startup-route was set in BE
	    if (strlen($this->_plugin->cObj->getFieldVal('select_key'))) {
	    	$parts = explode('/', $this->_plugin->cObj->getFieldVal('select_key'), 3);
	    	$action = array_pop($parts);
	    	if (empty($this->_plugin->piVars[$request->getModuleKey()]) &&
	    		empty($this->_plugin->piVars[$request->getControllerKey()])) {
	    		$options['defaultAction'] = $action;
	    	}
	    	//if (empty($this->_plugin->piVars[$request->getModuleKey()])) {
		    	if (count($parts)) {
		    		$options['defaultController'] = array_pop($parts);
		    	}
		    	if (count($parts)) {
		    		$options['defaultModule'] = array_pop($parts);
		    	}
	    	//}
	    }
	    
	    // This could be done better but for now it's ok
	    // set the determined m/c/a options back to frontcontroller
	    $front->setDefaultModule($options['defaultModule']);
	    $front->setDefaultControllerName($options['defaultController']);
	    $front->setDefaultAction($options['defaultAction']);
	    
	    $params = array_merge(
	        array (
    	        $request->getModuleKey() => $options['defaultModule'],
    	        $request->getControllerKey() => $options['defaultController'],
    	        $request->getActionKey() => $options['defaultAction']
	        ),
	        (array) $this->_plugin->piVars
	    );
	    
	    foreach ($params as $param => $value)
	    {
	    	if ($param == $request->getModuleKey())
	    	{
	    		$request->setModuleName($value);
	    	}
	        if ($param == $request->getControllerKey())
	        {
	            $request->setControllerName($value);
	        }
	        if ($param == $request->getActionKey())
	        {
	            $request->setActionName($value);
	        }
	        $request->setParam($param, $value);
	    }
		
		return $request;
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
        if (is_string($name) && stripos($name, 'xhr') === 0)
        {
            $parts = explode('.', $name);
            $name = (isset($parts[1])) ? $parts[1] : null;
            $userParams['gp:eID'] = 'zfext';
            $userParams['gp:tx_zfext'] = array('eid' => $this->_plugin->prefixId);
        }
        
        if (!count($userParams))
        {
            return $this->_plugin->pi_linkTP_keepPIvars_url();
        }
        
        $route = (is_string($name) && strlen($name) && $route != 'default') ? strtolower($name) : $this->_defaultRoute;
        
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
        foreach ($userParams as $param => $value)
        {
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
        
        $altPageId = 0;
        $cache = 0;
        
        if (!empty($globalParams['id']))
        {
            $altPageId = $globalParams['id'];
            unset($globalParams['id']);
        }
        
        if (!$reset)
        {
            $getParams = $_GET;
            unset($getParams[$prefixId], $getParams['id']);
            $globalParams = $this->_mergeParams($getParams, $globalParams);
            $localParams = $this->_mergeParams((array) $this->_plugin->piVars, $localParams);
            
            if ($this->_plugin->pi_autoCacheEn)
            {
                $cache = $this->_plugin->pi_autoCache($localParams);
            }
        }
        unset($localParams['DATA']);
        
        $front = Zend_Controller_Front::getInstance();
        $request = $front->getRequest();
        
        // Filter out unnecessary keys:
        foreach ($localParams as $param => $value) {
        	if (
        		($param == $request->getModuleKey() && $value == $front->getDefaultModule()) ||
        		($param == $request->getControllerKey() && $value == $front->getDefaultControllerName()) ||
        		($param == $request->getActionKey() && $value == $front->getDefaultAction())
        	) {
	    		unset($localParams[$param]);
	    	}
        }
        
        $globalParams[$prefixId] = $localParams;
        
        $this->_plugin->pi_linkTP('|', $globalParams, $cache, $altPageId);
        
        return $this->_plugin->cObj->lastTypoLinkUrl;
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
        if (!count($params2))
        {
            return $params1;
        }
        return t3lib_div::array_merge_recursive_overrule($params1, $params2);
    }
}
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
 * @version    $Id: Bootstrap.php 36506 2010-08-08 15:46:09Z metti $
 */

/**
 * Zend_View with Fluid
 *
 * @category   TYPO3
 * @package    Zend_View
 * @subpackage Fluid
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_View_Fluid extends Zend_View_Abstract
{
    const HELPERS_KEY = 'zfHelpers';

    /**
     * @var Zfext_View_Fluid_View
     */
    protected $_fluid;

    /**
     * The currently used base path
     * @var string
     */
    protected $_currentBasePath;

    /**
     * Construct fluid view object and assign helpers object to it
     * @param array $config
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
        $this->_fluid = new Zfext_View_Fluid_View();
        $this->assign(self::HELPERS_KEY, new Zfext_View_Fluid_Helpers($this));
    }

    /**
     * Also clone fluid view object and reassign the helpers to it
     */
    public function __clone()
    {
        $this->_fluid = clone $this->_fluid;
        $this->assign(self::HELPERS_KEY, new Zfext_View_Fluid_Helpers($this));
    }

    //
    // Assignment:
    //

    /* (non-PHPdoc)
     * @see Zend_View_Abstract::__get()
     */
    public function __get($key)
    {
        $templateVariableContainer = $this->_fluid->getTemplateVariableContainer();
		return $templateVariableContainer->exists($key) ? $templateVariableContainer->get($key) : null;
    }

    /* (non-PHPdoc)
     * @see Zend_View_Abstract::__set()
     */
    public function __set($key, $value)
    {
        $this->_fluid->assign($key, $value);
    }

    /* (non-PHPdoc)
     * @see Zend_View_Abstract::__isset()
     */
    public function __isset($key)
    {
        return $this->_fluid->getTemplateVariableContainer()->exists($key);
    }

    /* (non-PHPdoc)
     * @see Zend_View_Abstract::__unset()
     */
    public function __unset($key)
    {
        $templateVariableContainer = $this->_fluid->getTemplateVariableContainer();
		if ($templateVariableContainer->exists($key)) {
		    $templateVariableContainer->remove($key);
		}
    }

    /* (non-PHPdoc)
     * @see Zend_View_Abstract::assign()
     */
    public function assign($spec, $value = null)
    {
        if (is_string($spec)) {
            $this->_fluid->assign($spec, $value);
        } elseif (is_array($spec)) {
            $this->_fluid->assignMultiple($spec);
        }
        return $this;
    }

    //
    // Path based stuff
    //

    /* (non-PHPdoc)
     * @see Zend_View_Abstract::addBasePath()
     */
    public function addBasePath($path, $classPrefix = 'Zend_View')
    {
        $path = rtrim($path, '/\\').DIRECTORY_SEPARATOR;
        $this->addScriptPath($path.'templates');
        // No helpers yet
        return $this;
    }

    /* (non-PHPdoc)
     * @see Zend_View_Abstract::setBasePath()
     */
    public function setBasePath($path, $classPrefix = 'Zend_View')
    {
        $path = rtrim($path, '/\\').DIRECTORY_SEPARATOR;
        $this->setScriptPath($path.'templates');
        // No helpers yet
        return $this;
    }

    /* (non-PHPdoc)
     * @see Zend_View_Abstract::_script()
     */
    protected function _script($name)
    {
        $script = parent::_script($name);
        $this->_currentBasePath = substr($script, 0, - strlen($name));
        return $script;
    }

    /* (non-PHPdoc)
     * @see Zend_View_Abstract::_run()
     */
    protected function _run()
    {
        $loaders = Zend_Loader_Autoloader::getInstance()->getAutoloaders();
        foreach ($loaders as $loader) {
            if ($loader instanceof Zend_Application_Module_Autoloader && !$loader->hasResourceType('fluidviewhelper')) {
                $loader->addResourceType('fluidviewhelper', 'viewHelpers', 'ViewHelper');
            }
        }

        $this->_fluid->setPartialRootPath($this->_currentBasePath);
        $layoutPath = dirname($this->_currentBasePath).DIRECTORY_SEPARATOR.'layouts';
        if (file_exists($layoutPath)) {
            $this->_fluid->setLayoutRootPath($layoutPath);
        }
        $this->_fluid->setTemplatePathAndFilename(func_get_arg(0));
        echo $this->_fluid->render();
    }
}
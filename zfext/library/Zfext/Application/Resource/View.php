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
 * @version    $Id: Errorhandler.php 64684 2012-07-18 18:23:45Z metti $
 */

/**
 * Extend Zend_Application_Resource_View to
 * 1. Allow setting options on viewRenderer (controller helper) also
 * 2. Allow setting another view class than Zend_View
 *
 * @category   TYPO3
 * @package    Zfext_Application
 * @subpackage Resource
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Application_Resource_View extends Zend_Application_Resource_View
{
    /* (non-PHPdoc)
     * @see Zend_Application_Resource_View::init()
     */
    public function init()
    {
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');

        foreach ($this->getOptions() as $option => $value) {
            $method = 'set'.ucfirst($option);
            if (method_exists($viewRenderer, $method)) {
                call_user_func(array($viewRenderer, $method), $value);
                unset($this->_options[$option]);
            }
        }

        $view = $this->getView();
        $viewRenderer->setView($view);

        return $view;
    }

    /* (non-PHPdoc)
     * @see Zend_Application_Resource_View::getView()
     */
    public function getView()
    {
        $options = $this->getOptions();
        if (!$this->_view && !empty($options['class']) && $options['class'] != 'Zend_View') {
            $class = (string) $options['class'];
            unset($options['class']);
            Zend_Loader::loadClass($class);
            if (!class_exists($class)) {
                throw new Zend_Application_Resource_Exception('Could not load class "'.$class.'"');
            }
            $this->_view = new $class($options);
            if (!$this->_view instanceof Zend_View_Interface) {
                throw new Zend_Application_Resource_Exception('Class "'.$class.'" must implement Zend_View_Interface');
            }
            return $this->_view;
        }
        return parent::getView();
    }
}

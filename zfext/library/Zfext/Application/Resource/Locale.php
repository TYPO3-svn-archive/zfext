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
 * Binds the (default) locale to that from TYPO3 setup when enabled
 * NOTE: You still have to set force to true to force it
 *
 * @category   TYPO3
 * @package    Zfext_Application
 * @subpackage Resource
 * @author     Christian Opitz <co@netzelf.de>
 */
class Zfext_Application_Resource_Locale extends Zend_Application_Resource_Locale
{
    public function getLocale()
    {
        if (null === $this->_locale) {
            $options = $this->getOptions();
            if ($options['bindWithTypo3']) {
                if (isset($GLOBALS['TSFE']->config['config']['locale_all'])) {
                    $locale = $GLOBALS['TSFE']->config['config']['locale_all'];
                } elseif ($GLOBALS['TSFE']->lang && !$GLOBALS['TSFE']->lang != 'default') {
                    $locale = $GLOBALS['TSFE']->lang.'_'.strtoupper($GLOBALS['TSFE']->lang);
                }
                $this->_options['default'] = $locale;
            }
        }
        return parent::getLocale();
    }
}
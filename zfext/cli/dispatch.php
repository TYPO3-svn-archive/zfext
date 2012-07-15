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

if (!defined ('TYPO3_cliMode')) die ('Access denied: CLI only.');

$toolConf = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zfext']['toolManifests'];
if (!isset($toolConf[TYPO3_cliKey])) {
    die('No manifest found for '.TYPO3_cliKey);
}

Zfext_Manager::loadLibrary($toolConf[TYPO3_cliKey]['extKey']);
$options = array(
    'classesToLoad' => $toolConf[TYPO3_cliKey]['manifest']
);

chdir(PATH_site);

$client = new Zend_Tool_Framework_Client_Console($options);
$client->dispatch();
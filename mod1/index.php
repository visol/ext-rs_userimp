<?php
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

unset($MCONF);
include('conf.php');
include($GLOBALS['BACK_PATH'] . 'init.php');
$GLOBALS['LANG']->includeLLFile('EXT:rs_userimp/Resources/Private/Language/locallang.xlf');
$GLOBALS['BE_USER']->modAccess($MCONF, 1);    // This checks permissions and exits if the users has no permission for entry.


// Make instance:
/** @var \Visol\RsUserimp\Module\UserImporter $SOBE */
$SOBE = GeneralUtility::makeInstance('Visol\\RsUserimp\\Module\\UserImporter');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
 *  (c) 2005-2006 Jan-Erik Revsbech <jer@moccompany.com>
 *  (c) 2006 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

unset($MCONF);
include ('conf.php');
include ($BACK_PATH.'init.php');
$LANG->includeLLFile('EXT:rs_userimp/Resources/Private/Language/locallang.xlf');
$BE_USER->modAccess($MCONF, 1);    // This checks permissions and exits if the users has no permission for entry.


// Make instance:
/** @var \Visol\RsUserimp\Module\UserImporter $SOBE */
$SOBE = GeneralUtility::makeInstance('Visol\\RsUserimp\\Module\\UserImporter');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

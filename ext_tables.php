<?php
if (!defined("TYPO3_MODE")) die ("Access denied.");

if (TYPO3_MODE == "BE") {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule("tools", "txrsuserimpM1", "", \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . "mod1/");
}

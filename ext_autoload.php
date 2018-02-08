<?php
$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('rs_userimp');
return [
    'PHPExcel' => $extensionPath . 'Resources/PHP/PHPExcel-1.8.1/Classes/PHPExcel.php',
    'PHPExcel_IOFactory' => $extensionPath . 'Resources/PHP/PHPExcel-1.8.1/Classes/PHPExcel/IOFactory.php',
];

<?php
defined('TYPO3_MODE') || die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'pxa_ajax_loader',
    'Configuration/TypoScript',
    'Pxa Ajax Loader'
);

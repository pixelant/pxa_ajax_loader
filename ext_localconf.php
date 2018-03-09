<?php
defined('TYPO3_MODE') || die('Access denied');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Pixelant.' . $_EXTKEY,
    'Loader',
    [
        'AjaxLoader' => 'placeholder, load'
    ]
);

// Add plugin to content element wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
// @codingStandardsIgnoreStart
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:pxa_ajax_loader/Configuration/TSconfig/ContentElementWizard.ts">'
// @codingStandardsIgnoreEnd
);

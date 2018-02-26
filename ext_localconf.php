<?php
defined('TYPO3_MODE') || die('Access denied');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Pixelant.' . $_EXTKEY,
    'Loader',
    [
        'AjaxLoader' => 'placeholder, load'
    ],
    // non-cacheable actions
    [
        'AjaxLoader' => 'load'
    ]
);

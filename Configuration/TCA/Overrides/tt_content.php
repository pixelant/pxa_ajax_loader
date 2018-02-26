<?php
defined('TYPO3_MODE') || die('Access denied.');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'pxa_ajax_loader',
    'Loader',
    'LLL:EXT:pxa_ajax_loader/Resources/Private/Language/locallang_be.xlf:plugin.title'
);

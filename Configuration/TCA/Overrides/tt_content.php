<?php
defined('TYPO3_MODE') || die('Access denied.');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'pxa_ajax_loader',
    'Loader',
    'LLL:EXT:pxa_ajax_loader/Resources/Private/Language/locallang_be.xlf:plugin.title'
);
/*call_user_func(function () {
    $_EXTKEY = 'pxa_ajax_loader';

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
        $_EXTKEY,
        'PxaAjaxLoader',
        'Pxa Ajax Loader'
    );

    $pluginSignature = 'pxaajaxloader_loader';

    $GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
        $pluginSignature,
        'FILE:EXT:' . $_EXTKEY . '/Configuration/FlexForms/FlexForm.xml'
    );
});*/

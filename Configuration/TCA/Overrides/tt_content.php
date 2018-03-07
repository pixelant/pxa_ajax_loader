<?php
defined('TYPO3_MODE') || die('Access denied.');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'pxa_ajax_loader',
    'Loader',
    'LLL:EXT:pxa_ajax_loader/Resources/Private/Language/locallang_be.xlf:plugin.title'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        \Pixelant\PxaAjaxLoader\Hooks\PageLayoutViewHook::DB_FIELD_CONTAINER_NAME => [
            'exclude' => 1,
            'label' => 'LLL:EXT:pxa_ajax_loader/Resources/Private/Language/locallang_be.xlf:tca.tab',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0
                    ],
                ],
                'default' => 0,
                'foreign_table' => 'tt_content',
                'foreign_table_where' => 'AND (tt_content.sys_language_uid = ###REC_FIELD_sys_language_uid### OR tt_content.sys_language_uid = -1) AND tt_content.pid=###CURRENT_PID### AND tt_content.CType=\'list\' AND tt_content.list_type=\'pxaajaxloader_loader\'  AND tt_content.uid != ###THIS_UID### AND tt_content.' . \Pixelant\PxaAjaxLoader\Hooks\PageLayoutViewHook::DB_FIELD_CONTAINER_NAME . '=0',
                'dontRemapTablesOnCopy' => 'tt_content',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
            ]
        ]
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;LLL:EXT:pxa_ajax_loader/Resources/Private/Language/locallang_be.xlf:tca.field,'
    . \Pixelant\PxaAjaxLoader\Hooks\PageLayoutViewHook::DB_FIELD_CONTAINER_NAME
);

// Add field
// @codingStandardsIgnoreStart
$GLOBALS['TCA']['tt_content']['ctrl']['useColumnsForDefaultValues'] .= ',' . \Pixelant\PxaAjaxLoader\Hooks\PageLayoutViewHook::DB_FIELD_CONTAINER_NAME;
$GLOBALS['TCA']['tt_content']['ctrl']['shadowColumnsForNewPlaceholders'] .= ',' . \Pixelant\PxaAjaxLoader\Hooks\PageLayoutViewHook::DB_FIELD_CONTAINER_NAME;
// @codingStandardsIgnoreEnd

// Modify colPos
if (!empty($GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['itemsProcFunc'])) {
    \Pixelant\PxaAjaxLoader\UserFunction\ItemsProcFuncs\ColPosList::registerUserFunction(
        $GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['itemsProcFunc']
    );
}
$GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['itemsProcFunc'] =
    \Pixelant\PxaAjaxLoader\UserFunction\ItemsProcFuncs\ColPosList::class . '->processTTContentItems';

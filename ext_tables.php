<?php
defined('TYPO3_MODE') || die('Access denied');

// Add plugin to content element wizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
// @codingStandardsIgnoreStart
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:pxa_ajax_loader/Configuration/TSconfig/ContentElementWizard.ts">'
// @codingStandardsIgnoreEnd
);

call_user_func(function () {
    if (TYPO3_MODE === 'BE') {
        /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Imaging\IconRegistry::class
        );
        $iconRegistry->registerIcon(
            'ext-pxa-ajax-loader-wizard-icon',
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:pxa_ajax_loader/ext_icon.svg']
        );
    }
});
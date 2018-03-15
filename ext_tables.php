<?php
defined('TYPO3_MODE') || die('Access denied');

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
if (TYPO3_MODE === 'BE') {
    // Page BE view hook
    // @codingStandardsIgnoreStart
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['pxaajaxloader_loader'][$_EXTKEY] =
        \Pixelant\PxaAjaxLoader\Hooks\PageLayoutViewHook::class . '->render';

    // New item link hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'][$_EXTKEY] = \Pixelant\PxaAjaxLoader\Hooks\WizardItems::class;

    // Register data handler hook
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][$_EXTKEY] =
        \Pixelant\PxaAjaxLoader\Hooks\DataHandler::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][$_EXTKEY] =
        \Pixelant\PxaAjaxLoader\Hooks\DataHandler::class;
    // @codingStandardsIgnoreEnd
}

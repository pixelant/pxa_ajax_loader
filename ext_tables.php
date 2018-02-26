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

// Page BE view hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['pxaajaxloader_loader'][$_EXTKEY] =
    \Pixelant\PxaAjaxLoader\Hooks\PageLayoutViewHook::class . '->render';
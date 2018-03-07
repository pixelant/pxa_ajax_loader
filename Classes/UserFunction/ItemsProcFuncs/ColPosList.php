<?php
declare(strict_types=1);

namespace Pixelant\PxaAjaxLoader\UserFunction\ItemsProcFuncs;

use Pixelant\PxaAjaxLoader\Hooks\PageLayoutViewHook;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Modify colPos
 *
 * @package Pixelant\PxaAjaxLoader\UserFunction\ItemsProcFuncs
 */
class ColPosList
{
    /**
     * Here we save another hook if already exist
     *
     * @var string
     */
    protected static $otherUserFunction = '';

    /**
     * Process tt_content colPos items
     *
     * @param array $params
     */
    public function processTTContentItems(array &$params, TcaSelectItems $tcaSelectItems)
    {
        if (!empty($GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['otherItemsProcFunc'])) {
            GeneralUtility::callUserFunction(
                $GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['otherItemsProcFunc'],
                $params,
                $tcaSelectItems
            );
        }
        if ((int)$params['row'][PageLayoutViewHook::DB_FIELD_CONTAINER_NAME] > 0) {
            $params['items'] = [];
            $params['items'][] = [
                $this->getLanguageService()->sL(
                    'LLL:EXT:pxa_ajax_loader/Resources/Private/Language/locallang_be.xlf:tca.colPos'
                ),
                PageLayoutViewHook::COL_POS,
                null,
                null,
            ];
        }
    }

    /**
     * Save another user function
     *
     * @param string $userFunction
     */
    public static function registerUserFunction(string $userFunction)
    {
        $GLOBALS['TCA']['tt_content']['columns']['colPos']['config']['otherItemsProcFunc'] = $userFunction;
    }

    /**
     * Returns the language service
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}

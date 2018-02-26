<?php
declare(strict_types=1);

namespace Pixelant\PxaAjaxLoader\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class PageLayoutViewHook
 * @package Pixelant\PxaAjaxLoader\Hooks
 */
class PageLayoutViewHook
{
    /**
     * Render area for content
     *
     * @param array $params
     * @param PageLayoutView $pageLayoutView
     * @return string
     */
    public function render(array $params, PageLayoutView $pageLayoutView)
    {
        $label = BackendUtility::getLabelFromItemListMerged(
            $params['row']['pid'],
            'tt_content',
            'list_type',
            $params['row']['list_type']
        );
        $out = $pageLayoutView->linkEditContent(
            '<strong>' . htmlspecialchars($this->getLanguageService()->sL($label)) . '</strong>',
            $params['row']
        );
        $out .= '<br />';

       // $out .= $this->renderAjaxContentArea();

        return $out;
    }

    protected function renderAjaxContentArea(PageLayoutView $pageLayoutView, array $row): string
    {
        $head = [];
        $gridContent = [];
        $editUidList = [];
        $colPosValues = [];
        $singleColumn = false;

        // get the layout record for the selected backend layout if any
        $gridContainerId = $row['uid'];
        if ($row['pid'] < 0) {
            $originalRecord = BackendUtility::getRecord('tt_content', $row['t3ver_oid']);
        } else {
            $originalRecord = $row;
        }
        /** @var $layoutSetup LayoutSetup */
        $layoutSetup = GeneralUtility::makeInstance(LayoutSetup::class)->init($originalRecord['pid']);
        $gridElement = $layoutSetup->cacheCurrentParent($gridContainerId, true);
        $layoutUid = $gridElement['tx_gridelements_backend_layout'];
        $layout = $layoutSetup->getLayoutSetup($layoutUid);
        $parserRows = null;
        if (isset($layout['config']) && isset($layout['config']['rows.'])) {
            $parserRows = $layout['config']['rows.'];
        }

        // if there is anything to parse, lets check for existing columns in the layout
        if (is_array($parserRows) && !empty($parserRows)) {
            $this->setMultipleColPosValues($parserRows, $colPosValues, $layout);
        } else {
            $singleColumn = true;
            $this->setSingleColPosItems($parentObject, $colPosValues, $gridElement);
        }
        // if there are any columns, lets build the content for them
        $outerTtContentDataArray = $parentObject->tt_contentData['nextThree'];
        if (!empty($colPosValues)) {
            $this->renderGridColumns($parentObject, $colPosValues, $gridContent, $gridElement, $editUidList,
                $singleColumn, $head);
        }
        $parentObject->tt_contentData['nextThree'] = $outerTtContentDataArray;

        // if we got a selected backend layout, we have to create the layout table now
        if ($layoutUid && isset($layout['config'])) {
            $itemContent = $this->renderGridLayoutTable($layout, $gridElement, $head, $gridContent);
        } else {
            $itemContent = '<div class="t3-grid-container t3-grid-element-container">';
            $itemContent .= '<table border="0" cellspacing="0" cellpadding="0" width="100%" height="100%" class="t3-page-columns t3-grid-table">';
            $itemContent .= '<tr><td valign="top" class="t3-grid-cell t3-page-column t3-page-column-0">' . 'AHAHAHAHAH' . '</td></tr>';
            $itemContent .= '</table></div>';
        }

        return $itemContent;
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

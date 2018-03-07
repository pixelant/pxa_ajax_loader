<?php
declare(strict_types=1);

namespace Pixelant\PxaAjaxLoader\Hooks;

use TYPO3\CMS\Backend\Controller\PageLayoutController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawFooterHookInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class PageLayoutViewHook
 * @package Pixelant\PxaAjaxLoader\Hooks
 */
class PageLayoutViewHook
{
    /**
     * Container field name in BD
     */
    const DB_FIELD_CONTAINER_NAME = 'tx_pxaajaxloader_container';

    /**
     * Default colPos
     */
    const COL_POS = -98;

    /**
     * HTML templates
     *
     * @var array
     */
    protected $templates = [
        'main' => '
<div class="t3-grid-container">
    <table border="0" cellspacing="0" cellpadding="0" width="100%%" class="t3-page-columns t3-grid-table t3js-page-columns">
       <colgroup><col></colgroup>
       <tbody>
            <tr>
                <td valign="top" colspan="1" rowspan="1" data-colpos="%1$s" data-language-uid="%2$d" class="t3js-page-lang-column-%2$d t3js-page-column t3-grid-cell t3-page-column">
                    <div class="t3-page-column-header">%3$s</div>
                    %4$s
                </td>
            </tr>
       </tbody>
    </table>          
</div>
        ',
        'link' => '<a href="%s" title="%s" class="btn btn-default btn-sm">%s %s</a>'
    ];

    /**
     * Show hidden records
     *
     * @var bool
     */
    protected $showHidden = false;

    /**
     * @var IconFactory
     */
    protected $iconFactory = null;

    /**
     * Stores whether a certain language has translations in it
     *
     * @var array
     */
    protected $languageHasTranslationsCache = [];

    /**
     * Initialize
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Render area for content
     *
     * @param array $params
     * @param PageLayoutView $pageLayoutView
     * @return string
     */
    public function render(array $params, PageLayoutView $pageLayoutView): string
    {
        $label = BackendUtility::getLabelFromItemListMerged(
            $params['row']['pid'],
            'tt_content',
            'list_type',
            $params['row']['list_type']
        );
        $this->showHidden = (bool)$pageLayoutView->tt_contentConfig['showHidden'];

        $columnHtml = $this->renderAjaxContentArea($pageLayoutView, $params['row']);

        return sprintf(
            $this->templates['main'],
            $this->getSpecificColPosUid($params['row']),
            $params['row']['sys_language_uid'],
            $this->getLanguageService()->sL($label),
            $columnHtml
        );
    }

    /**
     * Content for ajax
     *
     * @param PageLayoutView $pageLayoutView
     * @param array $row
     * @return string
     */
    protected function renderAjaxContentArea(PageLayoutView $pageLayoutView, array $row): string
    {
        $outerTtContentDataArray = $pageLayoutView->tt_contentData['nextThree'];

        $collectedItems = $this->collectItemsForColumns($pageLayoutView, $row);
        $html = $this->renderAjaxColumn($pageLayoutView, $collectedItems, $row);

        $pageLayoutView->tt_contentData['nextThree'] = $outerTtContentDataArray;

        return $html;
    }

    /**
     * Collects tt_content data from a single tt_content element
     *
     * @param PageLayoutView $parentObject : The paren object that triggered this hook
     * @param array $colPosValues : The column position to collect the items for
     * @param array $row : The current data row for the container item
     *
     * @return mixed collected items for the given column
     */
    protected function collectItemsForColumns(PageLayoutView $parentObject, array $row)
    {
        $specificIds = $this->getSpecificIds($row);

        $queryBuilder = $this->getQueryBuilder('tt_content');
        $constraints = [
            $queryBuilder->expr()->eq(
                'pid',
                $queryBuilder->createNamedParameter($specificIds['pid'], \PDO::PARAM_INT)
            ),
            $queryBuilder->expr()->eq(
                'colPos',
                $queryBuilder->createNamedParameter(self::COL_POS, \PDO::PARAM_INT)
            ),
            $queryBuilder->expr()->in(
                self::DB_FIELD_CONTAINER_NAME,
                $queryBuilder->createNamedParameter(
                    [(int)$specificIds['uid'], $specificIds['uid']],
                    Connection::PARAM_INT_ARRAY
                )
            )
        ];
        if (!$parentObject->tt_contentConfig['languageMode']) {
            $constraints[] = $queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(
                        (int)$parentObject->tt_contentConfig['sys_language_uid'],
                        \PDO::PARAM_INT
                    )
                )
            );
        } elseif ($row['sys_language_uid'] > 0) {
            $constraints[] = $queryBuilder->expr()->eq(
                'sys_language_uid',
                $queryBuilder->createNamedParameter((int)$row['sys_language_uid'], \PDO::PARAM_INT)
            );
        }
        if ($this->getBackendUser()->workspace > 0 && $row['t3ver_wsid'] > 0) {
            $constraints[] = $queryBuilder->expr()->eq(
                't3ver_wsid',
                $queryBuilder->createNamedParameter((int)$row['t3ver_wsid'], \PDO::PARAM_INT)
            );
        }

        $queryBuilder
            ->select('*')
            ->from('tt_content')
            ->where(
                ...$constraints
            )
            ->orderBy('sorting');

        $restrictions = $queryBuilder->getRestrictions();
        if ($this->showHidden) {
            $restrictions->removeByType(HiddenRestriction::class);
        }
        $restrictions->removeByType(StartTimeRestriction::class);
        $restrictions->removeByType(EndTimeRestriction::class);
        $queryBuilder->setRestrictions($restrictions);

        return $queryBuilder->execute()->fetchAll();
    }

    /**
     * Render html of ajax area
     *
     * @param PageLayoutView $parentObject
     * @param array $items
     * @param array $row
     * @return string
     */
    protected function renderAjaxColumn(
        PageLayoutView $parentObject,
        $items,
        $row
    ): string {
        $specificIds = $this->getSpecificIds($row);
        $html = '';
        $lP = (int)$row['sys_language_uid'];
        $pageInfo = BackendUtility::readPageAccess($parentObject->id, '');

        if (!empty($this->getPageLayoutController())
            && get_class($this->getPageLayoutController()) === PageLayoutController::class
        ) {
            $contentIsNotLockedForEditors = $this->getPageLayoutController()->contentIsNotLockedForEditors();
        } else {
            $contentIsNotLockedForEditors = true;
        }
        if ($contentIsNotLockedForEditors
            && $this->getBackendUser()->doesUserHaveAccess($pageInfo, Permission::CONTENT_EDIT)
            && (!$this->checkIfTranslationsExistInLanguage($items, $lP, $parentObject))
        ) {
            if ($parentObject->option_newWizard) {
                $urlParameters = [
                    'id' => $parentObject->id,
                    'sys_language_uid' => $lP,
                    self::DB_FIELD_CONTAINER_NAME => $specificIds['uid'],
                    'colPos' => self::COL_POS,
                    'uid_pid' => $parentObject->id,
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                ];
                $url = BackendUtility::getModuleUrl('new_content_element', $urlParameters);
            } else {
                $urlParameters = [
                    'edit' => [
                        'tt_content' => [
                            $parentObject->id => 'new',
                        ],
                    ],
                    'defVals' => [
                        'tt_content' => [
                            'sys_language_uid' => $lP,
                            self::DB_FIELD_CONTAINER_NAME => $specificIds['uid'],
                            'colPos' => self::COL_POS,
                        ],
                    ],
                    'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                ];
                $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
            }
        }

        if (isset($url)) {
            $link = sprintf(
                $this->templates['link'],
                htmlspecialchars($url),
                $this->getLanguageService()->getLL(
                    'newContentElement',
                    true
                ),
                $this->iconFactory->getIcon(
                    'actions-document-new',
                    'small'
                ),
                $this->getLanguageService()->getLL('content', true)
            );
        }

        // Start wrapping div
        $html .= '<div data-colpos="' . $this->getSpecificColPosUid($row) . '" data-language-uid="' . $lP . '" class="t3js-sortable t3js-sortable-lang t3js-sortable-lang-' . $lP . ' t3-page-ce-wrapper';
        if (empty($items)) {
            $html .= ' t3-page-ce-empty';
        }
        $html .= '">';

        $html .= '
            <div class="t3-page-ce t3js-page-ce" data-page="' . $parentObject->id . '" id="' . StringUtility::getUniqueId() . '">
                <div class="t3js-page-new-ce t3-page-ce-wrapper-new-ce" id="colpos-' . self::COL_POS . '-' . 'page-' . $id . '-' . StringUtility::getUniqueId() . '">'
            . ($link ?? '')
            . '</div>
                <div class="t3-page-ce-dropzone-available t3js-page-ce-dropzone-available"></div>
            </div>';

        foreach ($items as $item) {
            if ((int)$item['t3ver_state'] === VersionState::DELETE_PLACEHOLDER) {
                continue;
            }
            if (is_array($item)) {
                $pid = (int)$item['pid'];
                $container = (int)$item[self::DB_FIELD_CONTAINER_NAME];
                $language = (int)$item['sys_language_uid'];
                $statusHidden = $parentObject->isDisabled('tt_content', $item) ? ' t3-page-ce-hidden' : '';
                $displayNone = !$parentObject->tt_contentConfig['showHidden'] && $statusHidden ?
                    ' style="display: none;"' : '';

                $html .= '
				<div class="t3-page-ce t3js-page-ce t3js-page-ce-sortable' . $statusHidden . '" 
				      id="element-tt_content-' . $row['uid'] . '"
				      data-table="tt_content"
				      data-uid="' . $item['uid'] . '"' . $displayNone . '>' .
                    $this->renderSingleElementHTML($parentObject, $item) .
                    '</div>';
                if ($contentIsNotLockedForEditors
                    && $this->getBackendUser()->doesUserHaveAccess($pageInfo, Permission::CONTENT_EDIT)
                    && (!$this->checkIfTranslationsExistInLanguage($items, $row['sys_language_uid'], $parentObject))
                ) {
                    // New content element:
                    $specificIds = $this->getSpecificIds($item);
                    if ($parentObject->option_newWizard) {
                        $urlParameters = [
                            'id' => $parentObject->id,
                            'sys_language_uid' => $language,
                            self::DB_FIELD_CONTAINER_NAME => $container,
                            'colPos' => self::COL_POS,
                            'uid_pid' => -$specificIds['uid'],
                            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                        ];
                        $url = BackendUtility::getModuleUrl('new_content_element', $urlParameters);
                    } else {
                        $urlParameters = [
                            'edit' => [
                                'tt_content' => [
                                    -$specificIds['uid'] => 'new',
                                ],
                            ],
                            'defVals' => [
                                'tt_content' => [
                                    'sys_language_uid' => $language,
                                    self::DB_FIELD_CONTAINER_NAME => $container,
                                    'colPos' => self::COL_POS,
                                ],
                            ],
                            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI'),
                        ];
                        $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
                    }
                    $link = sprintf(
                        $this->templates['link'],
                        htmlspecialchars($url),
                        $this->getLanguageService()->getLL('newContentElement', true),
                        $this->iconFactory->getIcon('actions-document-new', 'small'),
                        $this->getLanguageService()->getLL('content', true)
                    );

                    $html .= '
                    <div class="t3js-page-new-ce t3js-page-new-ce-allowed t3-page-ce-wrapper-new-ce btn-group btn-group-sm"'
                        . 'id="colpos-' . self::COL_POS . '-page-' . $pid . '-' . StringUtility::getUniqueId() . '">'
                        . $link . '
                     </div>';
                }

                $html .= '<div class="t3-page-ce-dropzone-available t3js-page-ce-dropzone-available"></div></div>';
            }
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @return PageLayoutController
     */
    public function getPageLayoutController()
    {
        return $GLOBALS['SOBE'];
    }

    /**
     * Query builder instance
     *
     * @param string $table
     * @return QueryBuilder
     */
    protected function getQueryBuilder(string $table): QueryBuilder
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);

        return $queryBuilder;
    }

    /**
     * Gets the uid of a record depending on the current context.
     * If in workspace mode, the overlay uid is used (if available),
     * otherwise the regular uid is used.
     *
     * @param array $record Overlaid record data
     *
     * @return int[]
     */
    public function getSpecificIds(array $record): array
    {
        $specificIds = [];
        $specificIds['uid'] = (int)$record['uid'];
        $specificIds['pid'] = (int)$record['pid'];

        if ($this->getBackendUser()->workspace > 0 && !empty($record['t3ver_oid'])) {
            $specificIds['uid'] = (int)$record['t3ver_oid'];
            $specificIds['pid'] = -1;
        }

        return $specificIds;
    }

    /**
     * Special colPos
     *
     * @param array $row
     * @return string
     */
    public function getSpecificColPosUid(array $row): string
    {
        return self::COL_POS . '|' . $row['uid'];
    }

    /**
     * Checks whether translated Content Elements exist in the desired language
     * If so, deny creating new ones via the UI
     * @param array $contentElements
     * @param int $language
     * @param PageLayoutView $parentObject
     * @return bool
     */
    protected function checkIfTranslationsExistInLanguage(
        array $contentElements,
        int $language,
        PageLayoutView $parentObject
    ): bool {   // If in default language, you may always create new entries
        // Also, you may override this strict behavior via user TS Config
        // If you do so, you're on your own and cannot rely on any support by the TYPO3 core
        // We jump out here since we don't need to do the expensive loop operations
        $allowInconsistentLanguageHandling = BackendUtility::getModTSconfig(
            $parentObject->id,
            'mod.web_layout.allowInconsistentLanguageHandling'
        );
        if ($language === 0 || $allowInconsistentLanguageHandling['value'] === '1') {
            return false;
        }
        /**
         * Build up caches
         */
        if (!isset($this->languageHasTranslationsCache[$language])) {
            foreach ($contentElements as $contentElement) {
                if ((int)$contentElement['l18n_parent'] === 0) {
                    $this->languageHasTranslationsCache[$language]['hasStandAloneContent'] = true;
                    $this->languageHasTranslationsCache[$language]['mode'] = 'free';
                }
                if ((int)$contentElement['l18n_parent'] > 0) {
                    $this->languageHasTranslationsCache[$language]['hasTranslations'] = true;
                    $this->languageHasTranslationsCache[$language]['mode'] = 'connected';
                }
            }
            // Check whether we have a mix of both
            if ($this->languageHasTranslationsCache[$language]['hasStandAloneContent']
                && $this->languageHasTranslationsCache[$language]['hasTranslations']
            ) {
                $this->languageHasTranslationsCache[$language]['mode'] = 'mixed';
                /** @var FlashMessage $message */
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    sprintf(
                        $this->getLanguageService()->getLL('staleTranslationWarning'),
                        $parentObject->languageIconTitles[$language]['title']
                    ),
                    sprintf(
                        $this->getLanguageService()->getLL('staleTranslationWarningTitle'),
                        $parentObject->languageIconTitles[$language]['title']
                    ),
                    FlashMessage::WARNING
                );
                /** @var FlashMessageService $service */
                $service = GeneralUtility::makeInstance(FlashMessageService::class);
                $queue = $service->getMessageQueueByIdentifier();
                $queue->addMessage($message);
            }
        }
        if ($this->languageHasTranslationsCache[$language]['hasTranslations']) {
            return true;
        }
        return false;
    }

    /**
     * Renders the HTML code for a single tt_content element
     *
     * @param PageLayoutView $parentObject : The parent object that triggered this hook
     * @param array $item : The data row to be rendered as HTML
     *
     * @return string
     */
    protected function renderSingleElementHTML(PageLayoutView $parentObject, array $item): string
    {
        $singleElementHTML = '';
        $parentObject->tt_contentData['nextThree'][$item['uid']] = $item['uid'];
        if (!$parentObject->tt_contentConfig['languageMode']) {
            $singleElementHTML .= '<div class="t3-page-ce-dragitem" id="' . StringUtility::getUniqueId() . '">';
        }
        $singleElementHTML .= $parentObject->tt_content_drawHeader(
            $item,
            $parentObject->tt_contentConfig['showInfo'] ? 15 : 5,
            $parentObject->defLangBinding,
            true,
            true
        );
        $singleElementHTML .= (!empty($item['_ORIG_uid']) ? '<div class="ver-element">' : '')
            . '<div class="t3-page-ce-body-inner t3-page-ce-body-inner-' . $item['CType'] . '">'
            . $parentObject->tt_content_drawItem($item)
            . '</div>'
            . (!empty($item['_ORIG_uid']) ? '</div>' : '');
        $singleElementHTML .= $this->tt_content_drawFooter($parentObject, $item);
        if (!$parentObject->tt_contentConfig['languageMode']) {
            $singleElementHTML .= '</div>';
        }
        unset($parentObject->tt_contentData['nextThree'][$item['uid']]);

        return $singleElementHTML;
    }

    /**
     * Draw the footer for a single tt_content element
     *
     * @param PageLayoutView $parentObject : The parent object that triggered this hook
     * @param array $row Record array
     * @return string HTML of the footer
     * @throws \UnexpectedValueException
     */
    protected function tt_content_drawFooter(PageLayoutView $parentObject, array $row): string
    {
        $content = '';
        // Get processed values:
        $info = [];
        $parentObject->getProcessedValue(
            'tt_content',
            'starttime,endtime,fe_group,spaceBefore,spaceAfter',
            $row,
            $info
        );

        // Content element annotation
        if (!empty($GLOBALS['TCA']['tt_content']['ctrl']['descriptionColumn'])) {
            $info[] = htmlspecialchars($row[$GLOBALS['TCA']['tt_content']['ctrl']['descriptionColumn']]);
        }

        // Call drawFooter hooks
        $drawFooterHooks = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawFooter'];
        if (is_array($drawFooterHooks)) {
            foreach ($drawFooterHooks as $hookClass) {
                $hookObject = GeneralUtility::getUserObj($hookClass);
                if (!$hookObject instanceof PageLayoutViewDrawFooterHookInterface) {
                    throw new \UnexpectedValueException(
                        $hookClass . ' must implement interface ' . PageLayoutViewDrawFooterHookInterface::class,
                        1404378171
                    );
                }
                $hookObject->preProcess($parentObject, $info, $row);
            }
        }

        // Display info from records fields:
        if (!empty($info)) {
            $content = '<div class="t3-page-ce-info">
				' . implode('<br>', $info) . '
				</div>';
        }
        // Wrap it
        if (!empty($content)) {
            $content = '<div class="t3-page-ce-footer">' . $content . '</div>';
        }

        return $content;
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

    /**
     * Gets the current backend user.
     *
     * @return BackendUserAuthentication
     */
    public function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}

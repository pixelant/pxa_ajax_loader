<?php
declare(strict_types=1);

namespace Pixelant\PxaAjaxLoader\Controller;

use Pixelant\PxaAjaxLoader\Hooks\PageLayoutViewHook;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class AjaxLoaderController
 * @package Pixelant\PxaPagetypeAjaxLoader\Controller
 */
class AjaxLoaderController extends ActionController
{

    /**
     * @var TypoScriptFrontendController
     */
    protected $tsfe = null;

    /**
     * Initialize
     */
    public function initializeAction()
    {
        $this->tsfe = $GLOBALS['TSFE'];
    }

    /**
     * Generate url for ajax
     *
     * @return void
     */
    public function placeholderAction()
    {
        $uriBuilder = $this->controllerContext->getUriBuilder()->reset();
        $uriBuilder
            ->setTargetPageUid($this->tsfe->id)
            ->setTargetPageType($this->settings['pageType'])
            ->setCreateAbsoluteUri(true);

        $this->view->assign(
            'url',
            $uriBuilder->uriFor('load', ['pluginUid' => $this->getPluginUid()])
        );
    }

    /**
     * Load content
     *
     * @param int $pluginUid
     * @return void
     */
    public function loadAction(int $pluginUid)
    {
        $this->view->assign('html', $this->renderAjaxContent($pluginUid));
    }

    /**
     * Render content inside
     *
     * @param int $pluginUid
     * @return string
     * @throws \Exception
     * @throws \TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException
     */
    protected function renderAjaxContent(int $pluginUid)
    {
        $content = '';
        $cObj = $this->configurationManager->getContentObject();
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $GLOBALS['TSFE'];

        $ttContentConfig = [
            'table' => 'tt_content',
            'select.' => [
                'pidInList' => $tsfe->id,
                'orderBy' => 'sorting',
                'where' => 'colPos=' . PageLayoutViewHook::COL_POS .
                    ' AND ' . PageLayoutViewHook::DB_FIELD_CONTAINER_NAME . '=' . (int)$pluginUid,
                'languageField' => 'sys_language_uid'
            ],
        ];

        $content .= $cObj->render($cObj->getContentObject('CONTENT'), $ttContentConfig);

        return $content;
    }

    /**
     * Get plugin tt_content UID
     *
     * @return int
     */
    protected function getPluginUid(): int
    {
        return $this->configurationManager->getContentObject()->data['uid'];
    }
}

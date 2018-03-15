<?php
declare(strict_types=1);

namespace Pixelant\PxaAjaxLoader\Controller;

use Pixelant\PxaAjaxLoader\Hooks\PageLayoutViewHook;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
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
     * @param int $pluginUid Container plugin uid
     * @param string $typoscriptObject
     * @return void
     */
    public function loadAction(int $pluginUid = 0, string $typoscriptObject = '')
    {
        $html = '';
        if ($pluginUid > 0) {
            $html .= $this->renderAjaxContentOfPlugin($pluginUid);
        } elseif (!empty($typoscriptObject)) {
            $html .= $this->renderTypoScriptObject($typoscriptObject);
        }

        $this->view->assign('html', $html);
    }

    /**
     * Render typoscript object
     *
     * @param string $typoscriptObject
     * @return string
     */
    protected function renderTypoScriptObject(string $typoscriptObject): string
    {
        $typoScriptSetup = $this->getTypoScriptSetup();
        $typoscriptObjectSegments = GeneralUtility::trimExplode('.', $typoscriptObject, true);

        do {
            $pathSegment = array_shift($typoscriptObjectSegments);
            $countSegments = count($typoscriptObjectSegments);

            if (array_key_exists($pathSegment . '.', $typoScriptSetup)) {
                if ($countSegments !== 0) {
                    $typoScriptSetup = $typoScriptSetup[$pathSegment . '.'];
                }
            } else {
                // If at lest one doesn't exist, then path is wrong
                return '';
            }
        } while ($countSegments > 0);

        return $this->getContentObjectRenderer()->cObjGetSingle(
            $typoScriptSetup[$pathSegment],
            $typoScriptSetup[$pathSegment . '.']
        );
    }

    /**
     * Get typoscript configuration
     *
     * @return array
     */
    protected function getTypoScriptSetup(): array
    {
        /** @var ConfigurationManagerInterface $configurationManager */
        $configurationManager = $this->objectManager->get(ConfigurationManagerInterface::class);
        return $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT
        );
    }

    /**
     * Wrapper for testing
     *
     * @return ContentObjectRenderer|object
     */
    protected function getContentObjectRenderer(): ContentObjectRenderer
    {
        return $this->objectManager->get(ContentObjectRenderer::class);
    }

    /**
     * Render content inside
     *
     * @param int $pluginUid
     * @return string
     * @throws \Exception
     */
    protected function renderAjaxContentOfPlugin(int $pluginUid): string
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

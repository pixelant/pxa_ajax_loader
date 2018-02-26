<?php
declare(strict_types=1);
namespace Pixelant\PxaAjaxLoader\Controller;

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
            $uriBuilder->uriFor('load', ['pluginUid' => $this->getPluginUid()], 'AjaxJsonLoader')
        );
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

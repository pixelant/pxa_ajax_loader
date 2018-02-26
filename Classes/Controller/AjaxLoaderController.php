<?php

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
     * generate url for ajax
     *
     * @return void
     */
    public function mainAction()
    {
        $uriBuilder = $this->controllerContext->getUriBuilder()->reset();
        $uriBuilder
            ->setTargetPageUid($this->getRootLinePage())
            ->setTargetPageType($this->settings['pageType'])
            ->setCreateAbsoluteUri(true);

        $this->view->assign('url', $uriBuilder->buildFrontendUri());
    }

    /**
     * Root page uid or current one if not found
     *
     * @return int
     */
    protected function getRootLinePage()
    {
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $GLOBALS['TSFE'];
        $rootPage = $tsfe->id;

        foreach ($tsfe->rootLine as $item) {
            if ((int)$item['is_site_root'] === 1) {
                $rootPage = $item['uid'];
                break;
            }
        }

        return $rootPage;
    }
}

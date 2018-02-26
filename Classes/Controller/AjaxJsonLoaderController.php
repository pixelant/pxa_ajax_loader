<?php
declare(strict_types=1);
namespace Pixelant\PxaAjaxLoader\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;

/**
 * Class AjaxJsonLoaderController
 * @package Pixelant\PxaAjaxLoader\Controller
 */
class AjaxJsonLoaderController extends ActionController
{
    /**
     * Default view
     *
     * @var string
     */
    protected $defaultViewObjectName = JsonView::class;

    /**
     * Load content
     *
     * @param int $pluginUid
     * @return void
     */
    public function loadAction(int $pluginUid)
    {
        $response = [
            'success' => true,
            'html' => '<b>LOL!!!!!</b>'
        ];

        $this->view->assign('value', $response);
    }
}

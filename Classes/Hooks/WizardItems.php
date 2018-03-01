<?php
declare(strict_types=1);

namespace Pixelant\PxaAjaxLoader\Hooks;

use TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController;
use TYPO3\CMS\Backend\Wizard\NewContentElementWizardHookInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class WizardItems
 * @package Pixelant\PxaAjaxLoader\Hooks
 */
class WizardItems implements NewContentElementWizardHookInterface
{
    /**
     * Processes the items of the new content element wizard
     * and inserts necessary default values for items created within a grid
     *
     * @param array $wizardItems The array containing the current status of the wizard item list before rendering
     * @param NewContentElementController $parentObject The parent object that triggered this hook
     */
    public function manipulateWizardItems(&$wizardItems, &$parentObject)
    {
        $container = (int)GeneralUtility::_GP(PageLayoutViewHook::DB_FIELD_CONTAINER_NAME);
        if ($container === 0) {
            return;
        }
        foreach ($wizardItems as $key => $wizardItem) {
            if (!$wizardItems[$key]['header']) {
                $wizardItems[$key]['tt_content_defValues'][PageLayoutViewHook::DB_FIELD_CONTAINER_NAME] = (int)$container;
                $wizardItems[$key]['params'] .= '&defVals[tt_content][' . PageLayoutViewHook::DB_FIELD_CONTAINER_NAME . ']=' . (int)$container;
            }
        }
    }
}

<?php
namespace Pixelant\PxaAjaxLoader\Tests\Unit\Hooks;

use PHPUnit\Framework\TestCase;
use Pixelant\PxaAjaxLoader\Hooks\WizardItems;
use TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController;

/**
 * Class WizardItemsTest
 * @package Pixelant\PxaAjaxLoader\Tests\Unit\Hooks
 */
class WizardItemsTest extends TestCase
{
    /**
     * @var WizardItems
     */
    protected $subject = null;

    public function setUp()
    {
        $this->subject = new WizardItems();
    }

    /**
     * @test
     */
    public function wizardItemsDoesntChangeIfNoContainer()
    {
        $wizardItems = [
            'test' => [
                'header' => '',
                'tt_content_defValues' => ['val' => 'test'],
                'params' => '&test=1'
            ]
        ];
        $expectedWizardItems = $wizardItems;

        $mockedNewContentElementController = $this->createMock(NewContentElementController::class);

        $this->subject->manipulateWizardItems($wizardItems, $mockedNewContentElementController);
        $this->assertEquals($expectedWizardItems, $wizardItems);
    }

    /**
     * @test
     */
    public function wizardItemsAppendWithParamsIfContainerIsSetAndAjaxContainerRemoved()
    {
        $_GET['tx_pxaajaxloader_container'] = 123;
        $mockedNewContentElementController = $this->createMock(NewContentElementController::class);

        $wizardItems = [
            'test' => [
                'header' => '',
                'tt_content_defValues' => ['val' => 'test'],
                'params' => '&test=1'
            ],
            'plugins_plugins_tx_pxaajaxloader_loader' => [
                'header' => 'Plugin header'
            ]
        ];
        $expectedWizardItems = $wizardItems;
        unset($expectedWizardItems['plugins_plugins_tx_pxaajaxloader_loader']);
        $expectedWizardItems['test']['tt_content_defValues']['tx_pxaajaxloader_container'] = 123;
        $expectedWizardItems['test']['params'] .= '&defVals[tt_content][tx_pxaajaxloader_container]=123';

        $this->subject->manipulateWizardItems($wizardItems, $mockedNewContentElementController);

        $this->assertEquals($expectedWizardItems, $wizardItems);
    }
}

<?php
namespace Pixelant\PxaAjaxLoader\Tests\Unit\UserFunction\ItemsProcFuncs;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pixelant\PxaAjaxLoader\UserFunction\ItemsProcFuncs\ColPosList;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class ColPosListTest
 * @package Pixelant\PxaAjaxLoader\Tests\Unit\UserFunction\ItemsProcFuncs
 */
class ColPosListTest extends TestCase
{
    /**
     * @var ColPosList
     */
    protected $subject = null;

    /**
     * @var LanguageService|MockObject
     */
    protected $mockedLanguageService = null;

    public function setUp()
    {
        $this->subject = new ColPosList();

        $this->mockedLanguageService = $this->createPartialMock(LanguageService::class, ['sL']);
        $GLOBALS['LANG'] = $this->mockedLanguageService;
    }

    /**
     * @test
     */
    public function rewriteColPostSelectItemsIfAjaxContainer()
    {
        $mockedTcaSelectItems = $this->createMock(TcaSelectItems::class);


        $params = [
            'row' => [
                'tx_pxaajaxloader_container' => 1
            ],
            'items' => [
                ['Will be removed', 1 , null, null]
            ]
        ];
        $expectedItems = [
            ['Ajax container', -98, null, null]
        ];

        $this->mockedLanguageService
            ->expects($this->once())
            ->method('sL')
            ->will($this->returnValue('Ajax container'));

        $this->subject->processTTContentItems($params, $mockedTcaSelectItems);

        $this->assertEquals($expectedItems, $params['items']);
    }

    /**
     * @test
     */
    public function ifRowHasNoAjaxContainerDontChangeItems()
    {
        $mockedTcaSelectItems = $this->createMock(TcaSelectItems::class);


        $params = [
            'row' => [
                'uid' => 1
            ],
            'items' => [
                ['Will not be removed', 1 , null, null]
            ]
        ];
        $expectedItems = [
            ['Will not be removed', 1 , null, null]
        ];

        $this->mockedLanguageService
            ->expects($this->never())
            ->method('sL');

        $this->subject->processTTContentItems($params, $mockedTcaSelectItems);

        $this->assertEquals($expectedItems, $params['items']);
    }

    public function tearDown()
    {
        unset($this->mockedLanguageService, $GLOBALS['LANG']);
    }
}

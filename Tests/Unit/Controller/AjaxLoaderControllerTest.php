<?php

namespace Pixelant\PxaAjaxLoader\Tests\Unit\Controller;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pixelant\PxaAjaxLoader\Controller\AjaxLoaderController;
use Pixelant\PxaAjaxLoader\Tests\Unit\InvokePrivateMethodTrait;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Class AjaxLoaderControllerTest
 * @package Pixelant\PxaAjaxLoader\Tests\Unit\Controller
 */
class AjaxLoaderControllerTest extends TestCase
{
    use InvokePrivateMethodTrait;

    /**
     * @var AjaxLoaderController|MockObject
     */
    protected $subject = null;

    public function setUp()
    {
        $this->subject = $this->createPartialMock(AjaxLoaderController::class, ['getTypoScriptSetup', 'getContentObjectRenderer']);
    }

    /**
     * @test
     */
    public function walkingThroughTypoScriptArrayRenderCorrectTypoScript()
    {
        $tsObject = 'TEXT';
        $tsConfig = [
            'value' => 'TEST',
            'wrap' => '<span>|</span>'
        ];
        $typoscriptObject = 'lib.content.main.head_line';

        $typoScriptSetup = [
            'lib.' => [
                'content.' => [
                    'main.' => [
                        'head_line' => $tsObject,
                        'head_line.' => $tsConfig
                    ],
                    'test.' => 'test'
                ]
            ],
            'page.' => [
                'test' => 'test'
            ]
        ];

        $mockedCntentObjectRenderer = $this->createPartialMock(ContentObjectRenderer::class, ['cObjGetSingle']);
        $mockedCntentObjectRenderer
            ->expects($this->once())
            ->method('cObjGetSingle')
            ->with($tsObject, $tsConfig)
            ->will($this->returnValue(''));

        $this->subject
            ->expects($this->once())
            ->method('getTypoScriptSetup')
            ->will($this->returnValue($typoScriptSetup));

        $this->subject
            ->expects($this->once())
            ->method('getContentObjectRenderer')
            ->will($this->returnValue($mockedCntentObjectRenderer));


        $this->invokeMethod($this->subject, 'renderTypoScriptObject', [$typoscriptObject]);
    }

    /**
     * @test
     */
    public function walkingThroughTypoScriptWithNoMatchReturnEmptyString()
    {
        $tsObject = 'TEXT';
        $tsConfig = [
            'value' => 'TEST',
            'wrap' => '<span>|</span>'
        ];
        $typoscriptObject = 'lib.content.non_existing';

        $typoScriptSetup = [
            'lib.' => [
                'content.' => [
                    'main.' => [
                        'head_line' => $tsObject,
                        'head_line.' => $tsConfig
                    ],
                    'test.' => 'test'
                ]
            ],
            'page.' => [
                'test' => 'test'
            ]
        ];

        $this->subject
            ->expects($this->once())
            ->method('getTypoScriptSetup')
            ->will($this->returnValue($typoScriptSetup));

        $this->subject
            ->expects($this->never())
            ->method('getContentObjectRenderer');


        $result = $this->invokeMethod($this->subject, 'renderTypoScriptObject', [$typoscriptObject]);
        $this->assertEmpty($result);
    }

    public function tearDown()
    {
        unset($this->subject);
    }
}

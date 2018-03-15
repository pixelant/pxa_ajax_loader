<?php

namespace Pixelant\PxaAjaxLoader\Tests\Unit\Hooks;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pixelant\PxaAjaxLoader\Hooks\DataHandler;
use Pixelant\PxaAjaxLoader\Tests\Unit\InvokePrivateMethodTrait;

/**
 * Class DataHandlerTest
 * @package Pixelant\PxaAjaxLoader\Tests\Unit\Hooks
 */
class DataHandlerTest extends TestCase
{
    use InvokePrivateMethodTrait;

    /**
     * @var DataHandler|MockObject
     */
    protected $subject = null;

    public function setUp()
    {
        $this->subject = new DataHandler();
    }

    /**
     * @test
     */
    public function determinateAjaxContainerOnlyForValidColPos()
    {
        $value = '1|123';

        $this->assertEquals(0, $this->invokeMethod($this->subject, 'determinateAjaxContainer', [$value]));
    }

    /**
     * @test
     */
    public function determinateAjaxContainerReturnAjaxContainer()
    {
        $value = '-98|123';

        $this->assertEquals(123, $this->invokeMethod($this->subject, 'determinateAjaxContainer', [$value]));
    }

    public function tearDown()
    {
        unset($this->subject);
    }
}

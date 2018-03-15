<?php
namespace Pixelant\PxaAjaxLoader\Tests\Unit;

/**
 * Trait for call private/protected methods
 *
 * @package Pixelant\PxaAjaxLoader\Tests\Unit\UserFunction\ItemsProcFuncs
 */
trait InvokePrivateMethodTrait
{
    /**
     * Call protected/private method of a class.
     *
     * @param object $object Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}

<?php

declare(strict_types=1);

namespace yiiunit\framework\di;

use Yii;
use yii\base\InvalidConfigException;
use yii\di\{Container, ReflectionFactory};
use yiiunit\framework\di\stubs\{Beta, CarTunning, EngineInterface, EngineMarkOne, EngineMarkOneInmutable, Qux};
use yiiunit\TestCase;

/**
 * @group di
 * @group reflection
 */
final class ReflectionFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Yii::$container = new Container();
    }

    public function testCreateObject(): void
    {
        $container = new Container();
        $factory = new ReflectionFactory($container);

        $class = Qux::class;
        $config = ['a' => 5];
        $object = $factory->create($class, [], $config);
        $this->assertInstanceOf($class, $object);
        $this->assertEquals(5, $object->a);
    }

    public function testCreateObjectWithConfigAndParams(): void
    {
        $container = new Container();
        $factory = new ReflectionFactory($container);

        $class = Qux::class;
        $params = [5];
        $config = ['a' => 10];

        $object = $factory->create($class, $params, $config);

        $this->assertInstanceOf($class, $object);
        $this->assertEquals(10, $object->a);
    }

    public function testCreateObjectWithConfigConstructor(): void
    {
        $container = new Container();
        $factory = new ReflectionFactory($container);

        $class = CarTunning::class;
        $config = ['__construct()' => ['blue']];
        $object = $factory->create($class, [], $config);

        $this->assertInstanceOf($class, $object);
        $this->assertSame('blue', $object->color);
    }

    public function testCreateObjectWithConfigPublicProperty(): void
    {
        $container = new Container();
        $factory = new ReflectionFactory($container);

        $class = CarTunning::class;
        $config = ['color' => 'red'];
        $object = $factory->create($class, [], $config);

        $this->assertInstanceOf($class, $object);
        $this->assertSame('red', $object->color);
    }

    public function testCreateObjectWithConfigWithSetter(): void
    {
        $container = new Container();
        $factory = new ReflectionFactory($container);

        $class = EngineMarkOne::class;

        $object = $factory->create($class, [], ['setNumber()' => [7]]);

        $this->assertInstanceOf($class, $object);
        $this->assertSame(7, $object->getNumber());
    }

    public function testCreateObjectWithConfigWithSetterInmutable(): void
    {
        $container = new Container();
        $factory = new ReflectionFactory($container);

        $class = EngineMarkOneInmutable::class;

        $object = $factory->create($class, [], ['withNumber()' => [5]]);

        $this->assertInstanceOf($class, $object);
        $this->assertSame(5, $object->getNumber());
    }

    public function testCreateObjectNotInstantiableClass(): void
    {
        $container = new Container();
        $factory = new ReflectionFactory($container);

        $class = EngineInterface::class;

        $this->expectException(\yii\di\NotInstantiableException::class);

        $factory->create($class);
    }

    public function testResolveCallableDependenciesWithAssociativeParams(): void
    {
        $container = new Container();
        $factory = new ReflectionFactory($container);

        $callable = function (Beta $param) {
            return $param;
        };

        $testInstance = new Beta();
        $params = ['param' => $testInstance];
        $result = $factory->resolveCallableDependencies($callable, $params);

        $this->assertCount(1, $result);
        $this->assertSame($testInstance, $result[0]);
    }

    public function testResolveCallableDependenciesWithNonAssociativeParams(): void
    {
        $container = new Container();
        $factory = new ReflectionFactory($container);

        $callable = function (Beta $param, $optionalParam = null) {
            return [$param, $optionalParam];
        };

        $testInstance = new Beta();
        $params = [$testInstance];
        $result = $factory->resolveCallableDependencies($callable, $params);

        $this->assertCount(2, $result);
        $this->assertSame($testInstance, $result[0]);
        $this->assertNull($result[1]);
    }

    public function testResolveCallableDependenciesThrowsExceptionForMissingRequiredParameter(): void
    {
        $container = new Container();
        $factory = new ReflectionFactory($container);

        $callable = function (string $requiredParam) {
            return $requiredParam;
        };

        $params = [];

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Missing required parameter "requiredParam" when calling');

        $factory->resolveCallableDependencies($callable, $params);
    }

    public function testResolveCallableDependenciesHandlesAdditionalParams(): void
    {
        $container = new Container();
        $factory = new ReflectionFactory($container);

        $callable = function (string $param1, ...$additionalParams) {
            return [$param1, $additionalParams];
        };
        $params = ['First', 'Second', 'Third'];
        $result = $factory->resolveCallableDependencies($callable, $params);

        $this->assertCount(3, $result);
        $this->assertEquals('First', $result[0]);
        $this->assertEquals('Second', $result[1]);
        $this->assertEquals('Third', $result[2]);
    }

}

<?php

declare(strict_types=1);

namespace yiiunit\framework\di;

use yii\base\{InvalidConfigException, UnknownPropertyException};
use yii\di\ServiceLocator;
use yiiunit\framework\di\stubs\{Creator, ServiceLocatorStub, TestClass, TestSubclass};
use yiiunit\TestCase;

/**
 * @group di
 */
class ServiceLocatorTest extends TestCase
{
    public function testCallable(): void
    {
        // anonymous function
        $container = new ServiceLocator();
        $className = TestClass::class;
        $container->set(
            $className,
            static function (): TestClass {
                return new TestClass(['prop1' => 100, 'prop2' => 200]);
            }
        );
        $object = $container->get($className);
        $this->assertInstanceOf($className, $object);
        $this->assertEquals(100, $object->prop1);
        $this->assertEquals(200, $object->prop2);

        // static method
        $container = new ServiceLocator();
        $className = TestClass::class;
        $container->set($className, [Creator::class, 'create']);
        $object = $container->get($className);
        $this->assertInstanceOf($className, $object);
        $this->assertEquals(1, $object->prop1);
        $this->assertNull($object->prop2);
    }

    public function testClear(): void
    {
        $container = new ServiceLocator();

        $className = TestClass::class;
        $container->set($className, new TestClass());
        $this->assertTrue($container->has($className));

        $container->clear($className);
        $this->assertFalse($container->has($className));
    }

    public function testDi3Compatibility(): void
    {
        $config = [
            'components' => [
                'test' => [
                    'class' => TestClass::class,
                ],
            ],
        ];

        // User Defined Config
        $config['components']['test']['__class'] = TestSubclass::class;

        $app = new ServiceLocator($config);
        $this->assertInstanceOf(TestSubclass::class, $app->get('test'));
    }

    public function testGetComponents(): void
    {
        $config = [
            'components' => [
                'test' => [
                    'class' => TestClass::class,
                ],
            ],
        ];

        $app = new ServiceLocator($config);
        $this->assertSame($config['components'], $app->getComponents());
    }

    public function testGetException(): void
    {
        $container = new ServiceLocator();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Unknown component ID: test');

        $container->get('test');
    }

    public function testGetExceptionWithThrowExceptionFalse(): void
    {
        $container = new ServiceLocator();

        $this->assertNull($container->get('test', false));
    }

    public function testGetReturnsParentGet(): void
    {
        $serviceLocator = new ServiceLocatorStub();

        $this->assertEquals('test value', $serviceLocator->testProperty);

        $this->expectException(UnknownPropertyException::class);
        $serviceLocator->nonExistentProperty;

        $this->expectException(InvalidConfigException::class);
        $serviceLocator->undefinedComponent;

        $serviceLocator->set('definedComponent', 'component value');
        $this->assertEquals('component value', $serviceLocator->definedComponent);
    }

    public function testIssetReturnsParentIsset(): void
    {
        $serviceLocator = new ServiceLocatorStub();

        $this->assertTrue(isset($serviceLocator->testProperty));

        $serviceLocator->testProperty = null;
        $this->assertFalse(isset($serviceLocator->testProperty));

        $serviceLocator->testProperty = 'test';
        $this->assertTrue(isset($serviceLocator->testProperty));
        $this->assertFalse(isset($serviceLocator->undefinedComponent));

        $serviceLocator->set('definedComponent', 'some value');
        $this->assertTrue(isset($serviceLocator->definedComponent));
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/11771
     */
    public function testModulePropertyIsset(): void
    {
        $config = [
            'components' => [
                'inputWidget' => [
                    'name' => 'foo bar',
                    'class' => 'yii\widgets\InputWidget',
                ],
            ],
        ];

        $app = new ServiceLocator($config);

        $this->assertTrue(isset($app->inputWidget->name));
        $this->assertNotEmpty($app->inputWidget->name);

        $this->assertEquals('foo bar', $app->inputWidget->name);

        $this->assertTrue(isset($app->inputWidget->name));
        $this->assertNotEmpty($app->inputWidget->name);
    }

    public function testObject(): void
    {
        $object = new TestClass();
        $className = TestClass::class;
        $container = new ServiceLocator();
        $container->set($className, $object);
        $this->assertSame($container->get($className), $object);
    }

    public function testSetDefinitionWithNull(): void
    {
        $container = new ServiceLocator();

        $this->assertNull($container->set('test', null));
    }

    public function testSetExceptionTypeDefinition(): void
    {
        $container = new ServiceLocator();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Unexpected configuration type for the "test" component: integer');

        $container->set('test', 123);
    }

    public function testShared(): void
    {
        // with configuration: shared
        $serviceLocator = new ServiceLocator();
        $class = TestClass::class;
        $serviceLocator->set(
            $class,
            [
                '__class' => $class,
                'prop1' => 10,
                'prop2' => 20,
            ],
        );

        $object = $serviceLocator->get($class);
        $this->assertEquals(10, $object->prop1);
        $this->assertEquals(20, $object->prop2);
        $this->assertInstanceOf($class, $object);

        // check shared
        $object2 = $serviceLocator->get($class);
        $this->assertInstanceOf($class, $object2);
        $this->assertSame($object, $object2);
    }
}

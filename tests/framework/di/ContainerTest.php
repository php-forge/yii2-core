<?php

declare(strict_types=1);

namespace yiiunit\framework\di;

use Yii;
use yii\base\InvalidConfigException;
use yii\di\{Container, Instance, NotFoundException, NotInstantiableException};
use yii\validators\NumberValidator;
use yiiunit\data\ar\{Cat, Order, Type};
use yiiunit\data\base\TraversableObject;
use yiiunit\framework\di\stubs\{
    Alpha,
    Bar,
    BarSetter,
    Beta,
    Corge,
    EngineCar,
    EngineMarkOne,
    EngineMarkTwo,
    Foo,
    FooProperty,
    Qux,
    QuxAnother,
    QuxFactory,
    QuxInterface,
    UnionTypeNotNull,
    UnionTypeNull,
    UnionTypeWithClass,
    Variadic,
    Zeta,
};
use yiiunit\framework\di\stubs\EngineInterface;
use yiiunit\framework\di\stubs\OptionalConcreteDependency;
use yiiunit\TestCase;

/**
 * @group di
 */
final class ContainerTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Yii::$container = new Container();
    }

    public function testAssociativeInvoke(): void
    {
        $this->mockApplication([
            'components' => [
                'qux' => [
                    'class' => Qux::class,
                    'a' => 'belongApp',
                ],
                'qux2' => [
                    'class' => Qux::class,
                    'a' => 'belongAppQux2',
                ],
            ],
        ]);

        $closure = function ($a, $b, $x = 5) {
            return $a > $b;
        };

        $this->assertFalse(Yii::$container->invoke($closure, ['b' => 5, 'a' => 1]));
        $this->assertTrue(Yii::$container->invoke($closure, ['b' => 1, 'a' => 5]));
    }

    public function testClear(): void
    {
        $container = new Container();
        $container->setSingletons(
            [
                'model.order' => Order::class,
                'test\TraversableInterface' => [
                    ['class' => '\yiiunit\data\base\TraversableObject'],
                    [['item1', 'item2']],
                ],
                'qux.using.closure' => static function () {
                    return new Qux();
                },
            ]
        );
        $container->clear();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Failed to instantiate component or class "model.order".');

        $container->get('model.order');
    }

    public function testContainerSingletons(): void
    {
        $container = new Container();
        $container->setSingletons(
            [
                'model.order' => Order::class,
                'test\TraversableInterface' => [
                    ['class' => TraversableObject::class],
                    [['item1', 'item2']],
                ],
                'qux.using.closure' => static function () {
                    return new Qux();
                },
            ]
        );
        $container->setSingletons([]);

        $order = $container->get('model.order');
        $sameOrder = $container->get('model.order');
        $this->assertSame($order, $sameOrder);

        $traversable = $container->get('test\TraversableInterface');
        $sameTraversable = $container->get('test\TraversableInterface');
        $this->assertSame($traversable, $sameTraversable);

        $foo = $container->get('qux.using.closure');
        $sameFoo = $container->get('qux.using.closure');
        $this->assertSame($foo, $sameFoo);
    }

    public function testCreate(): void
    {
        $Qux = Qux::class;

        $container = new Container();
        $container->set('qux', $Qux);

        $qux = $container->create('qux', [], ['a' => 2]);
        $this->assertSame(2, $qux->a);

        $qux = $container->create('qux', [3]);
        $this->assertSame(3, $qux->a);

        $qux = $container->create('qux', [3, ['a' => 4]]);
        $this->assertSame(4, $qux->a);
    }

    public function testCreateWithUnionTypeClassConstructorParameters(): void
    {
        $unionType = (new Container())->create(UnionTypeWithClass::class, ['value' => new Beta()]);
        $this->assertInstanceOf(UnionTypeWithClass::class, $unionType);
        $this->assertInstanceOf(Beta::class, $unionType->value);

        $this->expectException('TypeError');
        (new Container())->create(UnionTypeNotNull::class);
    }

    public function testCreateWithUnionTypeNullConstructorParameters(): void
    {
        $unionType = (new Container())->create(UnionTypeNull::class);
        $this->assertInstanceOf(UnionTypeNull::class, $unionType);
    }

    public function testCreateUnionTypeWithoutNullConstructorParameters(): void
    {
        $unionType = (new Container())->create(UnionTypeNotNull::class, ['value' => 'a']);
        $this->assertInstanceOf(UnionTypeNotNull::class, $unionType);

        $unionType = (new Container())->create(UnionTypeNotNull::class, ['value' => 1]);
        $this->assertInstanceOf(UnionTypeNotNull::class, $unionType);

        $unionType = (new Container())->create(UnionTypeNotNull::class, ['value' => 2.3]);
        $this->assertInstanceOf(UnionTypeNotNull::class, $unionType);

        $unionType = (new Container())->create(UnionTypeNotNull::class, ['value' => true]);
        $this->assertInstanceOf(UnionTypeNotNull::class, $unionType);

        $this->expectException('TypeError');
        (new Container())->create(UnionTypeNotNull::class);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/18245
     */
    public function testDelayedInitializationOfSubArray(): void
    {
        $definitions = [
            'test' => [
                'class' => Corge::class,
                '__construct()' => [
                    [Instance::of('setLater')],
                ],
            ],
        ];

        $application = Yii::createObject([
            '__class' => \yii\web\Application::class,
            'basePath' => __DIR__,
            'id' => 'test',
            'components' => [
                'request' => [
                    'baseUrl' => '123'
                ],
            ],
            'container' => [
                'definitions' => $definitions,
            ],
        ]);

        Yii::$container->set('setLater', new Qux());
        Yii::$container->get('test');

        $this->assertTrue(true);
    }

    public function testDi3Compatibility(): void
    {
        $container = new Container();
        $container->setDefinitions(
            [
                'test\TraversableInterface' => [
                    '__class' => TraversableObject::class,
                    '__construct()' => [['item1', 'item2']],
                ],
                'qux' => [
                    '__class' => Qux::class,
                    'a' => 42,
                ],
            ]
        );

        $qux = $container->get('qux');
        $this->assertInstanceOf(Qux::class, $qux);
        $this->assertSame(42, $qux->a);

        $traversable = $container->get('test\TraversableInterface');
        $this->assertInstanceOf(TraversableObject::class, $traversable);
        $this->assertEquals('item1', $traversable->current());
    }

    public function testGet(): void
    {
        $Foo = Foo::class;
        $Bar = Bar::class;
        $Qux = Qux::class;

        $container = new Container();

        // automatic wiring
        $container->set(QuxInterface::class, $Qux);
        $foo = $container->get($Foo);
        $this->assertInstanceOf($Foo, $foo);
        $this->assertInstanceOf($Bar, $foo->bar);
        $this->assertInstanceOf($Qux, $foo->bar->qux);

        $foo2 = $container->get($Foo);
        $this->assertNotSame($foo, $foo2);

        // full wiring
        $container->clear();
        $container->set(QuxInterface::class, $Qux);
        $container->set($Bar);
        $container->set($Qux);
        $container->set($Foo);
        $foo = $container->get($Foo);
        $this->assertInstanceOf($Foo, $foo);
        $this->assertInstanceOf($Bar, $foo->bar);
        $this->assertInstanceOf($Qux, $foo->bar->qux);

        // wiring by closure
        $container->clear();
        $container->set(
            'foo',
            static function (): Foo {
                $qux = new Qux();
                $bar = new Bar($qux);

                return new Foo($bar);
            }
        );
        $foo = $container->get('foo');
        $this->assertInstanceOf($Foo, $foo);
        $this->assertInstanceOf($Bar, $foo->bar);
        $this->assertInstanceOf($Qux, $foo->bar->qux);

        // wiring by closure which uses container
        $container->clear();
        $container->set(QuxInterface::class, $Qux);
        $container->set(
            'foo',
            static function (Container $c, $params, $config): Foo {
                return $c->get(Foo::class);
            }
        );
        $foo = $container->get('foo');
        $this->assertInstanceOf($Foo, $foo);
        $this->assertInstanceOf($Bar, $foo->bar);
        $this->assertInstanceOf($Qux, $foo->bar->qux);

        // predefined constructor parameters
        $container->clear();
        $container->set('foo', $Foo, [Instance::of('bar')]);
        $container->set('bar', $Bar, [Instance::of('qux')]);
        $container->set('qux', $Qux);
        $foo = $container->get('foo');
        $this->assertInstanceOf($Foo, $foo);
        $this->assertInstanceOf($Bar, $foo->bar);
        $this->assertInstanceOf($Qux, $foo->bar->qux);

        // predefined property parameters
        $fooSetter = FooProperty::class;
        $barSetter = BarSetter::class;

        $container->clear();
        $container->set('foo', ['class' => $fooSetter, 'bar' => Instance::of('bar')]);
        $container->set('bar', ['class' => $barSetter, 'qux' => Instance::of('qux')]);
        $container->set('qux', $Qux);
        $foo = $container->get('foo');
        $this->assertInstanceOf($fooSetter, $foo);
        $this->assertInstanceOf($barSetter, $foo->bar);
        $this->assertInstanceOf($Qux, $foo->bar->qux);

        // wiring by closure
        $container->clear();
        $container->set('qux', new Qux());
        $qux1 = $container->get('qux');
        $qux2 = $container->get('qux');
        $this->assertSame($qux1, $qux2);
    }

    public function testGetByClassIndirectly(): void
    {
        $container = new Container();
        $container->setSingletons(
            [
                'qux' => Qux::class,
                Qux::class => [
                    'a' => 42,
                ],
            ]
        );

        $qux = $container->get('qux');
        $this->assertInstanceOf(Qux::class, $qux);
        $this->assertSame(42, $qux->a);
    }

    public function testGetDefinitions(): void
    {
        $container = new Container();
        $container->setDefinitions(
            [
                'test' => [
                    'class' => Qux::class,
                    'a' => 42,
                ],
            ]
        );

        $this->assertSame(
            [
                'test' => [
                    'class' => Qux::class,
                    'a' => 42,
                ],
            ],
            $container->getDefinitions()
        );
    }

    public function testGetInvalidDefinitionWithArray(): void
    {
        $container = new Container();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('A class definition requires a "class" member.');

        $container->setDefinitions(
            [
                'test' => [
                    'a' => 42, // Invalid definition
                ],
            ],
        );
        $container->get('test');
    }

    public function testGetDefinitionWithCallable(): void
    {
        $container = new Container();
        $container->setDefinitions(
            [
                'test' => static function () {
                    return 42;
                },
            ],
        );
        $this->assertSame(42, $container->get('test'));
    }

    public function testGetInvalidDefinitionWithInteger(): void
    {
        $container = new Container();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('A class definition requires a "class" member.');

        $container->setDefinitions(
            [
                'test' => [
                    42 // Invalid definition
                ],
            ],
        );

        $container->get('test');
    }

    public function testGetInvalidDefinitionWithInteger1(): void
    {
        $container = new Container();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Unsupported definition type for "test": integer');

        $container->set('test', 42); // Invalid definition
        $container->get('test');
    }

    public function testGetInvalidDefinitionClassWithString(): void
    {
        $container = new Container();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Failed to instantiate component or class "42".');

        $container->setDefinitions(
            [
                'test' => [
                    'class' => '42', // Invalid definition
                ],
            ],
        );

        $container->get('test');
    }

    public function testGetInvalidDefinitionWithString(): void
    {
        $container = new Container();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('A class definition requires a "class" member.');

        $container->setDefinitions(
            [
                'test' => [
                    '42' // Invalid definition
                ],
            ],
        );

        $container->get('test');
    }

    /**
     * @dataProvider \yiiunit\framework\di\providers\ContainerProvider::dataHas
     */
    public function testHas(bool $expected, $id): void
    {
        $container = new Container();
        $container->setDefinitions(
            [
                EngineCar::class => [
                    'engine' => EngineMarkOne::class,
                ],
                EngineMarkOne::class => EngineMarkOne::class,
                EngineInterface::class => EngineMarkOne::class,
            ]
        );

        $this->assertSame($expected, $container->has($id));
    }

    public function testHasSingleton(): void
    {
        $container = new Container();
        $container->setSingletons(['qux' => Qux::class]);

        $this->assertTrue($container->hasSingleton('qux'));
    }

    public function testHasSingletonByClass(): void
    {
        $container = new Container();
        $container->setSingletons(['qux' => Qux::class, Qux::class => Qux::class]);

        $this->assertTrue($container->hasSingleton('qux'));
        $this->assertTrue($container->hasSingleton(Qux::class));
    }

    public function testHasSingletonByInstance(): void
    {
        $container = new Container();
        $container->setSingletons(['qux' => Qux::class, 'qux2' => Instance::of('qux')]);

        $this->assertTrue($container->hasSingleton('qux'));
        $this->assertTrue($container->hasSingleton('qux2'));
    }

    public function testHasSingletonByInstanceWithClass(): void
    {
        $container = new Container();
        $container->setSingletons(['qux' => Qux::class, 'qux2' => Instance::of(Qux::class)]);

        $this->assertTrue($container->hasSingleton('qux'));
        $this->assertTrue($container->hasSingleton('qux2'));
    }

    public function testHasSingletonWithCheckInstance(): void
    {
        $container = new Container();
        $container->setSingletons(['qux' => Qux::class, 'qux2' => Instance::of(Qux::class)]);

        $this->assertInstanceOf(Qux::class, $container->get('qux'));
        $this->assertTrue($container->hasSingleton('qux', true));
    }

    public function testIntegerKeys(): void
    {
        $this->expectException(InvalidConfigException::class);

        $container = new Container();
        $container->setDefinitions(
            [
                EngineMarkOne::class,
                EngineMarkTwo::class,
            ]
        );

        $container->get(EngineCar::class);
    }

    public function testInstanceOf(): void
    {
        $container = new Container();
        $container->setDefinitions(
            [
                'qux' => [
                    'class' => Qux::class,
                    'a' => 42,
                ],
                'bar' => [
                    '__class' => Bar::class,
                    '__construct()' => [
                        Instance::of('qux')
                    ],
                ],
            ]
        );
        $bar = $container->get('bar');
        $this->assertInstanceOf(Bar::class, $bar);
        $qux = $bar->qux;
        $this->assertInstanceOf(Qux::class, $qux);
        $this->assertSame(42, $qux->a);
    }

    public function testInvoke(): void
    {
        $this->mockApplication([
            'components' => [
                'qux' => [
                    'class' => 'yiiunit\framework\di\stubs\Qux',
                    'a' => 'belongApp',
                ],
                'qux2' => [
                    'class' => 'yiiunit\framework\di\stubs\Qux',
                    'a' => 'belongAppQux2',
                ],
            ],
        ]);
        Yii::$container->set('yiiunit\framework\di\stubs\QuxInterface', [
            'class' => 'yiiunit\framework\di\stubs\Qux',
            'a' => 'independent',
        ]);

        // use component of application
        $callback = static function ($param, stubs\QuxInterface $qux, Bar $bar) {
            return [$param, $qux instanceof Qux, $qux->a, $bar->qux->a];
        };
        $result = Yii::$container->invoke($callback, ['D426']);
        $this->assertEquals(['D426', true, 'belongApp', 'independent'], $result);

        // another component of application
        $callback = static function ($param, stubs\QuxInterface $qux2, $other = 'default') {
            return [$param, $qux2 instanceof Qux, $qux2->a, $other];
        };
        $result = Yii::$container->invoke($callback, ['M2792684']);
        $this->assertEquals(['M2792684', true, 'belongAppQux2', 'default'], $result);

        // component not belong application
        $callback = static function ($param, stubs\QuxInterface $notBelongApp, $other) {
            return [$param, $notBelongApp instanceof Qux, $notBelongApp->a, $other];
        };
        $result = Yii::$container->invoke($callback, ['MDM', 'not_default']);
        $this->assertEquals(['MDM', true, 'independent', 'not_default'], $result);

        $myFunc = static function ($a, NumberValidator $b, $c = 'default') {
            return [$a, \get_class($b), $c];
        };
        $result = Yii::$container->invoke($myFunc, ['a']);
        $this->assertEquals(['a', 'yii\validators\NumberValidator', 'default'], $result);

        $result = Yii::$container->invoke($myFunc, ['ok', 'value_of_c']);
        $this->assertEquals(['ok', 'yii\validators\NumberValidator', 'value_of_c'], $result);

        // use native php function
        $this->assertEquals(Yii::$container->invoke('trim', [' M2792684  ']), 'M2792684');

        // use helper function
        $array = ['M36', 'D426', 'Y2684'];
        $this->assertFalse(Yii::$container->invoke(['yii\helpers\ArrayHelper', 'isAssociative'], [$array]));

        $myFunc = static function (\yii\console\Request $request, \yii\console\Response $response) {
            return [$request, $response];
        };
        [$request, $response] = Yii::$container->invoke($myFunc);
        $this->assertEquals($request, Yii::$app->request);
        $this->assertEquals($response, Yii::$app->response);
    }

    /**
     * @dataProvider \yiiunit\framework\di\providers\ContainerProvider::dataNotInstantiableException
     *
     * @see https://github.com/yiisoft/yii2/pull/18379
     *
     * @param string $class
     */
    public function testNotFoundException(string $class): void
    {
        $this->expectException(NotFoundException::class);

        (new Container())->get($class);
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/18304
     */
    public function testNulledConstructorParameters(): void
    {
        $alpha = (new Container())->get(Alpha::class);
        $this->assertInstanceOf(Beta::class, $alpha->beta);
        $this->assertNull($alpha->omega);

        $QuxInterface = __NAMESPACE__ . '\stubs\QuxInterface';
        $container = new Container();
        $container->set($QuxInterface, Qux::class);
        $alpha = $container->get(Alpha::class);
        $this->assertInstanceOf(Beta::class, $alpha->beta);
        $this->assertInstanceOf($QuxInterface, $alpha->omega);
        $this->assertNull($alpha->unknown);
        $this->assertNull($alpha->color);

        $container = new Container();
        $container->set(__NAMESPACE__ . '\stubs\AbstractColor', __NAMESPACE__ . '\stubs\Color');
        $alpha = $container->get(Alpha::class);
        $this->assertInstanceOf(__NAMESPACE__ . '\stubs\Color', $alpha->color);
    }

    public function testNullTypeConstructorParameters(): void
    {
        $zeta = (new Container())->get(Zeta::class);
        $this->assertInstanceOf(Beta::class, $zeta->beta);
        $this->assertInstanceOf(Beta::class, $zeta->betaNull);
        $this->assertNull($zeta->color);
        $this->assertNull($zeta->colorNull);
        $this->assertNull($zeta->qux);
        $this->assertNull($zeta->quxNull);
        $this->assertNull($zeta->unknown);
        $this->assertNull($zeta->unknownNull);
    }

    public function testObject(): void
    {
        $container = new Container();
        $container->setDefinitions(['qux' => new Qux(42),]);
        $qux = $container->get('qux');
        $this->assertInstanceOf(Qux::class, $qux);
        $this->assertSame(42, $qux->a);
    }

    public function testOptionalDependencies(): void
    {
        $container = new Container();

        // Test optional unresolvable dependency.
        $closure = static function (QuxInterface|null $test = null) {
            return $test;
        };
        $this->assertNull($container->invoke($closure));
    }

    public function testOptionalResolvableClassDependency(): void
    {
        $container = new Container();
        $container->setDefinitions([EngineInterface::class => EngineMarkOne::class]);

        $service = $container->get(OptionalConcreteDependency::class);

        $this->assertInstanceOf(EngineCar::class, $service->getCar());
    }

    public function testOptionalNotResolvableClassDependency(): void
    {
        $container = new Container();

        $service = $container->get(OptionalConcreteDependency::class);
        $this->assertNull($service->getCar());
    }

    public function testReferencesInArrayInDependencies(): void
    {
        $quxInterface = 'yiiunit\framework\di\stubs\QuxInterface';
        $container = new Container();
        $container->setResolveArrays(true);
        $container->setSingletons(
            [
                $quxInterface => [
                    'class' => Qux::class,
                    'a' => 42,
                ],
                'qux' => Instance::of($quxInterface),
                'bar' => [
                    'class' => Bar::class,
                ],
                'corge' => [
                    '__class' => Corge::class,
                    '__construct()' => [
                        [
                            'qux' => Instance::of('qux'),
                            'bar' => Instance::of('bar'),
                            'q33' => new Qux(33),
                        ],
                    ],
                ],
            ]
        );

        $corge = $container->get('corge');
        $this->assertInstanceOf(Corge::class, $corge);

        $qux = $corge->map['qux'];
        $this->assertInstanceOf(Qux::class, $qux);
        $this->assertSame(42, $qux->a);

        $bar = $corge->map['bar'];
        $this->assertInstanceOf(Bar::class, $bar);
        $this->assertSame($qux, $bar->qux);

        $q33 = $corge->map['q33'];
        $this->assertInstanceOf(Qux::class, $q33);
        $this->assertSame(33, $q33->a);
    }

    public function testResolveCallableDependencies(): void
    {
        $this->mockApplication([
            'components' => [
                'qux' => [
                    'class' => 'yiiunit\framework\di\stubs\Qux',
                    'a' => 'belongApp',
                ],
                'qux2' => [
                    'class' => 'yiiunit\framework\di\stubs\Qux',
                    'a' => 'belongAppQux2',
                ],
            ],
        ]);

        $closure = function ($a, $b) {
            return $a > $b;
        };

        $this->assertEquals([1, 5], Yii::$container->resolveCallableDependencies($closure, ['b' => 5, 'a' => 1]));
        $this->assertEquals([1, 5], Yii::$container->resolveCallableDependencies($closure, ['a' => 1, 'b' => 5]));
        $this->assertEquals([1, 5], Yii::$container->resolveCallableDependencies($closure, [1, 5]));
    }

    public function testResolveCallableDependenciesIntersectionTypes(): void
    {
        Yii::$container->set('yiiunit\framework\di\stubs\QuxInterface', ['class' => Qux::class]);

        $className = 'yiiunit\framework\di\stubs\StaticMethodsWithIntersectionTypes';
        $params = Yii::$container->resolveCallableDependencies(
            [
                $className,
                'withQuxInterfaceAndQuxAnotherIntersection',
            ],
        );
        $this->assertInstanceOf(Qux::class, $params[0]);

        $params = Yii::$container->resolveCallableDependencies(
            [
                $className,
                'withQuxAnotherAndQuxInterfaceIntersection',
            ],
        );
        $this->assertInstanceOf(QuxAnother::class, $params[0]);
    }

    public function testResolveCallableDependenciesUnionTypes(): void
    {
        $this->mockApplication(
            [
                'components' => [
                    Beta::class,
                ],
            ]
        );

        Yii::$container->set('yiiunit\framework\di\stubs\QuxInterface', ['class' => Qux::class]);

        $className = 'yiiunit\framework\di\stubs\StaticMethodsWithUnionTypes';

        $params = Yii::$container->resolveCallableDependencies([$className, 'withBetaUnion']);
        $this->assertInstanceOf(Beta::class, $params[0]);

        $params = Yii::$container->resolveCallableDependencies([$className, 'withBetaUnionInverse']);
        $this->assertInstanceOf(Beta::class, $params[0]);

        $params = Yii::$container->resolveCallableDependencies([$className, 'withBetaAndQuxUnion']);
        $this->assertInstanceOf(Beta::class, $params[0]);

        $params = Yii::$container->resolveCallableDependencies([$className, 'withQuxAndBetaUnion']);
        $this->assertInstanceOf(Qux::class, $params[0]);
    }

    public function testResolveCallableDependenciesWithInvokeableClass(): void
    {
        $closure = new stubs\InvokeableClass();

        $resolvedDependencies = Yii::$container->resolveCallableDependencies($closure);
        $result = $closure(...$resolvedDependencies);

        $this->assertSame('invoked', $result);
    }

    public function testStaticCall(): void
    {
        $container = new Container();
        $container->setDefinitions(['qux' => [QuxFactory::class, 'create']]);
        $qux = $container->get('qux');
        $this->assertInstanceOf(Qux::class, $qux);
        $this->assertSame(42, $qux->a);
    }

    public function testSetDependencies(): void
    {
        $container = new Container();
        $container->setDefinitions(
            [
                'model.order' => Order::class,
                Cat::class => Type::class,
                'test\TraversableInterface' => [
                    ['class' => 'yiiunit\data\base\TraversableObject'],
                    [['item1', 'item2']],
                ],
                'qux.using.closure' => function () {
                    return new Qux();
                },
                'rollbar',
                'baibaratsky\yii\rollbar\Rollbar',
            ]
        );

        $container->setDefinitions([]);
        $this->assertInstanceOf(Order::class, $container->get('model.order'));
        $this->assertInstanceOf(Type::class, $container->get(Cat::class));

        $traversable = $container->get('test\TraversableInterface');
        $this->assertInstanceOf('yiiunit\data\base\TraversableObject', $traversable);
        $this->assertEquals('item1', $traversable->current());
        $this->assertInstanceOf('yiiunit\framework\di\stubs\Qux', $container->get('qux.using.closure'));

        try {
            $container->get('rollbar');
            $this->fail('InvalidConfigException was not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(NotFoundException::class, $e);
        }
    }

    public function testSettingScalars(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Unsupported definition type for "scalar": integer');

        $container = new Container();
        $container = $container->set('scalar', 42);

        $container->get('scalar');
    }

    public function testVariadicCallable(): void
    {
        $container = new Container();
        $container->setSingletons(
            [
                'qux' => Qux::class,
                'qux2' => Qux::class,
            ]
        );

        $func = static function (QuxInterface ...$quxes) {
            return 'That\'s a whole lot of quxes!';
        };
        $result = $container->invoke($func);
        $this->assertEquals('That\'s a whole lot of quxes!', $result);
    }

    public function testVariadicConstructor(): void
    {
        $container = new Container();
        $container->get(Variadic::class);

        $this->assertTrue(true);
    }

    public function testWithoutDefinition(): void
    {
        $container = new Container();

        $one = $container->get(Qux::class);
        $this->assertInstanceOf(Qux::class, $one);

        $two = $container->get(Qux::class);
        $this->assertInstanceOf(Qux::class, $two);

        $this->assertSame(1, $one->a);
        $this->assertSame(1, $two->a);
        $this->assertNotSame($one, $two);
    }
}

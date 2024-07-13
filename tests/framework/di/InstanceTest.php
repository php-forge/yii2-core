<?php

declare(strict_types=1);

namespace yiiunit\framework\di;

use Yii;
use yii\base\{Component, InvalidConfigException};
use yii\db\Connection;
use yii\di\{Container, Instance, NotFoundException};
use yiiunit\TestCase;

/**
 * @group di
 */
class InstanceTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Yii::$container = new Container();
    }

    public function testEnsure(): void
    {
        $container = new Container();
        $container->set(
            'db',
            [
                'class' => 'yii\db\Connection',
                'dsn' => 'test',
            ],
        );

        $this->assertInstanceOf(
            Connection::class,
            Instance::ensure('db', Connection::class, $container),
        );
        $this->assertInstanceOf(
            Connection::class,
            Instance::ensure(new Connection(), Connection::class, $container)
        );
        $this->assertInstanceOf(
            Connection::class,
            Instance::ensure(['class' => Connection::class, 'dsn' => 'test'], Connection::class, $container),
        );
    }

    public function testEnsureException(): void
    {
        $container = new Container();

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Failed to instantiate component or class "db".');

        Instance::ensure(Instance::of('db'), 'yii\base\Widget', $container);
    }


    public function testEnsureMinimalSettings(): void
    {
        Yii::$container->set(
            'db', [
                'class' => 'yii\db\Connection',
                'dsn' => 'test',
            ],
        );

        $this->assertInstanceOf(Connection::class, Instance::ensure('db'));
        $this->assertInstanceOf(Connection::class, Instance::ensure(new Connection()));
        $this->assertInstanceOf(Connection::class, Instance::ensure(['class' => Connection::class, 'dsn' => 'test']));
    }

    /**
     * ensure an InvalidConfigException is thrown when a component does not exist.
     */
    public function testEnsureWithNonExistingComponentException(): void
    {
        $container = new Container();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Failed to instantiate component or class "cache".');

        Instance::ensure('cache', 'yii\cache\Cache', $container);
    }

    /**
     * ensure an InvalidConfigException is thrown when a class does not exist.
     */
    public function testEnsureWithNonExistingClassException(): void
    {
        $container = new Container();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Failed to instantiate component or class "yii\cache\DoesNotExist".');

        Instance::ensure('yii\cache\DoesNotExist', 'yii\cache\Cache', $container);
    }

    public function testEnsureWithoutType(): void
    {
        $container = new Container();
        $container->set(
            'db', [
                'class' => 'yii\db\Connection',
                'dsn' => 'test',
            ],
        );

        $this->assertInstanceOf(Connection::class, Instance::ensure('db', null, $container));
        $this->assertInstanceOf(Connection::class, Instance::ensure(new Connection(), null, $container));
        $this->assertInstanceOf(
            Connection::class,
            Instance::ensure(['class' => Connection::class, 'dsn' => 'test'], null, $container),
        );
    }

    public function testOf(): void
    {
        $container = new Container();
        $className = Component::class;
        $instance = Instance::of($className);

        $this->assertInstanceOf(Instance::class, $instance);
        $this->assertInstanceOf(Component::class, $instance->get($container));
        $this->assertInstanceOf(Component::class, Instance::ensure($instance, $className, $container));
        $this->assertNotSame($instance->get($container), Instance::ensure($instance, $className, $container));
    }

    public function testExceptionComponentIsNotSpecified():  void
    {
        $this->expectException('yii\base\InvalidConfigException');
        $this->expectExceptionMessage('The required component is not specified.');
        Instance::ensure('');
    }

    public function testExceptionRefersTo(): void
    {
        $container = new Container();
        $container->set(
            'db',
            [
                'class' => 'yii\db\Connection',
                'dsn' => 'test',
            ],
        );

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('"db" refers to a yii\db\Connection component. yii\base\Widget is expected.');

        Instance::ensure('db', 'yii\base\Widget', $container);
    }

    public function testExceptionInvalidDataType(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Invalid data type: yii\db\Connection. yii\base\Widget is expected.');

        Instance::ensure(new Connection(), 'yii\base\Widget');
    }

    public function testExceptionInvalidDataTypeInArray(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Invalid data type: yii\db\Connection. yii\base\Widget is expected.');

        Instance::ensure(['class' => Connection::class], 'yii\base\Widget');
    }

    public function testExceptionWithEmptyId(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('The required component ID is empty.');

        Instance::of('', true);
    }

    public function testGet(): void
    {
        $this->mockApplication(
            [
                'components' => [
                    'db' => [
                        'class' => 'yii\db\Connection',
                        'dsn' => 'test',
                    ],
                ],
            ],
        );

        $container = Instance::of('db');

        $this->assertInstanceOf(Connection::class, $container->get());
        $this->destroyApplication();
    }

    /**
     * This tests the usage example given in yii\di\Instance class PHPDoc.
     */
    public function testLazyInitializationExample(): void
    {
        Yii::$container = new Container();
        Yii::$container->set(
            'cache',
            [
                'class' => 'yii\caching\DbCache',
                'db' => Instance::of('db'),
            ],
        );
        Yii::$container->set(
            'db',
            [
                'class' => 'yii\db\Connection',
                'dsn' => 'sqlite:path/to/file.db',
            ],
        );

        $this->assertInstanceOf('yii\caching\DbCache', $cache = Yii::$container->get('cache'));
        $this->assertInstanceOf('yii\db\Connection', $db = $cache->db);
        $this->assertEquals('sqlite:path/to/file.db', $db->dsn);
    }

    public function testRestoreAfterVarExport(): void
    {
        $instance = Instance::of('something');
        $export = var_export($instance, true);

        $this->assertMatchesRegularExpression(
            '~yii\\\\di\\\\Instance::__set_state\(array\(\s+\'id\' => \'something\',\s+\'optional\' => false,\s+\)\)~',
            $export,
        );

        $this->assertEquals(
            $instance,
            Instance::__set_state(['id' => 'something']),
        );
    }

    public function testRestoreAfterVarExportRequiresId(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Failed to instantiate class "Instance". Required parameter "id" is missing');

        Instance::__set_state([]);
    }
}

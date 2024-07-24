<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session;

use Yii;
use yii\db\Connection;
use yii\db\Migration;
use yii\db\Query;
use yii\web\DbSession;
use yiiunit\framework\console\controllers\EchoMigrateController;
use yiiunit\TestCase;

/**
 * @group db
 */
abstract class AbstractDbSessionTest extends TestCase
{
    use SessionTestTrait;

    /**
     * @return string[] the driver names that are suitable for the test (mysql, pgsql, etc)
     */
    abstract protected function getDriverNames();

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockApplication();

        Yii::$app->set('db', $this->getDbConfig());

        $this->dropTableSession();
        $this->createTableSession();
    }

    protected function tearDown(): void
    {
        $this->dropTableSession();
        parent::tearDown();
    }

    protected function getDbConfig(): array
    {
        $driverNames = $this->getDriverNames();
        $databases = self::getParam('databases');

        foreach ($driverNames as $driverName) {
            if (in_array($driverName, \PDO::getAvailableDrivers()) && array_key_exists($driverName, $databases)) {
                $driverAvailable = $driverName;
                break;
            }
        }

        if (!isset($driverAvailable)) {
            $this->markTestIncomplete(
                get_called_class() . ' requires ' . implode(' or ', $driverNames) .
                ' PDO driver! Configuration for connection required too.'
            );

            return [];
        }

        $config = $databases[$driverAvailable];

        $result = [
            'class' => Connection::class,
            'dsn' => $config['dsn'],
        ];

        if (isset($config['username'])) {
            $result['username'] = $config['username'];
        }

        if (isset($config['password'])) {
            $result['password'] = $config['password'];
        }

        return $result;
    }

    protected function createTableSession(): void
    {
        $this->runMigrate('up');
    }

    protected function dropTableSession(): void
    {
        try {
            $this->runMigrate('down', ['all']);
        } catch (\Exception $e) {
            // Table may not exist for different reasons, but since this method
            // reverts DB changes to make next test pass, this exception is skipped.
        }
    }

    // Tests :

    public function testReadWrite(): void
    {
        $session = new DbSession();

        $session->write('test', 'session data');
        $this->assertEquals('session data', $session->readSession('test'));

        $session->destroy('test');
        $this->assertEquals('', $session->readSession('test'));
    }

    public function testInitializeWithConfig(): void
    {
        // should produce no exceptions
        $session = new DbSession(['useCookies' => true]);

        $session->write('test', 'session data');
        $this->assertEquals('session data', $session->readSession('test'));

        $session->destroy('test');
        $this->assertEquals('', $session->readSession('test'));
    }

    /**
     * @depends testReadWrite
     */
    public function testGarbageCollection(): void
    {
        $session = new DbSession();

        $session->write('new', 'new data');
        $session->write('expire', 'expire data');

        $session->db->createCommand()
            ->update('session', ['expire' => time() - 100], 'id = :id', ['id' => 'expire'])
            ->execute();
        $session->gcSession(1);

        $this->assertEquals('', $session->read('expire'));
        $this->assertEquals('new data', $session->read('new'));
    }

    /**
     * @depends testReadWrite
     */
    public function testWriteCustomField(): void
    {
        $session = new DbSession();

        $session->writeCallback = function ($session) {
            return ['data' => 'changed by callback data'];
        };

        $session->write('test', 'session data');

        $query = new Query();
        $this->assertSame('changed by callback data', $session->read('test'));
    }

    /**
     * @depends testReadWrite
     */
    public function testWriteCustomFieldWithUserId(): void
    {
        $session = new DbSession();

        $session->open();
        $session->set('user_id', 12345);

        // add mapped custom column
        $migration = new Migration;
        $migration->compact = true;
        $migration->addColumn($session->sessionTable, 'user_id', $migration->integer());

        $session->writeCallback = function ($session) {
            return ['user_id' => $session['user_id']];
        };

        // here used to be error, fixed issue #9438
        $session->close();

        // reopen & read session from DB
        $session->open();
        $loadedUserId = empty($session['user_id']) ? null : $session['user_id'];
        $this->assertSame($loadedUserId, 12345);

        $session->close();
    }

    protected function buildObjectForSerialization(): object
    {
        $object = new \stdClass();
        $object->nullValue = null;
        $object->floatValue = pi();
        $object->textValue = str_repeat('QweåßƒТест', 200);
        $object->array = [null, 'ab' => 'cd'];
        $object->binary = base64_decode('5qS2UUcXWH7rjAmvhqGJTDNkYWFiOGMzNTFlMzNmMWIyMDhmOWIwYzAwYTVmOTFhM2E5MDg5YjViYzViN2RlOGZlNjllYWMxMDA0YmQxM2RQ3ZC0in5ahjNcehNB/oP/NtOWB0u3Skm67HWGwGt9MA==');
        $object->with_null_byte = 'hey!' . "\0" . 'y"ûƒ^äjw¾bðúl5êù-Ö=W¿Š±¬GP¥Œy÷&ø';

        return $object;
    }

    public function testSerializedObjectSaving(): void
    {
        $session = new DbSession();

        $object = $this->buildObjectForSerialization();
        $serializedObject = serialize($object);
        $session->write('test', $serializedObject);
        $this->assertSame($serializedObject, $session->read('test'));

        $object->foo = 'modification checked';
        $serializedObject = serialize($object);
        $session->write('test', $serializedObject);
        $this->assertSame($serializedObject, $session->read('test'));
    }

    protected function runMigrate(string $action, array $params = []): array
    {
        $migrate = new EchoMigrateController(
            'migrate',
            Yii::$app,
            [
                'migrationPath' => '@yii/web/migrations',
                'interactive' => false,
            ]
        );

        ob_start();
        ob_implicit_flush(false);

        $migrate->run($action, $params);

        ob_get_clean();

        return array_map(
            static function (string $version): string {
                return substr($version, 15);
            },
            (new Query())->select(['version'])->from('migration')->column(),
        );
    }

    public function testMigration(): void
    {
        $this->dropTableSession();

        $this->mockWebApplication(
            [
                'components' => [
                    'db' => $this->getDbConfig(),
                ],
            ],
        );

        $history = $this->runMigrate('history');
        $this->assertEquals(['base'], $history);

        $history = $this->runMigrate('up');
        $this->assertEquals(['base', 'session_init'], $history);

        $history = $this->runMigrate('down');
        $this->assertEquals(['base'], $history);

        $this->createTableSession();
    }

    public function testInstantiate(): void
    {
        $oldTimeout = ini_get('session.gc_maxlifetime');
        // unset Yii::$app->db to make sure that all queries are made against sessionDb
        Yii::$app->set('sessionDb', Yii::$app->db);
        Yii::$app->set('db', null);

        $session = new DbSession(['timeout' => 300, 'db' => 'sessionDb']);

        $this->assertSame(Yii::$app->sessionDb, $session->db);
        $this->assertSame(300, $session->timeout);

        $session->close();

        Yii::$app->set('db', Yii::$app->sessionDb);
        Yii::$app->set('sessionDb', null);

        ini_set('session.gc_maxlifetime', $oldTimeout);
    }

    public function testInitUseStrictMode(): void
    {
        $this->initStrictModeTest(DbSession::class);
    }

    public function testUseStrictMode(): void
    {
        $this->useStrictModeTest(DbSession::class);
    }
}

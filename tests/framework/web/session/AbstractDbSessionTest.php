<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session;

use Yii;
use yii\db\Connection;
use yii\db\Migration;
use yii\db\Query;
use yii\web\session\DbSession;
use yiiunit\framework\console\controllers\EchoMigrateController;
use yiiunit\TestCase;

/**
 * @runTestsInSeparateProcesses
 *
 * @group db
 */
abstract class AbstractDbSessionTest extends TestCase
{
    use FlashTestTrait;
    use SessionTestTrait;

    protected Connection $db;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropTableSession();
        $this->createTableSession();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->dropTableSession();
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

        $session->set('test', 'session data');
        $this->assertEquals('session data', $session->get('test'));

        $session->destroy('test');
        $this->assertEquals('', $session->get('test'));

        $session->close();
    }

    public function testInitializeWithConfig(): void
    {
        // should produce no exceptions
        $session = new DbSession(['useCookies' => true]);

        $session->set('test', 'session data');
        $this->assertEquals('session data', $session->get('test'));

        $session->destroy('test');
        $this->assertEquals('', $session->get('test'));

        $session->close();
    }

    public function testGarbageCollection(): void
    {
        $session = new DbSession();

        $expiredSessionId = 'expired_session_id';
        $session->setId($expiredSessionId);
        $session->set('expire', 'expire data');
        $session->close();

        $session->db->createCommand()
            ->update($session->sessionTable, ['expire' => time() - 100], ['id' => $expiredSessionId])
            ->execute();

        $validSessionId = 'valid_session_id';
        $session->setId($validSessionId);
        $session->set('new', 'new data');
        $session->setGCProbability(100);
        $session->close();

        $expiredData = $session->db->createCommand("SELECT * FROM {$session->sessionTable} WHERE id = :id")
            ->bindValue(':id', $expiredSessionId)
            ->queryOne();
        $this->assertFalse($expiredData);

        $validData = $session->db->createCommand("SELECT * FROM {$session->sessionTable} WHERE id = :id")
            ->bindValue(':id', $validSessionId)
            ->queryOne();
        $this->assertNotNull($validData);

        if (is_resource($validData['data'])) {
            $validData['data'] = stream_get_contents($validData['data']);
        }

        $this->assertSame('new|s:8:"new data";', $validData['data']);

        $session->close();
    }

    public function testSerializedObjectSaving(): void
    {
        $session = new DbSession();

        $object = $this->buildObjectForSerialization();
        $serializedObject = serialize($object);
        $session->set('test', $serializedObject);
        $this->assertSame($serializedObject, $session->get('test'));

        $object->foo = 'modification checked';
        $serializedObject = serialize($object);
        $session->set('test', $serializedObject);
        $this->assertSame($serializedObject, $session->get('test'));

        $session->close();
    }

    public function testMigration(): void
    {
        $this->dropTableSession();

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

        $session = new DbSession(
            [
                'timeout' => 300,
                'db' => 'sessionDb',
            ],
        );

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

    public function testAddFlash(): void
    {
        $this->add(DbSession::class);
    }

    public function testAddWithRemoveFlash(): void
    {
        $this->addWithRemove(DbSession::class);
    }

    public function testGetFlash(): void
    {
        $this->get(DbSession::class);
    }

    public function testGellAllFlash(): void
    {
        $this->getAll(DbSession::class);
    }

    public function testGetWithRemoveFlash(): void
    {
        $this->getWithRemove(DbSession::class);
    }

    public function testHasFlash(): void
    {
        $this->has(DbSession::class);
    }

    public function testRemoveFlash(): void
    {
        $this->remove(DbSession::class);
    }

    public function testRemoveAllFlash(): void
    {
        $this->removeAll(DbSession::class);
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

    protected function runMigrate($action, $params = []): array
    {
        $migrate = new EchoMigrateController(
            'migrate',
            Yii::$app,
            [
                'migrationPath' => '@yii/web/migrations',
                'interactive' => false,
            ],
        );

        ob_start();
        ob_implicit_flush(false);
        $migrate->run($action, $params);
        ob_get_clean();

        return array_map(
            static function ($version): string {
                return substr($version, 15);
            },
            (new Query())->select(['version'])->from('migration')->column(),
        );
    }
}

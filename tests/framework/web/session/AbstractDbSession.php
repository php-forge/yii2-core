<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session;

use phpmock\phpunit\PHPMock;
use Yii;
use yii\db\Connection;
use yii\db\Query;
use yii\web\session\DbSession;
use yii\web\session\handler\DbSessionHandler;
use yiiunit\framework\console\controllers\EchoMigrateController;

/**
 * @runTestsInSeparateProcesses
 */
abstract class AbstractDbSession extends AbstractSession
{
    use PHPMock;

    protected Connection $db;

    protected function setUp(): void
    {
        parent::setUp();

        Yii::$app->set('session', ['class' => DbSession::class]);

        $this->session = Yii::$app->getSession();

        $this->dropTableSession();
        $this->createTableSession();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->dropTableSession();
    }

    public function testGarbageCollection(): void
    {
        $this->session->destroy();

        $expiredSessionId = 'expired_session_id';

        $this->session->setId($expiredSessionId);
        $this->session->set('expire', 'expire data');
        $this->session->close();

        $this->session->db->createCommand()
            ->update($this->session->sessionTable, ['expire' => time() - 100], ['id' => $expiredSessionId])
            ->execute();

        $validSessionId = 'valid_session_id';

        $this->session->setId($validSessionId);
        $this->session->set('new', 'new data');
        $this->session->setGCProbability(100);
        $this->session->close();

        $expiredData = $this->session->db->createCommand("SELECT * FROM {$this->session->sessionTable} WHERE id = :id")
            ->bindValue(':id', $expiredSessionId)
            ->queryOne();

        $this->assertFalse($expiredData);

        $validData = $this->session->db->createCommand("SELECT * FROM {$this->session->sessionTable} WHERE id = :id")
            ->bindValue(':id', $validSessionId)
            ->queryOne();

        $this->assertNotNull($validData);

        if (is_resource($validData['data'])) {
            $validData['data'] = stream_get_contents($validData['data']);
        }

        $this->assertSame('new|s:8:"new data";', $validData['data']);

        $this->session->setGCProbability(1);
        $this->session->destroy();
    }

    public function testInitializeWithConfig(): void
    {
        // should produce no exceptions
        $session = new DbSession(['useCookies' => true]);

        $session->set('test', 'session data');
        $this->assertEquals('session data', $session->get('test'));

        $session->destroy('test');
        $this->assertEquals('', $session->get('test'));
    }

    public function testInstantiate(): void
    {
        $oldTimeout = ini_get('session.gc_maxlifetime');

        Yii::$app->set('sessionDb', Yii::$app->db);
        Yii::$app->set('db', null);

        $session = new DbSession(
            [
                'timeout' => 300,
                'db' => 'sessionDb',
                '_handler' => [
                    'class' => DbSessionHandler::class,
                    '__construct()' => ['sessionDb'],
                ],
            ],
        );

        $this->assertSame(Yii::$app->sessionDb, $session->db);
        $this->assertSame(300, $session->timeout);
        $session->close();

        Yii::$app->set('db', Yii::$app->sessionDb);
        Yii::$app->set('sessionDb', null);

        ini_set('session.gc_maxlifetime', $oldTimeout);
    }

    public function testMigration(): void
    {
        $this->dropTableSession();

        $history = $this->runMigrate('history');

        $this->assertSame(['base'], $history);

        $history = $this->runMigrate('up');

        $this->assertSame(['base', 'session_init'], $history);

        $history = $this->runMigrate('down');

        $this->assertSame(['base'], $history);
        $this->createTableSession();
    }

    public function testRegenerateIDWithNoActiveSession(): void
    {
        if ($this->session->getIsActive()) {
            $this->session->close();
        }

        $this->session->setId('');
        $this->session->regenerateID();

        $this->assertFalse($this->session->getIsActive(), 'No debería haberse iniciado una sesión');

        $count = (new Query())->from('session')->count('*', $this->session->db);

        $this->assertEquals(0, $count);

        $this->session->destroy();
    }

    public function testRegenerateIDWithDeleteSession(): void
    {
        $this->session->setId('old_session_id');
        $this->session->set('data', 'data');
        $this->session->regenerateID(true);

        $count = (new Query())->from('session')->count('*', $this->session->db);

        $this->assertEquals(1, $count);

        $data = (new Query())->from('session')->where(['id' => session_id()])->one($this->session->db);

        $this->assertNotNull($data);
        $this->assertNotSame('old_session_id', $data['id']);

        if (is_resource($data['data'])) {
            $data['data'] = stream_get_contents($data['data']);
        }

        $this->assertSame('data|s:4:"data";', $data['data']);

        $this->session->destroy();
    }

    public function testSerializedObjectSaving(): void
    {
        $object = $this->buildObjectForSerialization();
        $serializedObject = serialize($object);
        $this->session->set('test', $serializedObject);

        $this->assertSame($serializedObject, $this->session->get('test'));

        $object->foo = 'modification checked';
        $serializedObject = serialize($object);

        $this->session->set('test', $serializedObject);

        $this->assertSame($serializedObject, $this->session->get('test'));

        $this->session->close();
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

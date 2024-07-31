<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\sqlite;

use Yii;
use yii\db\Connection;
use yii\db\Query;
use yiiunit\framework\console\controllers\EchoMigrateController;
use yiiunit\framework\web\session\DbSessionStub;
use yiiunit\support\SqliteConnection;
use yiiunit\TestCase;

/**
 * Class RegenerateFailureTest.
 *
 * @group db
 * @group sqlite
 * @group session-db-sqlite
 */
class RegenerateFailureTest extends TestCase
{
    private Connection $db;

    protected function setUp(): void
    {
        DbSessionStub::$counter = 0;

        $this->mockWebApplication();

        $this->db = SqliteConnection::getConnection();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore the original session_id function
        \uopz_unset_return('session_id');

    }

    public function testRegenerateIDWithFailure(): void
    {
        if (!\extension_loaded('uopz')) {
            $this->markTestSkipped('uopz extension is required.');
        }

        // Mocking the session_id function
        \uopz_set_return(
            'session_id',
            function(string $id = null) {
                if (DbSessionStub::$counter === 0) {
                    DbSessionStub::$counter++;
                    return 'test-id';
                }

                return ''; // Return empty string as per your test case
            },
            true
        );

        $this->dropTableSession();
        $this->createTableSession();

        Yii::getLogger()->flush();

        $session = new DbSessionStub(['db' => $this->db]);

        $session->regenerateID();
        $session->destroy();

        $this->assertStringContainsString('Failed to generate new session ID', Yii::getLogger()->messages[0][0]);

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

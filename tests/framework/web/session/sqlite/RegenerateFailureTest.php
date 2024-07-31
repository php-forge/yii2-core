<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\sqlite;

use Yii;
use yii\db\Connection;
use yii\db\Query;
use yii\web\session\DbSession;
use yiiunit\framework\console\controllers\EchoMigrateController;
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
    use \phpmock\phpunit\PHPMock;

    private Connection $db;

    protected function setUp(): void
    {
        $this->mockWebApplication();

        $this->db = SqliteConnection::getConnection();

        parent::setUp();
    }

    public function testRegenerateIDFailure()
    {
        /** @var DbSession $session */
        $session = $this->getMockBuilder(DbSession::class)->onlyMethods(['getIsActive'])->getMock();
        $session->method('getIsActive')->willReturn(false);

        $this
            ->getFunctionMock('yii\web\session', 'session_id')
            ->expects($this->exactly(2))
            ->will($this->onConsecutiveCalls('old_session_id', ''));

        Yii::getLogger()->flush();

        $session->regenerateID();

        $this->assertStringContainsString('Failed to generate new session ID', Yii::getLogger()->messages[0][0]);
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

<?php

declare(strict_types=1);

namespace yiiunit\framework\i18n;

use Yii;
use yii\db\Query;
use yiiunit\framework\console\controllers\EchoMigrateController;
use yiiunit\TestCase;

abstract class AbstractDbMessageMigration extends TestCase
{
    public function testMigration(): void
    {
        $this->dropTableSession();

        $history = $this->runMigrate('history');

        $this->assertSame(['base'], $history);

        $history = $this->runMigrate('up');

        $this->assertSame(['base', 'i18n_init'], $history);

        $history = $this->runMigrate('down');

        $this->assertSame(['base'], $history);

        $this->createTablesI18N();
    }

    protected function createTablesI18N(): void
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
                'migrationPath' => '@yii/i18n/migrations/',
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

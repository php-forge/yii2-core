<?php

declare(strict_types=1);

namespace yiiunit\framework\i18n\sqlite;

use yiiunit\framework\i18n\AbstractDbMessageMigration;
use yiiunit\support\SqliteConnection;

/**
 * @group db
 * @group sqlite
 * @group i18n
 */
class DbMessageMigrationTest extends AbstractDbMessageMigration
{
    public function setUp(): void
    {
        $this->mockApplication();

        SqliteConnection::getConnection();
    }

    protected function createTablesI18N(): void
    {
        $this->destroyApplication();
    }
}

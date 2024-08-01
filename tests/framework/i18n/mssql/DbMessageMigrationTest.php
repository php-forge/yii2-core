<?php

declare(strict_types=1);

namespace yiiunit\framework\i18n\mssql;

use yiiunit\framework\i18n\AbstractDbMessageMigration;
use yiiunit\support\MssqlConnection;

/**
 * @group db
 * @group mssql
 * @group i18n
 */
class DbMessageMigrationTest extends AbstractDbMessageMigration
{
    public function setUp(): void
    {
        $this->mockApplication();

        MssqlConnection::getConnection();
    }
}

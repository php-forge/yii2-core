<?php

declare(strict_types=1);

namespace yiiunit\framework\i18n\pgsql;

use yiiunit\framework\i18n\AbstractDbMessageMigration;
use yiiunit\support\PgsqlConnection;

/**
 * @group db
 * @group pgsql
 * @group i18n
 */
class DbMessageMigrationTest extends AbstractDbMessageMigration
{
    public function setUp(): void
    {
        $this->mockApplication();

        PgsqlConnection::getConnection();
    }
}

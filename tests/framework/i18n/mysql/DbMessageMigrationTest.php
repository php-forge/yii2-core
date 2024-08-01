<?php

declare(strict_types=1);

namespace yiiunit\framework\i18n\mysql;

use yiiunit\framework\i18n\AbstractDbMessageMigration;
use yiiunit\support\MysqlConnection;

/**
 * @group db
 * @group mysql
 * @group i18n
 */
class DbMessageMigrationTest extends AbstractDbMessageMigration
{
    public function setUp(): void
    {
        $this->mockApplication();

        MysqlConnection::getConnection();
    }
}

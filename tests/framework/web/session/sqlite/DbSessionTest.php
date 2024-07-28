<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\sqlite;

use yiiunit\support\SqliteConnection;

/**
 * Class DbSessionTest.
 *
 * @group db
 * @group sqlite
 * @group session-db-sqlite
 */
class DbSessionTest extends \yiiunit\framework\web\session\AbstractDbSessionTest
{
    protected function setUp(): void
    {
        $this->mockWebApplication();

        $this->db = SqliteConnection::getConnection();

        parent::setUp();
    }
}

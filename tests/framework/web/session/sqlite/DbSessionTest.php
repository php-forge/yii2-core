<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\sqlite;

use yiiunit\framework\web\session\AbstractDbSession;
use yiiunit\support\SqliteConnection;

/**
 * Class DbSessionTest.
 *
 * @group db
 * @group sqlite
 * @group session-db
 */
class DbSessionTest extends AbstractDbSession
{
    protected function setUp(): void
    {
        $this->mockWebApplication();

        $this->db = SqliteConnection::getConnection();

        parent::setUp();
    }
}

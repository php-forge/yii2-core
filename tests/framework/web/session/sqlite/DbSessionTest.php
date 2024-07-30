<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\sqlite;

use Yii;
use yii\web\session\DbSession;
use yiiunit\framework\web\session\AbstractDbSession;
use yiiunit\support\SqliteConnection;

/**
 * Class DbSessionTest.
 *
 * @group db
 * @group sqlite
 * @group session-db-sqlite
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

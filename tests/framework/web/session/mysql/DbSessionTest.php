<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\mysql;

use yiiunit\framework\web\session\AbstractDbSession;
use yiiunit\support\MysqlConnection;

/**
 * Class DbSessionTest.
 *
 * @group db
 * @group mysql
 * @group session-db
 */
class DbSessionTest extends AbstractDbSession
{
    protected function setUp(): void
    {
        $this->mockWebApplication();

        $this->db = MysqlConnection::getConnection();

        parent::setUp();
    }
}

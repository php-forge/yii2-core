<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\mysql;

use yiiunit\support\MysqlConnection;

/**
 * Class DbSessionTest.
 *
 * @group db
 * @group mysql
 * @group session-db-mysql
 */
class DbSessionTest extends \yiiunit\framework\web\session\AbstractDbSessionTest
{
    protected function setUp(): void
    {
        $this->mockWebApplication();

        $this->db = MysqlConnection::getConnection();

        parent::setUp();
    }
}

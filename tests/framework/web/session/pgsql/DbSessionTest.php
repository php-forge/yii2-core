<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\pgsql;

use yiiunit\support\PgsqlConnection;

/**
 * Class DbSessionTest.
 *
 * @group db
 * @group pgsql
 * @group session-db-pgsql
 */
class DbSessionTest extends \yiiunit\framework\web\session\AbstractDbSessionTest
{
    protected function setUp(): void
    {
        $this->mockWebApplication();

        $this->db = PgsqlConnection::getConnection();

        parent::setUp();
    }
}

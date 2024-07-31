<?php

declare(strict_types=1);

namespace yiiunit\framework\web\session\pgsql;

use yiiunit\framework\web\session\AbstractDbSession;
use yiiunit\support\PgsqlConnection;

/**
 * Class DbSessionTest.
 *
 * @group db
 * @group pgsql
 * @group session-db
 */
class DbSessionTest extends AbstractDbSession
{
    protected function setUp(): void
    {
        $this->mockWebApplication();

        $this->db = PgsqlConnection::getConnection();

        parent::setUp();
    }
}
